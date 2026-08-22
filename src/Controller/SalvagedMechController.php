<?php

namespace App\Controller;

use App\Entity\SalvagedMech;
use App\Entity\Unit;
use App\Form\BattlefieldSalvageMechType;
use App\Form\SalvagedMechType;
use App\Form\ScrapyardMechType;
use App\Service\DropshipService;
use App\Service\SalvageCalculationService;
use App\Service\SalvagedMechService;
use App\Service\SalvageRightsParser;
use App\Service\ScrapyardService;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/salvaged-mechs')]
class SalvagedMechController extends BaseController
{
    private readonly SalvageCalculationService $salvageCalc;
    private readonly ScrapyardService $scrapyardService;

    public function __construct(
        EntityManagerInterface $em,
        SalvageCalculationService $salvageCalc,
        ScrapyardService $scrapyardService,
        private readonly ContractRepository $contractRepo,
        private readonly SalvageRightsParser $salvageRightsParser,
    ) {
        parent::__construct($em);
        $this->salvageCalc = $salvageCalc;
        $this->scrapyardService = $scrapyardService;
    }
    #[Route('/', name: 'app_salvaged_mech_index', methods: ['GET'])]
    public function index(SalvagedMechService $salvagedMechService, DropshipService $dropshipService, SalvageCalculationService $salvageCalc): Response
    {
        $company = $this->getUser()->getCompany();
        $mechanList = $salvagedMechService->getAllMechs($company);

        // Compute per-mech cost breakdown: base salvage value, salvage rights %, and final acquisition cost
        $basePrices = [];
        $salvageRightsPcts = [];
        $acquisitionCosts = [];
        foreach ($mechanList as $mechan) {
            $bvCost = $mechan->getBvCost() ?? 0;

            // Base salvage value: floor(bvCost / 2) — same for scrapyard and standard
            $basePrice = $salvageCalc->calculateSalvageValue($bvCost) ?? 0;
            $basePrices[$mechan->getId()] = $basePrice;

            // Salvage rights percentage from contract (null = Exchange, 0 = None, >0 = percentage)
            $salvageRightsPct = $mechan->getSalvageRightsPercent();
            $salvageRightsPcts[$mechan->getId()] = $salvageRightsPct;

            // Acquisition cost: base price adjusted by salvage rights, plus repair cost
            $adjustedCost = $basePrice;
            if ($salvageRightsPct !== null && $salvageRightsPct > 0) {
                $adjustedCost = $salvageCalc->calculateAcquisitionCost($basePrice, $salvageRightsPct) ?? $basePrice;
            }
            $adjustedCost += $mechan->getRepairCost() ?? 0;
            $acquisitionCosts[$mechan->getId()] = $adjustedCost;
        }

        return $this->render('salvaged_mech/index.html.twig', [
            'salvaged_mechs' => $mechanList,
            'company' => $company,
            'dropshipService' => $dropshipService,
            'basePrices' => $basePrices,
            'salvageRightsPcts' => $salvageRightsPcts,
            'acquisitionCosts' => $acquisitionCosts,
        ]);
    }

