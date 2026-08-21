<?php

namespace App\Controller;

use App\Entity\Pilot;
use App\Form\PilotFormType;
use App\Repository\ContractRepository;
use App\Service\PilotService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pilots')]
class PilotController extends BaseController
{
    public function __construct(
        EntityManagerInterface $em,
        private readonly ContractRepository $contractRepository,
    ) {
        parent::__construct($em);
    }

    #[Route('', name: 'app_pilots')]
    public function index(PilotService $pilotService): Response
    {
        $pilots = $pilotService->getPilots($this->getUser()->getCompany());

        return $this->render('pilot/index.html.twig', [
            'pilots'          => $pilots,
            'thresholdAlerts' => $pilotService->getXpThresholdAlerts($pilots),
        ]);
    }

    #[Route('/new', name: 'app_pilots_new')]
    public function new(Request $request, PilotService $pilotService): Response
    {
        $company = $this->getUser()->getCompany();
        $pilot = new Pilot();
        $form = $this->createForm(PilotFormType::class, $pilot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $error = $pilotService->createPilot($company, $pilot);
            if ($error) {
                $this->addFlash('danger', $error);
                return $this->redirectToRoute('app_pilots');
            }
            $this->addFlash('success', 'Pilot added.');
            return $this->redirectToRoute('app_pilots');
        }

        return $this->render('pilot/form.html.twig', ['form' => $form, 'title' => 'Add Pilot']);
    }

    #[Route('/{id}/edit', name: 'app_pilots_edit')]
    public function edit(Pilot $pilot, Request $request, PilotService $pilotService): Response
    {
        $form = $this->createForm(PilotFormType::class, $pilot);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pilotService->updatePilot($pilot);
            $this->addFlash('success', 'Pilot updated.');
            return $this->redirectToRoute('app_pilots');
        }

        return $this->render('pilot/form.html.twig', ['form' => $form, 'title' => 'Edit Pilot']);
    }

    #[Route('/{id}/delete', name: 'app_pilots_delete', methods: ['POST'])]
    public function delete(Pilot $pilot, PilotService $pilotService): Response
    {
        $pilotService->deletePilot($pilot);
        $this->addFlash('success', 'Pilot deleted.');
        return $this->redirectToRoute('app_pilots');
    }

    #[Route('/{id}/heal', name: 'app_pilots_heal', methods: ['POST'])]
    public function heal(Pilot $pilot): Response
    {
        $company = $this->getUser()->getCompany();
        $baseCost = 30;

        // Apply active contract support terms to healing cost
        $activeContract = $this->contractRepository->findActiveContractByCompany($company);
        $healCost = 30; // default base cost
        if ($activeContract !== null) {
            $supportType = $activeContract->getSupportType();
            if ($supportType === 'Battle') {
                $healCost = 0;
            } elseif ($supportType === 'Straight') {
                $supportPercent = $activeContract->parseSupportPercent();
                $healCost = max(0, (int) floor($baseCost * (1 - $supportPercent / 100)));
            }
        }

        try {
            if ($healCost > 0) {
                $company->deductSupportPoints($healCost, 'Pilot heal', $this->em->getConnection());
            } else {
                // Battle support or Straight/100%: no deduction
            }
            $pilot->setWounded(false);
            $this->em->flush();
            $this->addFlash('success', 'Pilot healed.');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }
        return $this->redirectToRoute('app_pilots');
    }
}
