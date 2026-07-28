<?php

namespace App\Controller;

use App\Entity\SalvagedMech;
use App\Form\SalvagedMechType;
use App\Service\SalvagedMechService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/salvaged-mechs')]
class SalvagedMechController extends AbstractController
{
    #[Route('/', name: 'app_salvaged_mech_index', methods: ['GET'])]
    public function index(SalvagedMechService $salvagedMechService): Response
    {
        return $this->render('salvaged_mech/index.html.twig', [
            'salvaged_mechs' => $salvagedMechService->getAllMechs(),
        ]);
    }

    #[Route('/new', name: 'app_salvaged_mech_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SalvagedMechService $salvagedMechService): Response
    {
        $mechan = new SalvagedMech();
        $form = $this->createForm(SalvagedMechType::class, $mechan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $salvagedMechService->createMech($mechan);
            return $this->redirectToRoute('app_salvaged_mech_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salvaged_mech/new.html.twig', [
            'salvaged_mech' => $mechan,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_salvaged_mech_show', methods: ['GET'])]
    public function show(SalvagedMechService $salvagedMechService, SalvagedMech $salvagedMech): Response
    {
        return $this->render('salvaged_mech/show.html.twig', [
            'salvaged_mech' => $salvagedMech,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_salvaged_mech_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SalvagedMech $salvagedMech, SalvagedMechService $salvagedMechService): Response
    {
        $form = $this->createForm(SalvagedMechType::class, $salvagedMech);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $salvagedMechService->updateMech($salvagedMech);
            return $this->redirectToRoute('app_salvaged_mech_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salvaged_mech/edit.html.twig', [
            'salvaged_mech' => $salvagedMech,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_salvaged_mech_delete', methods: ['POST'])]
    public function delete(Request $request, SalvagedMech $salvagedMech, SalvagedMechService $salvagedMechService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$salvagedMech->getId(), $request->getPayload()->getString('_token'))) {
            $salvagedMechService->deleteMech($salvagedMech);
        }

        return $this->redirectToRoute('app_salvaged_mech_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/acquire', name: 'app_salvaged_mech_acquire', methods: ['POST'])]
    public function acquire(int $id, Request $request, SalvagedMechService $salvagedMechService): Response
    {
        $company = $this->getUser()->getCompany();

        if (!$company) {
            $this->addFlash('error', 'Cannot acquire mech: No associated company found.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        $mechan = $salvagedMechService->getMech($id);

        if (!$mechan) {
            throw $this->createNotFoundException('Salvaged Mech not found.');
        }

        try {
            $salvagedMechService->acquireMech($mechan, $company);
            $this->addFlash('success', 'Mech acquired successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to acquire mech: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_salvaged_mech_index');
    }
}
