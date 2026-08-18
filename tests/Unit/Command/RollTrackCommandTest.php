<?php

namespace App\Tests\Unit\Command;

use App\Command\RollTrackCommand;
use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\TrackRecord;
use App\Enum\ContractLogEntryType;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Enum\CommandRights;
use App\Enum\TrackStatus;
use App\Service\ContractGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(RollTrackCommand::class)]
class RollTrackCommandTest extends TestCase
{
    private RollTrackCommand $command;
    private EntityManagerInterface $em;
    private ContractGeneratorService $generator;
    private EntityRepository $contractRepo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->generator = $this->createMock(ContractGeneratorService::class);
        $this->contractRepo = $this->createMock(EntityRepository::class);

        $this->command = new RollTrackCommand($this->em, $this->generator);
    }

    public function testExecuteWithValidContract(): void
    {
        // Create mock contract
        $contract = $this->createMock(Contract::class);
        $contract->method('getType')->willReturn(ContractType::Expedition);
        $contract->method('getCommandRights')->willReturn(CommandRights::Integrated);
        $contract->method('getTracksCompleted')->willReturn(0);

        $trackRecords = new \Doctrine\Common\Collections\ArrayCollection();
        $contract->method('getTrackRecords')->willReturn($trackRecords);

        // Mock repository find
        $this->em->method('getRepository')
            ->with(Contract::class)
            ->willReturn($this->contractRepo);
        $this->contractRepo->method('find')
            ->with(1)
            ->willReturn($contract);

        // Mock rollTrackSetup result
        $rollResult = [
            'missionType' => 'Reconnaissance',
            'missionRoll' => 3,
            'terrain' => 'Mountainous',
            'terrainSetting' => 'Highlands',
            'terrainRoll' => 7,
            'complication' => 'Enemy patrol spotted',
            'complicationRoll' => 5,
        ];
        $this->generator->method('rollTrackSetup')
            ->with(ContractType::Expedition, CommandRights::Integrated)
            ->willReturn($rollResult);

        // Track the persist/flush calls
        $persistedTrack = null;
        $persistedLogEntry = null;
        $this->em->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedTrack, &$persistedLogEntry) {
                if ($entity instanceof TrackRecord) {
                    $persistedTrack = $entity;
                }
                if ($entity instanceof ContractLogEntry) {
                    $persistedLogEntry = $entity;
                }
            });
        $this->em->method('flush');

        // Execute command
        $input = new ArrayInput(['contract_id' => '1']);
        $output = new BufferedOutput();
        $result = $this->command->run($input, $output);

        // Assert
        self::assertSame(Command::SUCCESS, $result);
        self::assertNotNull($persistedTrack);
        self::assertNotNull($persistedLogEntry);

        // Verify TrackRecord
        self::assertSame(1, $persistedTrack->getTrackNumber());
        self::assertSame('Reconnaissance', $persistedTrack->getMissionType());
        self::assertSame('Mountainous', $persistedTrack->getTerrain());
        self::assertSame('Enemy patrol spotted', $persistedTrack->getCommandComplication());
        self::assertSame(TrackStatus::Pending, $persistedTrack->getStatus());

        // Verify ContractLogEntry
        self::assertSame(ContractLogEntryType::TrackSetup, $persistedLogEntry->getEntryType());
        self::assertSame($persistedTrack, $persistedLogEntry->getTrack());
        self::assertSame(1, $persistedLogEntry->getMonth());
        self::assertSame([
            'missionType' => 'Reconnaissance',
            'missionRoll' => 3,
            'terrain' => 'Mountainous',
            'terrainSetting' => 'Highlands',
            'terrainRoll' => 7,
            'complication' => 'Enemy patrol spotted',
            'complicationRoll' => 5,
        ], $persistedLogEntry->getData());

        // Verify output contains complication
        $outputContent = $output->fetch();
        self::assertStringContainsString('Enemy patrol spotted', $outputContent);
        self::assertStringContainsString('Track #1', $outputContent);
    }

    public function testExecuteWithNonExistentContract(): void
    {
        $this->contractRepo->method('find')->with(999)->willReturn(null);
        $this->em->method('getRepository')
            ->with(Contract::class)
            ->willReturn($this->contractRepo);

        $input = new ArrayInput(['contract_id' => '999']);
        $output = new BufferedOutput();
        $result = $this->command->run($input, $output);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('No contract found with ID 999', $output->fetch());
    }

    public static function provideCommandRights(): array
    {
        return [
            'Integrated' => [CommandRights::Integrated, 3],
            'House' => [CommandRights::House, 2],
            'Liaison' => [CommandRights::Liaison, 1],
            'Independent' => [CommandRights::Independent, 0],
        ];
    }

    #[DataProvider('provideCommandRights')]
    public function testRollTrackSetupCallsGeneratorWithCorrectArgs(CommandRights $rights, int $expectedBonus): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->method('getType')->willReturn(ContractType::Raid);
        $contract->method('getCommandRights')->willReturn($rights);
        $contract->method('getTracksCompleted')->willReturn(2);

        $trackRecords = new \Doctrine\Common\Collections\ArrayCollection();
        $contract->method('getTrackRecords')->willReturn($trackRecords);

        $this->contractRepo->method('find')->willReturn($contract);
        $this->em->method('getRepository')
            ->with(Contract::class)
            ->willReturn($this->contractRepo);

        $this->generator->expects(self::once())
            ->method('rollTrackSetup')
            ->with(ContractType::Raid, $rights)
            ->willReturn([
                'missionType' => 'Reconnaissance',
                'missionRoll' => 3,
                'terrain' => 'Mountainous',
                'terrainSetting' => 'Highlands',
                'terrainRoll' => 7,
                'complication' => 'Enemy patrol spotted',
                'complicationRoll' => 5,
            ]);

        $input = new ArrayInput(['contract_id' => '42']);
        $output = new BufferedOutput();
        $this->command->run($input, $output);
    }
}
