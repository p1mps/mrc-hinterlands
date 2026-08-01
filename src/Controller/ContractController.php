<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Enum\CommandRights;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Form\ContractEditFormType;
use App\Form\PostTrackFormType;
use App\Service\ContractGeneratorService;
use App\Service\ContractService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\DataTables\PlanetTable;

class ContractController extends AbstractController
{
    #[Route('/contract', name: 'app_contracts')]
    public function index(EntityManagerInterface $em, ContractGeneratorService $generator): Response
    {
        $contracts = $em->getRepository(Contract::class)->findAllOrderedByConnections();
        return $this->render('contract/index.html.twig', [
            'contracts' => $contracts,
        ]);
    }

    #[Route('/contract/generate', name: 'app_contracts_generate', methods: ['GET'])]
    public function generate(Request $request, EntityManagerInterface $em, ContractService $contractService, ContractGeneratorService $generator): Response
    {
        $scale = $request->query->getInt('scale', 1);
        $company = $this->getUser()->getCompany();
        $reputation = $company->getReputation();

        if ($request->query->getBoolean('negotiate', false)) {
            $data = $generator->generateWithNegotiation($scale, $reputation);
        } else {
            $data = $generator->generate($scale);
        }

        return $this->render('contract/generate.html.twig', [
            'data'       => $data,
            'scale'      => $scale,
            'company'    => $company,
            'reputation' => $reputation,
        ]);
    }

    #[Route('/contract/generate/negotiate', name: 'app_contracts_negotiate', methods: ['POST'])]
    public function negotiateGenerated(Request $request, EntityManagerInterface $em, ContractService $contractService, ContractGeneratorService $generator): Response
    {
        $company = $this->getUser()->getCompany();
        $reputation = $company->getReputation();
        $scale = $request->request->getInt('scale', 1);

        $negotiationChanges = [];
        if ($request->request->has('negotiation')) {
            $negotiationChanges = $request->request->all('negotiation');
        }

        $data = $generator->generateWithNegotiation($scale, $reputation, $negotiationChanges);

        $data['scale'] = $scale;
        $data['company'] = $company;
        $data['reputation'] = $reputation;

        return $this->render('contract/generate.html.twig', [
            'data'       => $data,
            'scale'      => $scale,
            'company'    => $company,
            'reputation' => $reputation,
        ]);
    }

    #[Route('/contract/generate/accept', name: 'app_contracts_accept', methods: ['POST'])]
    public function acceptGenerated(Request $request, EntityManagerInterface $em, ContractService $contractService,  ContractGeneratorService $generator): Response
    {
        $data = [
            'type'             => ContractType::from($request->request->get('type')),
            'employer'         => $request->request->get('employer'),
            'employerAffiliation' => $request->request->get('affiliation'),
            'scale'            => $request->request->getInt('scale'),
            'durationMonths'   => $request->request->getInt('duration'),
            'basePayPercent'   => $request->request->get('basePayPercent') ?: null,
            'commandRights'    => CommandRights::from($request->request->get('commandRights')),
            'supportTerms'     => $request->request->get('supportTerms'),
            'salvageRights'    => $request->request->get('salvageRights'),
            'transportTerms'   => $request->request->get('transportTerms'),
            'numberOfTracks'   => $request->request->getInt('numberOfTracks'),
        ];

        $contract = $contractService->createContract($data);
        $contract->setPlanet(PlanetTable::randomPlanet());
        $opposingData = $generator->generateOpposing($data['type'], $data['scale'], $data['numberOfTracks']);
        $opposing = $contractService->createContract($opposingData);
        $opposing->setPlanet(PlanetTable::randomPlanet());
        $opposing->setLinkedContract($contract);
        $contract->setLinkedContract($opposing);
        $em->persist($contract);
        $em->persist($opposing);
        $em->flush();
        $this->addFlash('success', 'Contract generated.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/generate/discard', name: 'app_contracts_discard', methods: ['POST'])]
    public function discard(): Response
    {
        return $this->redirectToRoute('app_contracts');
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

    #[Route('/contract/{id}', name: 'app_contracts_show')]
    public function show(Contract $contract): Response
    {
        return $this->render('contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/contract/{id}/edit', name: 'app_contracts_edit')]
    public function edit(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ContractEditFormType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Contract updated.');

            return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
        }

        return $this->render('contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/contract/{id}/accept', name: 'app_contract_accept', methods: ['POST'])]
    public function accept(Contract $contract, EntityManagerInterface $em, ContractService $contractService): Response
    {
        if ($contract->getStatus() !== ContractStatus::Available) {
            $this->addFlash('error', 'Contract is not available.');
            return $this->redirectToRoute('app_contracts');
        }

        $contract->setCompany($this->getUser()->getCompany());
        $contractService->acceptContract($contract);
        $em->persist($contract);
        $em->flush();
        $this->addFlash('success', 'Contract accepted.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/{id}/generate-opposing', name: 'app_contracts_generate_opposing', methods: ['POST'])]
    public function generateOpposing(Contract $contract, EntityManagerInterface $em, ContractService $contractService, ContractGeneratorService $generator): Response
    {
        if ($contract->isOpposing()) {
            $this->addFlash('error', 'Cannot generate opposing for an opposing contract.');
            return $this->redirectToRoute('app_contracts');
        }

        $opposingData = $generator->generateOpposing($contract->getType(), $contract->getScale(), $contract->getNumberOfTracks());
        $opposing = $contractService->createContract($opposingData);
        $opposing->setPlanet(PlanetTable::randomPlanet());
        $opposing->setLinkedContract($contract);
        $em->persist($opposing);
        $em->flush();
        $this->addFlash('success', 'Opposing contract generated.');

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/{id}/delete', name: 'app_contracts_delete', methods: ['POST'])]
    public function delete(Contract $contract, EntityManagerInterface $em): Response
    {
        if ($contract->isOpposing()) {
            $contract->setLinkedContract(null);
            $em->flush();
        } else {
            foreach ($contract->getOpposingContracts() as $opposing) {
                $em->remove($opposing);
            }
            $contract->setLinkedContract(null);
            $em->flush();
        }
        $em->remove($contract);
        $em->flush();
        $this->addFlash('success', 'Contract deleted.');

        return $this->redirectToRoute('app_contracts');
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
