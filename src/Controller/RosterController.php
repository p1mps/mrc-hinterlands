<?php
namespace App\Controller;

use App\Entity\Pilot;
use App\Entity\Unit;
use App\Form\UnitFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/roster')]
class RosterController extends AbstractController {
    #[Route('', name: 'app_roster')]
    public function index(): Response {
        $company = $this->getUser()->getCompany();
        return $this->render('roster/index.html.twig', [
            'company' => $company,
            'units'   => $company->getUnits(),
            'pilots'  => $company->getPilots(),
            'totalBv' => $company->getTotalBv(),
        ]);
    }

    #[Route('/new', name: 'app_roster_new')]
    public function new(Request $request, EntityManagerInterface $em): Response {
        $unit = new Unit();
        $form = $this->createForm(UnitFormType::class, $unit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $unit->setCompany($this->getUser()->getCompany());
            $em->persist($unit);
            $em->flush();
            $this->addFlash('success', 'Unit added.');
            return $this->redirectToRoute('app_roster');
        }
        return $this->render('roster/form.html.twig', ['form' => $form, 'title' => 'Add Unit']);
    }

    #[Route('/{id}/edit', name: 'app_roster_edit')]
    public function edit(Unit $unit, Request $request, EntityManagerInterface $em): Response {
        if ($unit->getCompany() !== $this->getUser()->getCompany()) {
            throw $this->createAccessDeniedException();
        }
        $form = $this->createForm(UnitFormType::class, $unit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Unit updated.');
            return $this->redirectToRoute('app_roster');
        }
        return $this->render('roster/form.html.twig', ['form' => $form, 'title' => 'Edit Unit']);
    }

    #[Route('/{id}/assign-pilot', name: 'app_roster_assign_pilot', methods: ['POST'])]
    public function assignPilot(Unit $unit, Request $request, EntityManagerInterface $em): Response {
        if ($unit->getCompany() !== $this->getUser()->getCompany()) {
            throw $this->createAccessDeniedException();
        }
        $pilotId = $request->request->get('pilot_id');
        if ($pilotId) {
            $pilot = $em->getRepository(Pilot::class)->find((int) $pilotId);
            if ($pilot && $pilot->getCompany() === $this->getUser()->getCompany()) {
                $unit->setPilot($pilot);
            }
        } else {
            $unit->setPilot(null);
        }
        $em->flush();
        return $this->redirectToRoute('app_roster');
    }

    #[Route('/{id}/delete', name: 'app_roster_delete', methods: ['POST'])]
    public function delete(Unit $unit, EntityManagerInterface $em): Response {
        if ($unit->getCompany() !== $this->getUser()->getCompany()) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($unit);
        $em->flush();
        $this->addFlash('success', 'Unit deleted.');
        return $this->redirectToRoute('app_roster');
    }
}
