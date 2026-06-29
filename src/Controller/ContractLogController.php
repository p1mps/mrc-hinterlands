<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\SupportPointEntry;
use App\Entity\TrackRecord;
use App\Enum\ContractLogEntryType;
use App\Enum\ContractStatus;
use App\Enum\TrackStatus;
use App\DataTables\ContractTrackTable;
use App\DataTables\TerrainTable;
use App\Form\ContractLogEntryEditFormType;
use App\Form\PostTrackFormType;
use App\Service\ContractGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contracts/{id}/log')]
class ContractLogController extends AbstractController
{
    public function __construct(
        private readonly ContractGeneratorService $generator,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/entry/{entryId}/edit', name: 'app_contracts_log_edit', methods: ['GET', 'POST'])]
    public function editEntry(Contract $contract, int $entryId, Request $request): Response
    {
        $entry = $this->em->getRepository(ContractLogEntry::class)->find($entryId);

        if (!$entry || $entry->getContract() !== $contract) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ContractLogEntryEditFormType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($entry->getEntryType() === ContractLogEntryType::TrackSetup && $entry->getTrack()) {
                $this->updateTrackSetupData($entry, $request);
            }

            $this->em->flush();
            $this->addFlash('success', 'Log entry updated.');

            return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
        }

        return $this->render('contract_log/edit.html.twig', [
            'contract'     => $contract,
            'entry'        => $entry,
            'form'         => $form,
            'missionTypes' => ContractTrackTable::getAllMissionTypes(),
            'terrains'     => array_keys(TerrainTable::getAllTerrains()),
        ]);
    }

    #[Route('/entry/{entryId}/delete', name: 'app_contracts_log_delete', methods: ['POST'])]
    public function delete(Contract $contract, int $entryId): Response
    {
        $entry = $this->em->getRepository(ContractLogEntry::class)->find($entryId);

        if ($entry && $entry->getContract() === $contract) {
            if ($entry->getEntryType() === ContractLogEntryType::PostTrack) {
                $this->revertPostTrack($contract);
            }

            if ($entry->getSupportPointEntry()) {
                $this->em->remove($entry->getSupportPointEntry());
            }

            $this->em->remove($entry);
            $this->em->flush();
            $this->addFlash('success', 'Log entry deleted.');
        }

        return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
    }

    #[Route('/add', name: 'app_contracts_log_add', methods: ['GET', 'POST'])]
    public function add(Contract $contract, Request $request): Response
    {
        $company = $contract->getCompany();
        $action = $request->request->get('action');
        $postTrackForm = $this->createForm(PostTrackFormType::class);
        $currentMonth = $this->calculateCurrentMonth($contract, $action);

        if ($request->isMethod('POST')) {
            match ($action) {
                'transport'   => $this->handleTransport($contract, $company, $request->request->getInt('jumps', 0)),
                'maintenance' => $this->handleMaintenance($contract, $company, $currentMonth),
                'base_pay'    => $this->handleBasePay($contract, $company, $currentMonth),
                'track_setup' => $this->handleTrackSetup($contract, $request->request->getInt('month'), $request->request->getBoolean('toftt')),
                'post_track'  => $this->handlePostTrackForm($contract, $company, $postTrackForm, $request),
                'downtime'    => $this->handleDowntime($contract, $company, $request->request->getInt('month'), $request->request->getString('note', ''), $request->request->getInt('amount', 0)),
                default       => null,
            };

            // If it's a post_track that failed validation, don't redirect so we can show errors
            if ($action !== 'post_track' || ($postTrackForm->isSubmitted() && $postTrackForm->isValid())) {
                return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
            }
        }

        return $this->render('contract_log/add.html.twig', [
            'contract'      => $contract,
            'currentMonth'  => $currentMonth,
            'postTrackForm' => $postTrackForm,
        ]);
    }

    // --- Private Helper Methods ---

