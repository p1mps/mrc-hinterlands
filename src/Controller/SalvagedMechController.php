<?php

namespace App\Controller;

use App\Entity\SalvagedMech;
use App\Form\SalvagedMechType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/salvaged-mechs')]
class SalvagedMechController extends AbstractController
{
    #[Route('/', name: 'app_salvaged_mech_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $salvagedMechs = $entityManager->getRepository(SalvagedMech::class)->findAll();

        return $this->render('salvaged_mech/index.html.twig', [
            'salvaged_mechs' => $salvagedMechs,
        ]);
    }

    #[Route('/new', name: 'app_salvaged_mech_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $salvagedMech = new SalvagedMech();
        $form = $this->createForm(SalvagedMechType::class, $salvagedMech);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($salvagedMech);
            $entityManager->flush();

            return $this->redirectToRoute('app_salvaged_mech_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salvaged_mech/new.html.twig', [
            'salvaged_mech' => $salvagedMech,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_salvaged_mech_show', methods: ['GET'])]
    public function show(SalvagedMech $salvagedMech): Response
    {
        return $this->render('salvaged_mech/show.html.twig', [
            'salvaged_mech' => $salvagedMech,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_salvaged_mech_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SalvagedMech $salvagedMech, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SalvagedMechType::class, $salvagedMech);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_salvaged_mech_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salvaged_mech/edit.html.twig', [
            'salvaged_mech' => $salvagedMech,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_salvaged_mech_delete', methods: ['POST'])]
    public function delete(Request $request, SalvagedMech $salvagedMech, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$salvagedMech->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($salvagedMech);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_salvaged_mech_index', [], Response::HTTP_SEE_OTHER);
    }
}