    #[Route('/new', name: 'app_salvaged_mech_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SalvagedMechService $salvagedMechService): Response
    {
        $company = $this->getUser()->getCompany();

        // Roll for a scrapyard mech using the scrapyard tables
        $mechan = $this->scrapyardService->rollScrapyardMech();
        $salvagedMechService->createMech($mechan, $company);

        $this->addFlash('success', sprintf(
            'Scrapyard roll: Found a %s (%s BV, %s) with %s condition. Repair cost: %s SP.',
            $mechan->getModel(),
            $mechan->getBvCost(),
            $mechan->getTonnage(),
            $mechan->getDamageState()?->value ?? 'unknown',
            $mechan->getRepairCost() ?? 0
        ));

        return $this->redirectToRoute('app_salvaged_mech_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/new-with-check', name: 'app_salvaged_mech_new_with_check', methods: ['GET', 'POST'])]
    public function newWithCheck(
        Request $request,
        SalvagedMechService $salvagedMechService,
        SalvageCalculationService $salvageCalc
    ): Response {
        $mechan = new SalvagedMech();
        $form = $this->createForm(BattlefieldSalvageMechType::class, $mechan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mechan->setScrapyard(false);

            // Calculate and set repair cost (defaults to IS tech base)
            $repairCost = $salvageCalc->calculateRepairCost(
                $mechan->getTonnage(),
                $mechan->getDamageState(),
                $mechan->getTechBase()
            );
            $mechan->setRepairCost($repairCost);

            $company = $this->getUser()->getCompany();

            // Create mech with contract attachment (non-scrapyard path)
            $result = $salvagedMechService->createMech(
                $mechan,
                $company,
                true,
                $mechan->getTechBase()?->value,
                $mechan->getDamageState()?->value,
            );

            // Build success message with contract info if attached
            $message = sprintf(
                'Battlefield salvage: %s (%s BV, %st) with %s condition. Repair cost: %s SP.',
                $mechan->getModel(),
                $mechan->getBvCost(),
                $mechan->getTonnage(),
                $mechan->getDamageState()?->value ?? 'unknown',
                $mechan->getRepairCost() ?? 0
            );

            if ($result !== null) {
                $salvageRightsPercent = $result['salvageRightsPercent'];
                $adjustedCost = $result['adjustedCost'];
                $message .= sprintf(
                    ' Contract attached (salvage rights %s%%). Adjusted acquisition cost: %s SP.',
                    $salvageRightsPercent,
                    $adjustedCost
                );
            } else {
                $message .= ' No active contract found or salvage terms do not allow attachment.';
            }

            $this->addFlash('success', $message);

            return $this->render('salvaged_mech/new_with_check_result.html.twig', [
                'salvaged_mech' => $mechan,
                'form' => $form,
                'contractResult' => $result ?? null,
            ]);
        }

        return $this->render('salvaged_mech/new_with_check.html.twig', [
            'salvaged_mech' => $mechan,
            'form' => $form,
        ]);
    }

    /**
     * Calculate total acquisition cost including repair cost.
     */
    private function calculateTotalAcquisitionCost(SalvagedMech $mechan): int
    {
        // Both scrapyard and non-scrapyard use floor(bvCost / 2) for base cost
        $cost = $this->salvageCalc->calculateSalvageValue($mechan->getBvCost());

        // Apply salvage rights discount from contract if present
        $salvageRightsPercent = $mechan->getSalvageRightsPercent();
        if ($salvageRightsPercent !== null && $salvageRightsPercent > 0) {
            $cost = $this->salvageCalc->calculateAcquisitionCost($cost, $salvageRightsPercent) ?? $cost;
        }

        // Add repair cost
        $repairCost = $mechan->getRepairCost() ?? 0;
        $cost = $cost + $repairCost;

        return $cost;
    }

