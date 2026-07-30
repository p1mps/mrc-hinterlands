<?php

namespace App\Controller;

use App\Entity\Unit;
use App\Form\UnitFormType;
use App\Service\RosterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/roster')]
class RosterController extends AbstractController
{
    #[Route('', name: 'app_roster')]
    public function index(RosterService $rosterService): Response
    {
        $company = $this->getUser()->getCompany();

        return $this->render('roster/index.html.twig', [
            'company' => $company,
            'units'   => $rosterService->getUnits($company),
            'pilots'  => $rosterService->getPilots($company),
            'totalBv' => $company->getTotalBv(),
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
}
