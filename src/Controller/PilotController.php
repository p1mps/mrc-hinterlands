<?php

namespace App\Controller;

use App\Entity\Pilot;
use App\Form\PilotFormType;
use App\Service\PilotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pilots')]
class PilotController extends AbstractController
{
    #[Route('', name: 'app_pilots')]
    public function index(PilotService $pilotService): Response
    {
        $company = $this->getUser()->getCompany();
        $pilots = $pilotService->getPilots($company);

        return $this->render('pilot/index.html.twig', [
            'company'         => $company,
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
}