    private function calculateCurrentMonth(Contract $contract, ?string $action): int
    {
        $lastMaintenance = $this->em->getRepository(ContractLogEntry::class)->findOneBy(
            ['contract' => $contract, 'entryType' => ContractLogEntryType::Maintenance],
            ['createdAt' => 'DESC']
        );

        if (!$lastMaintenance) {
            return 1;
        }

        return $action === 'maintenance' ? $lastMaintenance->getMonth() + 1 : $lastMaintenance->getMonth();
    }

    private function updateTrackSetupData(ContractLogEntry $entry, Request $request): void
    {
        $track = $entry->getTrack();
        $missionType = $request->request->get('missionType', $track->getMissionType());
        $terrain = $request->request->get('terrain', $track->getTerrain());
        $terrainSetting = TerrainTable::getSettingByTerrain($terrain);
        $tofttLabel = $track->isTakingOneForTeam() ? ' [TOFTT]' : '';

        $track->setMissionType($missionType);
        $track->setTerrain($terrain);

        $entry->setDescription("Track {$track->getTrackNumber()}: {$missionType} on {$terrain} (MegaMek: {$terrainSetting}){$tofttLabel}");

        $data = $entry->getData() ?? [];
        $data['missionType'] = $missionType;
        $data['terrain'] = $terrain;
        $data['terrainSetting'] = $terrainSetting;
        $entry->setData($data);
    }

    private function revertPostTrack(Contract $contract): void
    {
        if ($contract->getTracksCompleted() > 0) {
            $contract->setTracksCompleted($contract->getTracksCompleted() - 1);
        }

        $contract->setStatus(ContractStatus::Active);

        // Doctrine Collections allow us to filter easily without raw foreach loops
        $completedTracks = $contract->getTrackRecords()->filter(
            fn(TrackRecord $t) => $t->getStatus() === TrackStatus::Completed
        );

        if (!$completedTracks->isEmpty()) {
            $lastCompletedTrack = $completedTracks->last();
            $lastCompletedTrack->setStatus(TrackStatus::Pending);
            $lastCompletedTrack->setCompletedAt(null);
            $lastCompletedTrack->setCombatPayTier(null);
        }
    }

    private function handleTransport(Contract $contract, $company, int $jumps): void
    {
        $full = 50 + (50 * $jumps);
        $pct = $contract->parseTransportPercent();
        $employerShare = (int) round($full * $pct / 100);
        $playerPays = $full - $employerShare;

        $sp = (new SupportPointEntry())
            ->setCompany($company)
            ->setAmount(-$playerPays)
            ->setDescription("Transport ({$jumps} jumps)");

        $this->em->persist($sp);

        $pctNote = $pct > 0 ? " — employer covers {$pct}% (+{$employerShare} SP)" : '';

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth($contract->getTracksCompleted() + 1)
            ->setEntryType(ContractLogEntryType::Transport)
            ->setDescription("Transport: 50 + (50 × {$jumps}) = {$full} SP total{$pctNote} → player pays -{$playerPays} SP")
            ->setSupportPointEntry($sp);

        $this->em->persist($log);
        $this->em->flush();
        $this->addFlash('success', 'Transport recorded.');
    }

    private function handleMaintenance(Contract $contract, $company, int $month): void
    {
        $amount = -500 * $contract->getScale();

        $sp = (new SupportPointEntry())
            ->setCompany($company)
            ->setAmount($amount)
            ->setDescription("Contract maintenance (scale {$contract->getScale()})");

        $this->em->persist($sp);

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::Maintenance)
            ->setDescription("Maintenance deducted: $amount SP")
            ->setSupportPointEntry($sp);

