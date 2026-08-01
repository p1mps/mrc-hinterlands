<?php

namespace App\Service;

use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\SupportPointEntry;
use App\Entity\TrackRecord;
use App\Enum\CombatPayTier;
use App\Enum\ContractLogEntryType;
use App\Enum\ContractStatus;
use App\Enum\TrackStatus;
use Doctrine\ORM\EntityManagerInterface;

class ContractService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContractGeneratorService $generator,
    ) {}

    public function createContract(Contract|array $data): Contract
    {
        $contract = (new Contract());

        if ($data instanceof Contract) {
            $contract
                ->setType($data->getType())
                ->setEmployer($data->getEmployer())
                ->setEmployerAffiliation($data->getEmployerAffiliation())
                ->setScale($data->getScale())
                ->setDurationMonths($data->getDurationMonths())
                ->setBasePayPercent($data->getBasePayPercent())
                ->setCommandRights($data->getCommandRights())
                ->setSupportTerms($data->getSupportTerms())
                ->setSalvageRights($data->getSalvageRights())
                ->setTransportTerms($data->getTransportTerms())
                ->setNumberOfTracks($data->getNumberOfTracks())
                ->setIsOpposing($data->isOpposing());

            if ($data->getCompany()) {
                $contract->setCompany($data->getCompany());
            }

            if ($data->getOpposingCompany()) {
                $contract->setOpposingCompany($data->getOpposingCompany());
            }

            if ($data->getLinkedContract()) {
                $contract->setLinkedContract($data->getLinkedContract());
            }
        } else {
            $contract
                ->setType($data['type'])
                ->setEmployer($data['employer'])
                ->setEmployerAffiliation($data['employerAffiliation'] ?? '')
                ->setScale($data['scale'])
                ->setDurationMonths($data['durationMonths'] ?? 0)
                ->setBasePayPercent($data['basePayPercent'] ?? null)
                ->setCommandRights($data['commandRights'])
                ->setSupportTerms($data['supportTerms'])
                ->setSalvageRights($data['salvageRights'])
                ->setTransportTerms($data['transportTerms'])
                ->setNumberOfTracks($data['numberOfTracks'])
                ->setIsOpposing($data['isOpposing'] ?? false);

            if ($data['company'] ?? null) {
                $contract->setCompany($data['company']);
            }

            if ($data['opposingCompany'] ?? null) {
                $contract->setOpposingCompany($data['opposingCompany']);
            }

            if ($data['linkedContract'] ?? null) {
                $contract->setLinkedContract($data['linkedContract']);
            }
        }

        return $contract;
    }

    public function acceptContract(Contract $contract): void
    {
        $contract->setStatus(ContractStatus::Accepted);
        $contract->setAcceptedAt(new \DateTimeImmutable());
    }

    public function handleTrackSetup(Contract $contract, int $month): void
    {
        $result = $this->generator->rollTrackSetup($contract->getType(), $contract->getCommandRights());

        $track = (new TrackRecord())
            ->setContract($contract)
            ->setTrackNumber($contract->getTracksCompleted() + 1)
            ->setMissionType($result['missionType'])
            ->setTerrain($result['terrain'])
            ->setCommandComplication($result['complication'])
            ->setStatus(TrackStatus::Pending)
            ->setTakingOneForTeam(false);

        $this->em->persist($track);

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setTrack($track)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::TrackSetup)
            ->setDescription("Track {$track->getTrackNumber()}: {$result['missionType']} on {$result['terrain']} (MegaMek: {$result['terrainSetting']})")
            ->setData($result);

        $this->em->persist($log);
        $this->em->flush();
    }

    public function handlePostTrack(Contract $contract, array $formData, int $month): void
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
                ->setCompany($contract->getCompany())
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

    public function handleDowntime(Contract $contract, array $formData, int $month): void
    {
        $amount = $formData['amount'];
        $note = $formData['note'] ?? '';

        $sp = null;
        if ($amount !== 0) {
            $sp = (new SupportPointEntry())
                ->setCompany($contract->getCompany())
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

    public function handleSalvage(Contract $contract, array $formData, int $month): void
    {
        $amount = $formData['amount'];
        $note = $formData['note'] ?? '';

        $sp = null;
        if ($amount !== 0) {
            $sp = (new SupportPointEntry())
                ->setCompany($contract->getCompany())
                ->setAmount($amount)
                ->setDescription("Salvage — " . ($note ?: 'no note'));
            $this->em->persist($sp);
        }

        $amountNote = $amount !== 0 ? " (" . ($amount >= 0 ? "+$amount" : "$amount") . " SP)" : '';

        $log = (new ContractLogEntry())
            ->setContract($contract)
            ->setMonth($month)
            ->setEntryType(ContractLogEntryType::Salvage)
            ->setDescription(($note ?: '(no note)') . $amountNote);

        if ($sp) {
            $log->setSupportPointEntry($sp);
        }

        $this->em->persist($log);
        $this->em->flush();
    }
}
