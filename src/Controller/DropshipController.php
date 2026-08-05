<?php

namespace App\Controller;

use App\Entity\Dropship;
use App\Form\DropshipType;
use App\Service\DropshipService;
use App\Service\SalvagedMechService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dropship')]
class DropshipController extends AbstractController
{
    #[Route('/', name: 'app_dropship_show', methods: ['GET'])]
    public function show(DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();
        $dropship = $dropshipService->getDropshipByCompany($company);

        return $this->render('dropship/show.html.twig', [
            'dropship' => $dropship,
            'dropshipService' => $dropshipService,
        ]);
    }

    #[Route('/new', name: 'app_dropship_new', methods: ['GET', 'POST'])]
    public function new(Request $request, DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();

        $existing = $dropshipService->getDropshipByCompany($company);
        if ($existing !== null) {
            $this->addFlash('error', 'This company already has a dropship.');
            return $this->redirectToRoute('app_dropship_show');
        }

        $dropship = new Dropship();
        $form = $this->createForm(DropshipType::class, $dropship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dropshipService->createDropship($company, $dropship->getMaxCapacity(), $dropship->getName(), $dropship->getMekbayCapacity());
                $this->addFlash('success', 'Dropship created successfully.');
                return $this->redirectToRoute('app_dropship_show');
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('dropship/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dropship_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dropship $dropship, DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();

        if ($dropship->getCompany() !== $company) {
            $this->addFlash('error', 'You do not have permission to edit this dropship.');
            return $this->redirectToRoute('app_dropship_show');
        }

        $form = $this->createForm(DropshipType::class, $dropship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dropshipService->updateDropship($dropship, $dropship->getMaxCapacity(), $dropship->getMekbayCapacity());
                $this->addFlash('success', 'Dropship updated successfully.');
                return $this->redirectToRoute('app_dropship_show');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('dropship/edit.html.twig', [
            'dropship' => $dropship,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_dropship_delete', methods: ['POST'])]
    public function delete(Request $request, Dropship $dropship, DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();

        if ($dropship->getCompany() !== $company) {
            $this->addFlash('error', 'You do not have permission to delete this dropship.');
            return $this->redirectToRoute('app_dropship_show');
        }

        if ($this->isCsrfTokenValid('delete' . $dropship->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $dropshipService->deleteDropship($dropship);
                $this->addFlash('success', 'Dropship deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to delete dropship: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_dropship_show');
    }

    #[Route('/assign-mech/{id}', name: 'app_dropship_assign_mech', methods: ['POST'])]
    public function assignMech(Request $request, Dropship $dropship, int $id, DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();

        if ($dropship->getCompany() !== $company) {
            $this->addFlash('error', 'You do not have permission to manage this dropship.');
            return $this->redirectToRoute('app_dropship_show');
        }

        $mechan = $this->salvagedMechService->getMech($id);

        if (!$mechan) {
            $this->addFlash('error', 'Mech not found.');
            return $this->redirectToRoute('app_dropship_show');
        }

        try {
            $dropshipService->assignMechToDropship($mechan, $dropship);
            $this->addFlash('success', 'Mech assigned to dropship.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_salvaged_mech_index');
    }

    #[Route('/assign-unit/{id}', name: 'app_dropship_assign_unit', methods: ['POST'])]
    public function assignUnit(Request $request, Dropship $dropship, int $id, DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();

        if ($dropship->getCompany() !== $company) {
            $this->addFlash('error', 'You do not have permission to manage this dropship.');
            return $this->redirectToRoute('app_dropship_show');
        }

        $unit = $this->em->getRepository(\App\Entity\Unit::class)->find($id);

        if (!$unit) {
            $this->addFlash('error', 'Unit not found.');
            return $this->redirectToRoute('app_dropship_show');
        }

        try {
            $dropshipService->assignUnitToDropship($unit, $dropship);
            $this->addFlash('success', 'Unit assigned to dropship.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_roster');
    }

    #[Route('/unassign-mech/{dropshipId}/{mechanId}', name: 'app_dropship_unassign_mech', methods: ['POST'])]
    public function unassignMech(Dropship $dropship, int $mechanId): Response
    {
        $company = $this->getUser()->getCompany();

        if ($dropship->getCompany() !== $company) {
            $this->addFlash('error', 'Permission denied.');
            return $this->redirectToRoute('app_dropship_show');
        }

        $mechan = $this->salvagedMechService->getMech($mechanId);

        if ($mechan && $mechan->getDropship() === $dropship) {
            $mechan->setDropship(null);
            $this->em->flush();
            $this->addFlash('success', 'Mech removed from dropship.');
        }

        return $this->redirectToRoute('app_dropship_show');
    }

    #[Route('/unassign-unit/{dropshipId}/{unitId}', name: 'app_dropship_unassign_unit', methods: ['POST'])]
    public function unassignUnit(Dropship $dropship, int $unitId): Response
    {
        $company = $this->getUser()->getCompany();

        if ($dropship->getCompany() !== $company) {
            $this->addFlash('error', 'Permission denied.');
            return $this->redirectToRoute('app_dropship_show');
        }

        $unit = $this->em->getRepository(\App\Entity\Unit::class)->find($unitId);

        if ($unit && $unit->getDropship() === $dropship) {
            $unit->setDropship(null);
            $this->em->flush();
            $this->addFlash('success', 'Unit removed from dropship.');
        }

        return $this->redirectToRoute('app_dropship_show');
    }
}