        $this->em->persist($log);
        $this->em->flush();
        $this->addFlash('success', 'Maintenance recorded.');
    }

    private function handleBasePay(Contract $contract, $company, int $month): void
    {
        $amount = $contract->calculateMonthlyBasePay();

        $sp = (new SupportPointEntry())
            ->setCompany($company)
            ->setAmount($amount)
            ->setDescription("Base pay (scale {$contract->getScale()})");

        $this->em->persist($sp);

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::BasePay)
            ->setDescription("Base pay received: +$amount SP")
            ->setSupportPointEntry($sp);

        $this->em->persist($log);
        $this->em->flush();
        $this->addFlash('success', 'Base pay recorded.');
    }

    private function handleTrackSetup(Contract $contract, int $month, bool $toftt): void
    {
        $result = $this->generator->rollTrackSetup($contract->getType(), $contract->getCommandRights());

        $track = (new TrackRecord())
            ->setContract($contract)
            ->setTrackNumber($contract->getTracksCompleted() + 1)
            ->setMissionType($result['missionType'])
            ->setTerrain($result['terrain'])
            ->setCommandComplication($result['complication'])
            ->setStatus(TrackStatus::Pending)
            ->setTakingOneForTeam($toftt);

        $this->em->persist($track);

        $tofttLabel = $toftt ? ' [TOFTT]' : '';

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setTrack($track)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::TrackSetup)
            ->setDescription("Track {$track->getTrackNumber()}: {$result['missionType']} on {$result['terrain']} (MegaMek: {$result['terrainSetting']}){$tofttLabel}")
            ->setData($result);

        $this->em->persist($log);
        $this->em->flush();
        $this->addFlash('success', 'Track setup rolled and recorded.');
    }

    private function handlePostTrackForm(Contract $contract, $company, FormInterface $form, Request $request): void
    {
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return;
        }

        $data = $form->getData();
        $tier = $data['combatPayTier'];
        $combatPay = $contract->calculateMonthlyCombatPay($tier);
        $month = $request->request->getInt('month');

        // Find the first pending track
        $pendingTrack = $contract->getTrackRecords()->filter(
            fn(TrackRecord $t) => $t->getStatus() === TrackStatus::Pending
        )->first() ?: null;

        $toftt = $pendingTrack?->isTakingOneForTeam() ?? false;
        if ($toftt) {
            $combatPay = (int) floor($combatPay / 2);
        }

        $sp = null;
        if ($combatPay > 0) {
            if ($toftt) {
                $combatPay = floor($combatPay/4);
            }
            $sp = (new SupportPointEntry())
                ->setCompany($company)
                ->setAmount($combatPay)
                ->setDescription("Combat pay ({$tier->value})");
            $this->em->persist($sp);
        }

        if ($pendingTrack) {
            $pendingTrack->setStatus(TrackStatus::Completed);
            $pendingTrack->setCompletedAt(new \DateTimeImmutable());
            $pendingTrack->setCombatPayTier($tier);
        }

        $contract->setTracksCompleted($contract->getTracksCompleted() + 1);
        if ($contract->getTracksCompleted() >= $contract->getNumberOfTracks()) {
            $contract->setStatus(ContractStatus::Completed);
        }

        $salvageNote = $data['salvageClaimed'] ? 'Salvage claimed.' : 'No salvage.';
        $tofttNote = $toftt ? ' (TOFTT — half pay)' : '';

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::PostTrack)
            ->setDescription("Combat pay: " . ($combatPay > 0 ? "+$combatPay SP" : "none") . " ({$tier->value}){$tofttNote}. $salvageNote");

        if ($sp) {
            $log->setSupportPointEntry($sp);
        }

        $this->em->persist($log);
        $this->em->flush();
        $this->addFlash('success', 'Post-track results recorded.');
    }

    private function handleDowntime(Contract $contract, $company, int $month, string $note, int $amount): void
    {
        $sp = null;
        if ($amount !== 0) {
            $sp = (new SupportPointEntry())
                ->setCompany($company)
                ->setAmount($amount)
                ->setDescription("Downtime — " . ($note ?: 'no note'));
            $this->em->persist($sp);
        }

        $amountNote = $amount !== 0 ? " (" . ($amount >= 0 ? "+$amount" : "$amount") . " SP)" : '';

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::Downtime)
            ->setDescription(($note ?: '(no note)') . $amountNote);

        if ($sp) {
            $log->setSupportPointEntry($sp);
        }

        $this->em->persist($log);
        $this->em->flush();
        $this->addFlash('success', 'Downtime note added.');
    }
}
