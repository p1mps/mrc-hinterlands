<?php

namespace App\Controller;

use App\DataTables\ContractTrackTable;
use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Service\ContractLogService;
use App\DataTables\TerrainTable;
use App\Form\ContractLogEntryEditFormType;
use App\Form\PostTrackFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contracts/{id}/log')]
class ContractLogController extends BaseController
{
    public function __construct(
        private readonly ContractLogService $logService,
        EntityManagerInterface $em,
    ) {
        parent::__construct($em);
    }

    #[Route('/entry/{entryId}/edit', name: 'app_contracts_log_edit', methods: ['GET', 'POST'])]
    public function editEntry(Contract $contract, int $entryId, Request $request): Response
    {
        $entry = $this->em->getRepository(ContractLogEntry::class)->find($entryId);

        if (!$entry || $entry->getContract() !== $contract) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ContractLogEntryEditFormType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($entry->getEntryType()->value === 'track_setup' && $entry->getTrack()) {
                $missionType = $request->request->get('missionType', $entry->getTrack()->getMissionType());
                $terrain = $request->request->get('terrain', $entry->getTrack()->getTerrain());
                $this->logService->updateTrackSetupData($entry, $missionType, $terrain);
            }

            $this->em->flush();
            $this->addFlash('success', 'Log entry updated.');

            return $this->redirectToRoute('app_contracts_show', [
                'id' => $contract->getId(),
            ]);
        }

        return $this->render('contract_log/edit.html.twig', [
            'contract'     => $contract,
            'entry'        => $entry,
            'form'         => $form,
            'missionTypes' => ContractTrackTable::getAllMissionTypes(),
            'terrains'     => array_keys(TerrainTable::getAllTerrains()),
        ]);
    }

    #[Route('/entry/{entryId}/delete', name: 'app_contracts_log_delete', methods: ['POST'])]
    public function delete(Contract $contract, int $entryId): Response
    {
        $entry = $this->em->getRepository(ContractLogEntry::class)->find($entryId);

        if ($entry && $entry->getContract() === $contract) {
            $this->logService->deleteEntry($contract, $entry);
            $this->addFlash('success', 'Log entry deleted.');
        }

        return $this->redirectToRoute('app_contracts_show', [
            'id' => $contract->getId(),
        ]);
    }

    #[Route('/add', name: 'app_contracts_log_add', methods: ['GET', 'POST'])]
    public function add(Contract $contract, Request $request): Response
    {
        $company = $contract->getCompany();
        $action = $request->request->get('action');
        $postTrackForm = $this->createForm(PostTrackFormType::class);
        $postTrackForm->handleRequest($request);
        $currentMonth = $this->logService->calculateCurrentMonth($contract);

        if ($request->isMethod('POST')) {
            match ($action) {
                'transport'   => $this->logService->handleTransport($contract, $company),
                'maintenance' => $this->logService->handleMaintenance($contract, $company, $currentMonth),
                'base_pay'    => $this->logService->handleBasePay($contract, $company, $currentMonth),
                'track_setup' => $this->logService->handleTrackSetup($contract, $currentMonth, $request->request->getBoolean('toftt')),
                'post_track'  => $this->handlePostTrackAction($contract, $company, $postTrackForm, $request->request->getInt('month')),
                'downtime'    => $this->logService->handleDowntime($contract, $company, $request->request->getInt('month'), $request->request->getString('note', ''), $request->request->getInt('amount', 0)),
                default       => null,
            };

            if ($action !== 'post_track') {
                $this->addFlash('success', "Log entry added $action.");
                return $this->redirectToRoute('app_contracts_show', [
                    'id' => $contract->getId(),
                ]);
            }
        }

        return $this->render('contract_log/add.html.twig', [
            'contract'      => $contract,
            'currentMonth'  => $currentMonth,
            'postTrackForm' => $postTrackForm,
        ]);
    }

    private function handlePostTrackAction(Contract $contract, $company, object $form, int $month): void
    {
        try {
            $this->logService->handlePostTrack($contract, $company, $form->getData() ?? [], $month);
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }
    }
}
