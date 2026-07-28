<?php

namespace App\Service;

use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\SupportPointEntry;
use App\Entity\TrackRecord;
use App\Enum\ContractLogEntryType;
use App\Enum\ContractStatus;
use App\Enum\TrackStatus;
use App\DataTables\ContractTrackTable;
use App\DataTables\TerrainTable;
use Doctrine\ORM\EntityManagerInterface;

class ContractLogService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContractGeneratorService $generator,
    ) {}

    public function updateTrackSetupData(ContractLogEntry $entry, string $missionType, string $terrain): void
    {
        $track = $entry->getTrack();
        $terrainSetting = TerrainTable::getSettingByTerrain($terrain);
        $tofttLabel = $track?->isTakingOneForTeam() ? ' [TOFTT]' : '';

        if ($track) {
            $track->setMissionType($missionType);
            $track->setTerrain($terrain);
        }

        $entry->setDescription("Track {$track?->getTrackNumber()}: {$missionType} on {$terrain} (MegaMek: {$terrainSetting}){$tofttLabel}");

        $data = $entry->getData() ?? [];
        $data['missionType'] = $missionType;
        $data['terrain'] = $terrain;
        $data['terrainSetting'] = $terrainSetting;
        $entry->setData($data);
    }

    public function deleteEntry(Contract $contract, ContractLogEntry $entry): void
    {
        if ($entry->getContract() !== $contract) {
            return;
        }

        if ($entry->getEntryType() === ContractLogEntryType::PostTrack) {
            $this->revertPostTrack($contract);
        }

        if ($entry->getSupportPointEntry()) {
            $this->em->remove($entry->getSupportPointEntry());
        }

        $this->em->remove($entry);
    }

    public function calculateCurrentMonth(Contract $contract): int
    {
        $lastMaintenance = $this->em->getRepository(ContractLogEntry::class)->findOneBy(
            ['contract' => $contract, 'entryType' => ContractLogEntryType::Maintenance],
            ['createdAt' => 'DESC']
        );

        if (!$lastMaintenance) {
            return 1;
        }

        return $lastMaintenance->getMonth() + 1;
    }

    public function handleTransport(Contract $contract, $company, int $jumps): void
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
    }

    public function handleMaintenance(Contract $contract, $company, int $month): void
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
    }

    public function handleBasePay(Contract $contract, $company, int $month): void
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
    }

    public function handleTrackSetup(Contract $contract, int $month, bool $toftt): void
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
    }

    public function handlePostTrack(Contract $contract, $company, object $form, array $formData, int $month): void
    {
        $tier = $formData['combatPayTier'];
        $combatPay = $contract->calculateMonthlyCombatPay($tier);
        $salvageClaimed = $formData['salvageClaimed'] ?? false;

        $pendingTrack = $contract->getTrackRecords()->filter(
            fn(TrackRecord $t) => $t->getStatus() === TrackStatus::Pending
        )->first() ?: null;

        $toftt = $pendingTrack?->isTakingOneForTeam() ?? false;
        if ($toftt) {
            $combatPay = (int) floor($combatPay / 2);
        }

        $sp = null;
        if ($combatPay > 0) {
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

        $salvageNote = $salvageClaimed ? 'Salvage claimed.' : 'No salvage.';
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
    }

    public function handleDowntime(Contract $contract, $company, int $month, string $note, int $amount): void
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
    }

    private function revertPostTrack(Contract $contract): void
    {
        if ($contract->getTracksCompleted() > 0) {
            $contract->setTracksCompleted($contract->getTracksCompleted() - 1);
        }

        $contract->setStatus(ContractStatus::Active);

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
}
