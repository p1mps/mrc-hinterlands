<?php
namespace App\Controller;

use App\Form\SupportPointEntryType;
use App\Service\SupportPointService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/support-points')]
class SupportPointController extends AbstractController
{
    #[Route('', name: 'app_support_points')]
    public function index(
        Request $request,
        SupportPointService $supportPointService
    ): Response {
        $company = $this->getUser()->getCompany();
        $entry = new \App\Entity\SupportPointEntry();
        $form = $this->createForm(SupportPointEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $supportPointService->addEntry(
                $company,
                $form->get('amount')->getData(),
                $form->get('description')->getData(),
            );

            $this->addFlash('success', 'Entry added.');
            return $this->redirectToRoute('app_support_points');
        }

        $data = $supportPointService->getCompanySupportPoints($this->getUser()->getCompany());

        return $this->render('support_point/index.html.twig', [
            'entries' => $data['entries'],
            'balance' => $data['balance'],
            'form'    => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_support_points_delete', methods: ['POST'])]
    public function delete(
        \App\Entity\SupportPointEntry $entry,
        SupportPointService $supportPointService
    ): Response {
        $supportPointService->deleteEntry($entry);
        $this->addFlash('success', 'Entry deleted.');
        return $this->redirectToRoute('app_support_points');
    }
}
