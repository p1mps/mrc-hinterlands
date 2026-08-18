<?php

namespace App\Command;

use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\TrackRecord;
use App\Enum\ContractLogEntryType;
use App\Enum\TrackStatus;
use App\Service\ContractGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:roll-track',
    description: 'Roll a track (mission type + terrain + complication) for an existing contract.',
    aliases: ['app:roll']
)]
class RollTrackCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContractGeneratorService $contractGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('contract_id', InputArgument::REQUIRED, 'The contract ID to roll a track for');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $contractId = (int) $input->getArgument('contract_id');

        $contract = $this->em->getRepository(Contract::class)->find($contractId);

        if (!$contract) {
            $io->error("No contract found with ID {$contractId}.");
            return Command::FAILURE;
        }

        // Determine next track number
        $trackRecords = $contract->getTrackRecords();
        $nextTrackNumber = $trackRecords->count() + 1;

        // Roll the track
        $rollResult = $this->contractGenerator->rollTrackSetup(
            $contract->getType(),
            $contract->getCommandRights()
        );

        // Create TrackRecord
        $track = new TrackRecord();
        $track->setContract($contract);
        $track->setTrackNumber($nextTrackNumber);
        $track->setMissionType($rollResult['missionType']);
        $track->setTerrain($rollResult['terrain']);
        $track->setCommandComplication($rollResult['complication']);
        $track->setStatus(TrackStatus::Pending);

        $this->em->persist($track);

        // Create ContractLogEntry
        $logEntry = new ContractLogEntry();
        $logEntry->setContract($contract);
        $logEntry->setTrack($track);
        $logEntry->setMonth($contract->getTracksCompleted() + 1);
        $logEntry->setEntryType(ContractLogEntryType::TrackSetup);
        $logEntry->setRollResult($rollResult['terrainRoll']);
        $logEntry->setData([
            'missionType'      => $rollResult['missionType'],
            'missionRoll'      => $rollResult['missionRoll'],
            'terrain'          => $rollResult['terrain'],
            'terrainSetting'   => $rollResult['terrainSetting'],
            'terrainRoll'      => $rollResult['terrainRoll'],
            'complication'     => $rollResult['complication'],
            'complicationRoll' => $rollResult['complicationRoll'],
        ]);
        $logEntry->setDescription(
            "Track #{$nextTrackNumber}: {$rollResult['missionType']} on {$rollResult['terrain']} ({$rollResult['terrainSetting']})"
        );

        $this->em->persist($logEntry);
        $this->em->flush();

        // Output results
        $io->section("Track #{$nextTrackNumber} Rolled");
        $io->table(
            ['Metric', 'Value'],
            [
                ['Mission Type', $rollResult['missionType']],
                ['Mission Roll', $rollResult['missionRoll']],
                ['Terrain', $rollResult['terrain']],
                ['Terrain Setting', $rollResult['terrainSetting']],
                ['Terrain Roll (2d6)', $rollResult['terrainRoll']],
                ['Complication', $rollResult['complication']],
                ['Complication Roll (1d6 + bonus)', $rollResult['complicationRoll']],
            ]
        );

        $io->success("Track #{$nextTrackNumber} saved (ID: {$track->getId()})");

        return Command::SUCCESS;
    }
}
