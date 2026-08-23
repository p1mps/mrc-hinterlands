<?php

namespace App\Controller;

use App\DataTables\PlanetTable;
use App\Entity\Contract;
use App\Enum\CommandRights;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Form\ContractEditFormType;
use App\Form\PostTrackFormType;
use App\Service\ContractGeneratorService;
use App\Service\ContractLogService;
use App\Service\ContractService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContractController extends BaseController
{
    #[Route('/contract', name: 'app_contracts')]
    public function index(EntityManagerInterface $em): Response
    {
        $acceptedContracts = $em->getRepository(Contract::class)->findAllOrderedByConnections(ContractStatus::Accepted);
        $availableContracts = $em->getRepository(Contract::class)->findAllOrderedByConnections(ContractStatus::Available);
        $completedContracts = $em->getRepository(Contract::class)->findAllOrderedByConnections(ContractStatus::Completed);

        return $this->render('contract/index.html.twig', [
            'acceptedContracts' => $acceptedContracts,
            'availableContracts' => $availableContracts,
            'completedContracts' => $completedContracts,
        ]);
    }

    #[Route('/contract/generate', name: 'app_contracts_generate', methods: ['GET'])]
    public function generate(Request $request, EntityManagerInterface $em, ContractService $contractService, ContractGeneratorService $generator): Response
    {
        $scale = $request->query->getInt('scale', 1);
        $data = $generator->generate($scale);

        return $this->render('contract/generate.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/contract/generate/accept', name: 'app_contracts_accept', methods: ['POST'])]
    public function acceptGenerated(Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $data = [
            'type'             => ContractType::from($request->request->get('type')),
            'employer'         => $request->request->get('employer'),
            'employerAffiliation' => $request->request->get('affiliation'),
            'scale'            => $request->request->getInt('scale'),
            'durationMonths'   => $request->request->getInt('duration'),
            'basePayPercent'   => $request->request->get('basePayPercent') ?: null,
            'commandRights'    => CommandRights::from($request->request->get('commandRights')),
            'supportTerms'     => $request->request->get('supportTerms'),
            'salvageRights'    => $request->request->get('salvageRights'),
            'transportTerms'   => $request->request->get('transportTerms'),
            'numberOfTracks'   => $request->request->getInt('numberOfTracks'),
        ];

        $contract = $contractService->createContract($data);
        $contract->setPlanet(PlanetTable::randomPlanet());
        $em->persist($contract);
        $em->flush();
        $this->addFlash('success', 'Contract generated.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/generate/discard', name: 'app_contracts_discard', methods: ['POST'])]
    public function discard(): Response
    {
        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/new', name: 'app_contract_new')]
    public function new(Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $form = $this->createForm(ContractEditFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract = $contractService->createContract($form->getData());
            $em->persist($contract);
            $em->flush();

            $this->addFlash('success', 'Contract created.');
            return $this->redirectToRoute('app_contracts');
        }

        return $this->render('contract/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/contract/{id}', name: 'app_contracts_show')]
    public function show(Contract $contract, ContractLogService $logService): Response
    {
        $currentMonth = $logService->calculateCurrentMonth($contract);

        return $this->render('contract/show.html.twig', [
            'contract'      => $contract,
            'currentMonth'  => $currentMonth,
        ]);
    }

    #[Route('/contract/{id}/negotiate', name: 'app_contracts_negotiate_view', methods: ['GET'])]
    public function negotiateView(Contract $contract, ContractGeneratorService $generator): Response
    {
        $company = $this->getUser()->getCompany();
        $data = $generator->negotiateExistingContract($contract, $company->getReputation());

        $initialSteps = $this->getInitialSteps($data);

        $stepsTable = $data['stepsTable'] ?? [];
        foreach ($stepsTable as $stepKey => &$stepData) {
            if (isset($stepData['commandRights']) && $stepData['commandRights'] instanceof \App\Enum\CommandRights) {
                $stepData['commandRights'] = $stepData['commandRights']->value;
            }
        }
        unset($stepData);

        $negotiationData = json_encode([
            'scale'        => $contract->getScale(),
            'reputation'   => $company->getReputation(),
            'initialSteps' => $initialSteps,
            'stepsTable'   => $stepsTable,
        ]);

        return $this->render('contract/negotiate.html.twig', [
            'contract'          => $contract,
            'negotiationData'   => $negotiationData,
        ]);
    }

    #[Route('/contract/{id}/negotiate/accept', name: 'app_contracts_negotiate_accept', methods: ['POST'])]
    public function acceptNegotiation(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService, ContractGeneratorService $generator): Response
    {
        $company = $this->getUser()->getCompany();

        $negotiationChanges = [];
        $categories = ['basePayPercent', 'commandRights', 'salvageRights', 'supportTerms', 'transportTerms'];
        foreach ($categories as $cat) {
            $stepStr = $request->request->get('negotiation_' . $cat);
            if ($stepStr !== null) {
                $negotiationChanges[$cat] = (int) $stepStr;
            }
        }

        $data = $generator->negotiateExistingContract($contract, $company->getReputation(), $negotiationChanges);

        $contractData = [
            'type'             => $data['type'],
            'employer'         => $data['employer'],
            'employerAffiliation' => $data['affiliation'],
            'scale'            => $data['scale'],
            'durationMonths'   => $data['duration'],
            'basePayPercent'   => $data['basePayPercent'],
            'commandRights'    => $data['commandRights'],
            'supportTerms'     => $data['supportTerms'],
            'salvageRights'    => $data['salvageRights'],
            'transportTerms'   => $data['transportTerms'],
            'numberOfTracks'   => $data['numberOfTracks'],
        ];

        $contractService->applyNegotiatedTerms($contract, $contractData);
        $em->persist($contract);
        $em->flush();
        $this->addFlash('success', 'Contract terms updated via negotiation.');

        return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
    }

    private function getInitialSteps(array $data): array {
        return [
            'basePayPercent' => $this->getStepForValue('basePayPercent', $data['basePayPercent']),
            'commandRights'  => $this->getStepForValue('commandRights', $data['commandRights']->value),
            'salvageRights'  => $this->getStepForValue('salvageRights', $data['salvageRights']),
            'supportTerms'   => $this->getStepForValue('supportTerms', $data['supportTerms']),
            'transportTerms' => $this->getStepForValue('transportTerms', $data['transportTerms'] ?? '—'),
        ];
    }

    private function getStepForValue(string $category, mixed $value): ?int {
        for ($i = 1; $i <= 13; $i++) {
            $values = \App\DataTables\ContractStepsTable::getStepValues($i);
            $match = match ($category) {
                'basePayPercent' => $values[0] === $value,
                'commandRights'  => $values[1] instanceof \App\Enum\CommandRights ? $values[1]->value === $value : $value === null,
                'salvageRights'  => $values[2] === $value,
                'supportTerms'   => $values[3] === $value,
                'transportTerms' => $values[4] === $value,
                default          => false,
            };
            if ($match) return $i;
        }
        return null;
    }

    #[Route('/contract/{id}/edit', name: 'app_contracts_edit')]
    public function edit(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ContractEditFormType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Contract updated.');

            return $this->redirectToRoute('app_contracts');
        }

        return $this->render('contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/contract/{id}/accept', name: 'app_contract_accept', methods: ['POST'])]
    public function accept(Contract $contract, EntityManagerInterface $em, ContractService $contractService): Response
    {
        if ($contract->getStatus() !== ContractStatus::Available) {
            $this->addFlash('error', 'Contract is not available.');
            return $this->redirectToRoute('app_contracts');
        }

        $contract->setCompany($this->getUser()->getCompany());
        $contractService->acceptContract($contract);
        $em->persist($contract);
        $em->flush();
        $this->addFlash('success', 'Contract accepted.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route("/contract/{id}/generate-opposing", name: "app_contracts_generate_opposing", methods: ["POST"])]
    public function generateOpposing(Contract $contract, EntityManagerInterface $em, ContractService $contractService, ContractGeneratorService $generator): Response
    {
        if ($contract->isOpposing()) {
            $this->addFlash('error', 'Cannot generate opposing for an opposing contract.');
            return $this->redirectToRoute('app_contracts');
        }

        $opposingData = $generator->generateOpposing($contract->getType(), $contract->getScale(), $contract->getNumberOfTracks(), $contract->getPlanet(), $contract->getIntensity());
        $opposing = $contractService->createContract($opposingData);
        $opposing->setLinkedContract($contract);
        $contract->getOpposingContracts()->add($opposing);
        $em->persist($opposing);
        $em->flush();
        $this->addFlash('success', 'Opposing contract generated.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/{id}/delete', name: 'app_contracts_delete', methods: ['POST'])]
    public function delete(Contract $contract, EntityManagerInterface $em): Response
    {
        if ($contract->isOpposing()) {
            $contract->setLinkedContract(null);
            $em->flush();
        } else {
            foreach ($contract->getOpposingContracts() as $opposing) {
                $em->remove($opposing);
            }
            $contract->setLinkedContract(null);
            $em->flush();
        }
        $em->remove($contract);
        $em->flush();
        $this->addFlash('success', 'Contract deleted.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/{id}/track-setup', name: 'app_contract_track_setup')]
    public function trackSetup(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $month = $request->request->getInt('month') ?? 1;
        $contractService->handleTrackSetup($contract, $month);
        $em->flush();
        $this->addFlash('success', 'Track setup rolled and recorded.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/{id}/post-track', name: 'app_contract_post_track')]
    public function postTrack(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $form = $this->createForm(PostTrackFormType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
return $this->redirectToRoute('app_contracts');
        }

        $month = $request->request->getInt('month') ?? 1;
        $contractService->handlePostTrack($contract, $form->getData(), $month);
        $em->flush();
        $this->addFlash('success', 'Post-track results recorded.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/{id}/downtime', name: 'app_contract_downtime')]
    public function downtime(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $month = $request->request->getInt('month') ?? 1;
        $amount = $request->request->getInt('amount', 0);
        $note = $request->request->getString('note', '');
        $contractService->handleDowntime($contract, ['amount' => $amount, 'note' => $note], $month);
        $em->flush();
        $this->addFlash('success', 'Downtime note added.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/{id}/salvage', name: 'app_contract_salvage')]
    public function salvage(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $month = $request->request->getInt('month') ?? 1;
        $amount = $request->request->getInt('amount', 0);
        $note = $request->request->getString('note', '');
        $contractService->handleSalvage($contract, ['amount' => $amount, 'note' => $note], $month);
        $em->flush();
        $this->addFlash('success', 'Salvage recorded.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/{id}/advance-month', name: 'app_contract_advance_month', methods: ['POST'])]
    public function advanceMonth(Contract $contract, EntityManagerInterface $em, ContractLogService $logService): Response
    {
        try {
            $logService->advanceMonth($contract);
            $em->flush();
            $this->addFlash('success', 'Month advanced.');
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
    }
}
