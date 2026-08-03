<?php

namespace App\Controller;

use App\DataTables\ContractStepsTable;
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
        $mode = $request->query->get('mode', 'standard');

        $data = $generator->generate($scale);
        $data['mode'] = $mode;
        $data['negotiationSummary'] = [
            'reputation' => $reputation,
            'availableSteps' => min($reputation, 2 * $scale),
        ];
        $data['steptsTable'] = json_encode($this->buildStepsTable());

        return $this->render('contract/generate.html.twig', [
            'data'       => $data,
            'scale'      => $scale,
            'company'    => $company,
            'reputation' => $reputation,
        ]);
    }

    private function validateNegotiationState(?array $state, ?array $baseSteps, int $scale, int $reputation, int $maxShifts, int $shiftsUsed): array {
        if (!$state) return ['valid' => true];

        $categories = ['basePayPercent', 'commandRights', 'salvageRights', 'supportTerms', 'transportTerms'];

        if ($shiftsUsed > $maxShifts) {
            return ['valid' => false, 'message' => "Too many reputation shifts: {$shiftsUsed} used, but only {$maxShifts} available for Scale {$scale}."];
        }

        foreach ($categories as $key) {
            if (!isset($state[$key])) continue;

            $finalStep = $state[$key];

            if ($finalStep < 1 || $finalStep > 13) {
                return ['valid' => false, 'message' => "Invalid step for {$key}: must be between 1 and 13."];
            }

            if (!ContractStepsTable::isStepValidForCategory($finalStep, $key)) {
                return ['valid' => false, 'message' => "{$key} step {$finalStep} has no value for this category."];
            }

        }

        return ['valid' => true, 'state' => $state];
    }

    private function buildStepsTable(): array {
        $result = [];
        foreach (range(1, 13) as $step) {
            $result[$step] = [
                'basePayPercent' => ContractStepsTable::getBasePayPercent($step),
                'commandRights' => ContractStepsTable::getCommandRights($step)?->value ?? null,
                'salvageRights' => ContractStepsTable::getSalvageRights($step),
                'supportTerms' => ContractStepsTable::getSupportTerms($step),
                'transportTerms' => ContractStepsTable::getTransportTerms($step),
            ];
        }
        return $result;
    }

    #[Route('/contract/generate/accept', name: 'app_contracts_accept', methods: ['POST'])]
    public function acceptGenerated(Request $request, EntityManagerInterface $em, ContractService $contractService, ContractGeneratorService $generator): Response
    {
        $mode = $request->request->get('mode', 'standard');
        $scale = $request->request->getInt('scale', 1);
        $company = $this->getUser()->getCompany();
        $reputation = $company->getReputation();
        $maxShifts = 2 * $scale;

        if ($mode === 'negotiate') {
            $stateJson = $request->request->get('negotiation_state');
            $baseStepsJson = $request->request->get('base_steps');
            if ($stateJson) {
                $baseSteps = json_decode($baseStepsJson, true) ?? [];
                $shiftsUsed = $request->request->getInt('shifts_used', 0);
                $validated = $this->validateNegotiationState(json_decode($stateJson, true), $baseSteps, $scale, $reputation, $maxShifts, $shiftsUsed);
                if (!$validated['valid']) {
                    $this->addFlash('error', $validated['message']);

                    $data = $generator->generate($scale);
                    $data['mode'] = 'negotiate';
                    $data['negotiationSummary'] = [
                        'reputation' => $reputation,
                        'availableSteps' => min($reputation, 2 * $scale),
                    ];
                    $data['steptsTable'] = json_encode($this->buildStepsTable());

                    return $this->render('contract/generate.html.twig', [
                        'data'       => $data,
                        'scale'      => $scale,
                        'company'    => $company,
                        'reputation' => $reputation,
                    ]);
                }

                $negotiationChanges = [];
                foreach ($validated['state'] as $key => $finalStep) {
                    $negotiationChanges[$key] = $finalStep;
                }

                $negotiated = $generator->generateWithNegotiation($scale, $reputation, $negotiationChanges);

                $contractData = [
                    'type'             => $negotiated['type'],
                    'employer'         => $negotiated['employer'],
                    'employerAffiliation' => $negotiated['affiliation'],
                    'scale'            => $negotiated['scale'],
                    'durationMonths'   => $negotiated['duration'],
                    'basePayPercent'   => $negotiated['basePayPercent'],
                    'commandRights'    => $negotiated['commandRights'],
                    'supportTerms'     => $negotiated['supportTerms'],
                    'salvageRights'    => $negotiated['salvageRights'],
                    'transportTerms'   => $negotiated['transportTerms'],
                    'numberOfTracks'   => $negotiated['numberOfTracks'],
                ];

                $contract = $contractService->createContract($contractData);
                $contract->setPlanet(PlanetTable::randomPlanet());
                $em->persist($contract);
                $em->flush();
                $this->addFlash('success', 'Contract generated.');

                return $this->redirectToRoute('app_contracts');
            }
        }

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
        $em->persist($contract);
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
            return $this->redirectToRoute('app_contracts');
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

            return $this->redirectToRoute('app_contracts');
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

        return $this->redirectToRoute('app_contracts');
    }

    #[Route('/contract/{id}/post-track', name: 'app_contract_post_track')]
    public function postTrack(Contract $contract, Request $request, EntityManagerInterface $em, ContractService $contractService): Response
    {
        $form = $this->createForm(PostTrackFormType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
return $this->redirectToRoute('app_contracts');
        }

        $month = $request->request->getInt('month') ?? 1;
        $contractService->handlePostTrack($contract, $form->getData(), $month);
        $em->flush();
        $this->addFlash('success', 'Post-track results recorded.');

        return $this->redirectToRoute('app_contracts');
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

        return $this->redirectToRoute('app_contracts');
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

        return $this->redirectToRoute('app_contracts');
    }
}
