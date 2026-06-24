<?php
namespace App\Controller;

use App\Entity\SupportPointEntry;
use App\Form\SupportPointEntryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/support-points')]
class SupportPointController extends AbstractController {
    #[Route('', name: 'app_support_points')]
    public function index(Request $request, EntityManagerInterface $em): Response {
        $company = $this->getUser()->getCompany();
        $entry   = new SupportPointEntry();
        $form    = $this->createForm(SupportPointEntryType::class, $entry);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entry->setCompany($company);
            $em->persist($entry);
            $em->flush();
            $this->addFlash('success', 'Entry added.');
            return $this->redirectToRoute('app_support_points');
        }
        return $this->render('support_point/index.html.twig', [
            'entries' => $company->getSupportPointEntries(),
            'balance' => $company->getSupportPointsBalance(),
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_support_points_delete', methods: ['POST'])]
    public function delete(SupportPointEntry $entry, EntityManagerInterface $em): Response {
        if ($entry->getCompany() !== $this->getUser()->getCompany()) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($entry);
        $em->flush();
        $this->addFlash('success', 'Entry deleted.');
        return $this->redirectToRoute('app_support_points');
    }
}
