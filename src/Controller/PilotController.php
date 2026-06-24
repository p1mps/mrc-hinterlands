<?php
namespace App\Controller;

use App\DataTables\XpThresholdsTable;
use App\Entity\Pilot;
use App\Form\PilotFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pilots')]
class PilotController extends AbstractController {
    #[Route('', name: 'app_pilots')]
    public function index(): Response {
        $company = $this->getUser()->getCompany();
        $pilots  = $company->getPilots();
        $thresholdAlerts = [];
        foreach ($pilots as $pilot) {
            if ($pilot->isNamed()) {
                $alert = XpThresholdsTable::checkImprovement($pilot->getGunnery(), $pilot->getPiloting(), $pilot->getXp());
                if ($alert) $thresholdAlerts[$pilot->getId()] = $alert;
            }
        }
        return $this->render('pilot/index.html.twig', [
            'company'         => $company,
            'pilots'          => $pilots,
            'thresholdAlerts' => $thresholdAlerts,
        ]);
    }

    #[Route('/new', name: 'app_pilots_new')]
    public function new(Request $request, EntityManagerInterface $em): Response {
        $company = $this->getUser()->getCompany();
        $pilot   = new Pilot();
        $form    = $this->createForm(PilotFormType::class, $pilot);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($pilot->isNamed() && $company->getNamedPilotsCount() >= 4) {
                $this->addFlash('danger', 'Maximum 4 named pilots allowed.');
                return $this->redirectToRoute('app_pilots');
            }
            $pilot->setCompany($company);
            $em->persist($pilot);
            $em->flush();
            $this->addFlash('success', 'Pilot added.');
            return $this->redirectToRoute('app_pilots');
        }
        return $this->render('pilot/form.html.twig', ['form' => $form, 'title' => 'Add Pilot']);
    }

    #[Route('/{id}/edit', name: 'app_pilots_edit')]
    public function edit(Pilot $pilot, Request $request, EntityManagerInterface $em): Response {
        if ($pilot->getCompany() !== $this->getUser()->getCompany()) {
            throw $this->createAccessDeniedException();
        }
        $form = $this->createForm(PilotFormType::class, $pilot);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Pilot updated.');
            return $this->redirectToRoute('app_pilots');
        }
        return $this->render('pilot/form.html.twig', ['form' => $form, 'title' => 'Edit Pilot']);
    }

    #[Route('/{id}/delete', name: 'app_pilots_delete', methods: ['POST'])]
    public function delete(Pilot $pilot, EntityManagerInterface $em): Response {
        if ($pilot->getCompany() !== $this->getUser()->getCompany()) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($pilot);
        $em->flush();
        $this->addFlash('success', 'Pilot deleted.');
        return $this->redirectToRoute('app_pilots');
    }
}