    #[Route('/{id}', name: 'app_salvaged_mech_show', methods: ['GET'])]
    public function show(SalvagedMech $salvagedMech): Response
    {
        $isScrapyard = $salvagedMech->isScrapyard();
        $acquisitionCost = $this->calculateTotalAcquisitionCost($salvagedMech);
        $scrapyardNote = $isScrapyard ? ' (Scrapyard: half BV, stays Crippled)' : '';

        return $this->render('salvaged_mech/show.html.twig', [
            'salvaged_mech' => $salvagedMech,
            'acquisitionCost' => $acquisitionCost,
            'scrapyardNote' => $scrapyardNote,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_salvaged_mech_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SalvagedMech $salvagedMech, SalvagedMechService $salvagedMechService): Response
    {
        $form = $salvagedMech->isScrapyard()
            ? $this->createForm(ScrapyardMechType::class, $salvagedMech)
            : $this->createForm(BattlefieldSalvageMechType::class, $salvagedMech);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $salvagedMechService->updateMech($salvagedMech);
            return $this->redirectToRoute('app_salvaged_mech_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            $salvagedMech->isScrapyard()
                ? 'salvaged_mech/scrapyard_edit.html.twig'
                : 'salvaged_mech/battlefield_edit.html.twig',
            ['salvaged_mech' => $salvagedMech, 'form' => $form]
        );
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

    #[Route('/{id}/take-sp', name: 'app_salvaged_mech_take_sp', methods: ['POST'])]
    public function takeSp(
        int $id,
        Request $request,
        SalvagedMechService $salvagedMechService,
        SalvageCalculationService $salvageCalc
    ): Response {
        $company = $this->getUser()->getCompany();

        if (!$company) {
            $this->addFlash('error', 'Cannot take SP: No associated company found.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        $mechan = $salvagedMechService->getMech($id);

        if (!$mechan) {
            throw $this->createNotFoundException('Salvaged Mech not found.');
        }

        // Check if acquisition is NOT allowed (Exchange or Exchange/XX%)
        if ($salvageCalc->isAcquisitionAllowed($mechan->getSalvageRightsPercent())) {
            $this->addFlash('error', 'Cannot take SP: This mech allows acquisition, use Acquire Mech instead.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        $spPayout = $salvageCalc->calculateSpPayout($this->salvageCalc->calculateSalvageValue($mechan->getBvCost()), $mechan->getSalvageRightsPercent());

        if ($spPayout === null || $spPayout <= 0) {
            $this->addFlash('error', 'No SP payout available for this mech.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        // Add SP to company
        $company->addSupportPoints($spPayout, "Salvage SP payout for {$mechan->getModel()}");
        $mechan->setSpTaken($spPayout);

        $this->em->persist($company);
        $salvagedMechService->updateMech($mechan);
        $this->em->flush();
        $this->addFlash('success', "Received {$spPayout} SP from salvage payout.");

        return $this->redirectToRoute('app_salvaged_mech_index');
    }

    #[Route('/{id}/sell', name: 'app_salvaged_mech_sell', methods: ['POST'])]
    public function sell(
        int $id,
        Request $request,
        SalvagedMechService $salvagedMechService,
        ContractRepository $contractRepository
    ): Response {
        $company = $this->getUser()->getCompany();

        if (!$company) {
            $this->addFlash('error', 'Cannot sell mech: No associated company found.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        $mechan = $salvagedMechService->getMech($id);

        if (!$mechan) {
            throw $this->createNotFoundException('Salvaged Mech not found.');
        }

        // Check if the mech is already acquired, sold, or taken SP
        if ($mechan->getContractId() !== null) {
            $this->addFlash('error', 'Cannot sell: This mech has already been acquired.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        if ($mechan->getSpTaken() !== null) {
            $this->addFlash('error', 'Cannot sell: SP has already been taken for this mech.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        // Get the current active contract
        $activeContract = $this->contractRepo->findActiveContractByCompany($company);

        if ($activeContract === null) {
            $this->addFlash('error', 'Cannot sell: You have no active contract. Sell requires an active contract with salvage rights.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        // Parse the salvage rights from the active contract
        $salvageRightsPercent = $this->salvageRightsParser->parse($activeContract->getSalvageRights());

        // null means "Exchange" — acquisition and selling are both prohibited
        if ($salvageRightsPercent === null) {
            $this->addFlash('error', 'Cannot sell: Your active contract has "Exchange" salvage terms, which prohibit selling. Use "Take SP" to receive a 25% payout instead.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        // 0 means "None" — no salvage rights, cannot sell
        if ($salvageRightsPercent === 0) {
            $this->addFlash('error', 'Cannot sell: Your active contract has no salvage rights ("None").');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        // Calculate selling price: floor(bvCost / 2) * (salvageRightsPercent / 100)
        $salvageValue = $this->salvageCalc->calculateSalvageValue($mechan->getBvCost());

        if ($salvageValue === null || $salvageValue <= 0) {
            $this->addFlash('error', 'Cannot sell: This mech has no valid BV cost for selling.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        $sellingPrice = $this->salvageCalc->calculateSpPayout($salvageValue, $salvageRightsPercent);

        if ($sellingPrice <= 0) {
            $this->addFlash('error', 'Cannot sell: The calculated selling price is zero.');
            return $this->redirectToRoute('app_salvaged_mech_index');
        }

        // Apply the sale: add SP to company, update mech
        $company->addSupportPoints($sellingPrice, "Salvage selling price for {$mechan->getModel()}");
        $this->em->remove($mechan);
        $this->em->flush();

        $this->addFlash('success', "Sold {$mechan->getModel()} for {$sellingPrice} SP.");

        return $this->redirectToRoute('app_salvaged_mech_index');
    }
}
