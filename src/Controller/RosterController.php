<?php

namespace App\Controller;

use App\Entity\Unit;
use App\Form\UnitFormType;
use App\Service\DropshipService;
use App\Service\RosterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/roster')]
class RosterController extends BaseController
{
    #[Route('', name: 'app_roster')]
    public function index(
        RosterService $rosterService,
        DropshipService $dropshipService,
    ): Response
    {
        $company = $this->getUser()->getCompany();
        $units = $rosterService->getUnits($company);

        $repairCosts = [];
        $baseRepairCosts = [];
        $supportPercentages = [];
        foreach ($units as $unit) {
            $result = $rosterService->getDiscountedRepairCost($unit, $company);
            $baseRepairCosts[$unit->getId()] = $result['baseCost'];
            $repairCosts[$unit->getId()] = $result['cost'];
            $supportPercentages[$unit->getId()] = $result['supportPercent'];
        }

        return $this->render('roster/index.html.twig', [
            'company' => $company,
            'units'   => $units,
            'pilots'  => $rosterService->getPilots($company),
            'totalBv' => $company->getTotalBv(),
            'dropshipService' => $dropshipService,
            'repairCosts' => $repairCosts,
            'baseRepairCosts' => $baseRepairCosts,
            'supportPercentages' => $supportPercentages,
        ]);
    }

    #[Route('/new', name: 'app_roster_new')]
    public function new(Request $request, RosterService $rosterService): Response
    {
        $company = $this->getUser()->getCompany();
        $unit = new Unit();
        $form = $this->createForm(UnitFormType::class, $unit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rosterService->createUnit($company, $unit);
            $this->addFlash('success', 'Unit added.');
            return $this->redirectToRoute('app_roster');
        }

        return $this->render('roster/form.html.twig', ['form' => $form, 'title' => 'Add Unit']);
    }

    #[Route('/{id}/edit', name: 'app_roster_edit')]
    public function edit(Unit $unit, Request $request, RosterService $rosterService): Response
    {
        $form = $this->createForm(UnitFormType::class, $unit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rosterService->updateUnit($unit);
            $this->addFlash('success', 'Unit updated.');
            return $this->redirectToRoute('app_roster');
        }

        return $this->render('roster/form.html.twig', ['form' => $form, 'title' => 'Edit Unit']);
    }

    #[Route('/{id}/assign-pilot', name: 'app_roster_assign_pilot', methods: ['POST'])]
    public function assignPilot(Unit $unit, Request $request, RosterService $rosterService): Response
    {
        $company = $this->getUser()->getCompany();
        $pilotId = $request->request->get('pilot_id');

        $error = $rosterService->assignPilotToUnit($unit, $pilotId ? (int) $pilotId : null, $company);
        if ($error) {
            $this->addFlash('danger', $error);
        }

        return $this->redirectToRoute('app_roster');
    }

    #[Route('/{id}/delete', name: 'app_roster_delete', methods: ['POST'])]
    public function delete(Unit $unit, RosterService $rosterService): Response
    {
        $rosterService->deleteUnit($unit);
        $this->addFlash('success', 'Unit deleted.');
        return $this->redirectToRoute('app_roster');
    }

    #[Route('/{id}/repair', name: 'app_roster_repair', methods: ['POST'])]
    public function repair(Unit $unit, Request $request, RosterService $rosterService): Response
    {
        $company = $this->getUser()->getCompany();
        $error = $rosterService->repairUnit($unit, $company);
        if ($error) {
            $this->addFlash('danger', $error);
        } else {
            $this->addFlash('success', 'Unit repaired successfully.');
        }

        return $this->redirectToRoute('app_roster');
    }

    #[Route('/{id}/battlefield-lose', name: 'app_roster_battlefield_lose', methods: ['POST'])]
    public function battlefieldLose(Unit $unit, RosterService $rosterService): Response
    {
        $company = $this->getUser()->getCompany();
        $error = $rosterService->battlefieldLoseUnit($unit, $company);
        if ($error) {
            $this->addFlash('danger', $error);
        } else {
            $this->addFlash('success', 'Unit marked as battlefield loss. Support points credited.');
        }

        return $this->redirectToRoute('app_roster');
    }
}
