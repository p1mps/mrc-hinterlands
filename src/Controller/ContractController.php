<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Enum\ContractStatus;
use App\Form\ContractEditFormType;
use App\Form\PostTrackFormType;
use App\Service\ContractService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContractController extends AbstractController
{
    #[Route('/contract', name: 'app_contract')]
    public function index(EntityManagerInterface $em): Response
    {
        $contracts = $em->getRepository(Contract::class)->findAll();
        return $this->render('contract/index.html.twig', [
            'contracts' => $contracts,
        ]);
    }

    #[Route('/contract/new', name: 'app_contract_new')]
    public function new(Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $form = $this->createForm(ContractEditFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract = $contractService->createContract($form->getData());
            $em->persist($contract);
            $em->flush();

            $this->addFlash('success', 'Contract created.');
            return $this->redirectToRoute('app_contract');
        }

        return $this->render('contract/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/contract/{id}/accept', name: 'app_contract_accept')]
    public function accept(Contract $contract, EntityManagerInterface $em, ContractService $contractService): Response
    {
        if ($contract->getStatus() !== ContractStatus::Available) {
            $this->addFlash('error', 'Contract is not available.');
            return $this->redirectToRoute('app_contract');
        }

        $contractService->acceptContract($contract);
        $em->flush();
        $this->addFlash('success', 'Contract accepted.');

        return $this->redirectToRoute('app_contract');
    }

    #[Route('/contract/{id}/track-setup', name: 'app_contract_track_setup')]
    public function trackSetup(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $month = $request->request->getInt('month') ?? 1;
        $contractService->handleTrackSetup($contract, $month);
        $em->flush();
        $this->addFlash('success', 'Track setup rolled and recorded.');

        return $this->redirectToRoute('app_contract');
    }

    #[Route('/contract/{id}/post-track', name: 'app_contract_post_track')]
    public function postTrack(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $form = $this->createForm(PostTrackFormType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirectToRoute('app_contract');
        }

        $month = $request->request->getInt('month') ?? 1;
        $contractService->handlePostTrack($contract, $form->getData(), $month);
        $em->flush();
        $this->addFlash('success', 'Post-track results recorded.');

        return $this->redirectToRoute('app_contract');
    }

    #[Route('/contract/{id}/downtime', name: 'app_contract_downtime')]
    public function downtime(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $month = $request->request->getInt('month') ?? 1;
        $amount = $request->request->getInt('amount', 0);
        $note = $request->request->getString('note', '');
        $contractService->handleDowntime($contract, ['amount' => $amount, 'note' => $note], $month);
        $em->flush();
        $this->addFlash('success', 'Downtime note added.');

        return $this->redirectToRoute('app_contract');
    }

    #[Route('/contract/{id}/salvage', name: 'app_contract_salvage')]
    public function salvage(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $month = $request->request->getInt('month') ?? 1;
        $amount = $request->request->getInt('amount', 0);
        $note = $request->request->getString('note', '');
        $contractService->handleSalvage($contract, ['amount' => $amount, 'note' => $note], $month);
        $em->flush();
        $this->addFlash('success', 'Salvage recorded.');

        return $this->redirectToRoute('app_contract');
    }
}
