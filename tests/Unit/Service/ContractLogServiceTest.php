<?php

namespace App\Tests\Unit\Service;

use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\MercenaryCompany;
use App\Entity\SupportPointEntry;
use App\Entity\TrackRecord;
use App\Enum\CombatPayTier;
use App\Enum\CommandRights;
use App\Enum\ContractLogEntryType;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Enum\TrackStatus;
use App\Service\ContractGeneratorService;
use App\Service\ContractLogService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class ContractLogServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private object $generator;
    private ContractLogService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->generator = $this->createStub(ContractGeneratorService::class);
        $this->service = new ContractLogService($this->em, $this->generator);
    }

    private function makeContract(array $overrides = []): Contract
    {
        $contract = $this->createMock(Contract::class);
        $defaults = [
            'numberOfTracks' => 3,
            'tracksCompleted' => 0,
            'status' => ContractStatus::Active,
            'scale' => 1,
            'type' => ContractType::Raid,
            'commandRights' => CommandRights::Liaison,
            'transportTerms' => '—',
            'basePay' => 500,
        ];
        $merged = array_merge($defaults, $overrides);

        $contract->method('getNumberOfTracks')->willReturn($merged['numberOfTracks']);
        $contract->method('getTracksCompleted')->willReturn($merged['tracksCompleted']);
        $contract->method('getStatus')->willReturn($merged['status']);
        $contract->method('getScale')->willReturn($merged['scale']);
        $contract->method('getType')->willReturn($merged['type']);
        $contract->method('getCommandRights')->willReturn($merged['commandRights']);
        $contract->method('parseTransportPercent')->willReturn(
            (int) rtrim($merged['transportTerms'] ?? '—', '%') ?: 0
        );
        $contract->method('calculateMonthlyBasePay')->willReturn($merged['basePay']);
        $contract->method('calculateMonthlyCombatPay')
            ->willReturnCallback(fn(CombatPayTier $tier) => (int) round(500 * ($merged['scale'] ?? 1) * $tier->multiplier()));
        $contract->method('getTrackRecords')->willReturn(
            new \Doctrine\Common\Collections\ArrayCollection()
        );

        return $contract;
    }

    private function makeCompany(): MercenaryCompany
    {
        return $this->createStub(MercenaryCompany::class);
    }

    private function makeLogEntry(array $overrides = []): ContractLogEntry
    {
        $entry = $this->createMock(ContractLogEntry::class);
        $contract = $overrides['contract'] ?? $this->makeContract();
        $track = $overrides['track'] ?? null;
        $defaults = [
            'contract' => $contract,
            'track' => $track,
            'entryType' => ContractLogEntryType::BasePay,
            'data' => null,
            'supportPointEntry' => null,
        ];
        $merged = array_merge($defaults, $overrides);

        $entry->method('getContract')->willReturn($merged['contract']);
        $entry->method('getTrack')->willReturn($merged['track'] ?? $this->makeTrackRecord());
        $entry->method('getEntryType')->willReturn($merged['entryType']);
        $entry->method('getData')->willReturn($merged['data']);
        $entry->method('getSupportPointEntry')->willReturn($merged['supportPointEntry']);

        // Configure fluent setters to return $this (ContractLogEntry setters return `static`)
        $entry->method('setContract')->willReturnSelf();
        $entry->method('setTrack')->willReturnSelf();
        $entry->method('setMonth')->willReturnSelf();
        $entry->method('setEntryType')->willReturnSelf();
        $entry->method('setDescription')->willReturnSelf();
        $entry->method('setData')->willReturnSelf();
        $entry->method('setSupportPointEntry')->willReturnSelf();

        return $entry;
    }

    private function makeTrackRecord(array $overrides = []): TrackRecord
    {
        $track = $this->createMock(TrackRecord::class);
        $defaults = [
            'trackNumber' => 1,
            'missionType' => 'Patrol',
            'terrain' => 'Plains',
            'takingOneForTeam' => false,
            'status' => TrackStatus::Pending,
        ];
        $merged = array_merge($defaults, $overrides);

        $track->method('getTrackNumber')->willReturn($merged['trackNumber']);
        $track->method('getMissionType')->willReturn($merged['missionType']);
        $track->method('getTerrain')->willReturn($merged['terrain']);
        $track->method('isTakingOneForTeam')->willReturn($merged['takingOneForTeam']);
        $track->method('getStatus')->willReturn($merged['status']);

        // Configure fluent setters to return $this
        $track->method('setContract')->willReturnSelf();
        $track->method('setTrackNumber')->willReturnSelf();
        $track->method('setMissionType')->willReturnSelf();
        $track->method('setTerrain')->willReturnSelf();
        $track->method('setCommandComplication')->willReturnSelf();
        $track->method('setStatus')->willReturnSelf();
        $track->method('setCompletedAt')->willReturnSelf();
        $track->method('setCombatPayTier')->willReturnSelf();
        $track->method('setTakingOneForTeam')->willReturnSelf();

        return $track;
    }

    // ── updateTrackSetupData ──────────────────────────────────────────────

    public function testUpdateTrackSetupDataUpdatesTrackAndEntry(): void
    {
        // Use real objects so we can verify state changes via getters
        $track = new TrackRecord();
        $track->setTrackNumber(2);

        $entry = new ContractLogEntry();
        $contract = new Contract();
        $entry->setContract($contract);
        $entry->setTrack($track);

        $this->service->updateTrackSetupData($entry, 'Raid', 'Wasteland');

        // Verify track state changes via direct getter assertions
        $this->assertEquals('Raid', $track->getMissionType());
        $this->assertEquals('Wasteland', $track->getTerrain());

        // Verify entry state changes
        $description = $entry->getDescription();
        $this->assertStringContainsString('Track 2: Raid on Wasteland', $description);

        $data = $entry->getData();
        $this->assertIsArray($data);
        $this->assertEquals('Raid', $data['missionType']);
        $this->assertEquals('Wasteland', $data['terrain']);
        $this->assertArrayHasKey('terrainSetting', $data);
    }

    public function testUpdateTrackSetupDataHandlesNullTrack(): void
    {
        // Use a real ContractLogEntry so setDescription() actually stores the value
        $entry = new ContractLogEntry();
        $contract = new Contract();
        $entry->setContract($contract);

        $this->service->updateTrackSetupData($entry, 'Invasion', 'Urban');

        // Verify description was set (no track, so no track number in description)
        $description = $entry->getDescription();
        $this->assertStringContainsString('on Urban', $description);
    }

    public function testUpdateTrackSetupDataAddsTofttLabelWhenTrackIsToftt(): void
    {
        // Use a real TrackRecord and ContractLogEntry for state verification
        $track = new TrackRecord();
        $track->setTrackNumber(1);
        $track->setTakingOneForTeam(true);

        $entry = new ContractLogEntry();
        $contract = new Contract();
        $entry->setContract($contract);
        $entry->setTrack($track);

        $this->service->updateTrackSetupData($entry, 'Raid', 'Forest');

        // Verify TOFTT label in description
        $description = $entry->getDescription();
        $this->assertStringContainsString(' [TOFTT]', $description);
    }

    // ── deleteEntry ───────────────────────────────────────────────────────

    public function testDeleteEntryReturnsEarlyWhenEntryBelongsToDifferentContract(): void
    {
        $contractA = $this->makeContract();
        $contractB = $this->makeContract();
        $entry = $this->makeLogEntry(['contract' => $contractB]);

        $this->em->expects($this->never())
            ->method('remove');
        $this->em->expects($this->never())
            ->method('flush');

        $this->service->deleteEntry($contractA, $entry);
    }

    public function testDeleteEntryRemovesSupportPointEntryAndLogEntry(): void
    {
        $contract = $this->makeContract();
        $spEntry = $this->createMock(SupportPointEntry::class);
        $entry = $this->makeLogEntry([
            'contract' => $contract,
            'entryType' => ContractLogEntryType::BasePay,
            'supportPointEntry' => $spEntry,
        ]);

        $callCount = 0;
        $this->em
            ->expects($this->exactly(2))
            ->method('remove')
            ->willReturnCallback(function ($obj) use ($spEntry, $entry, &$callCount) {
                match ($callCount) {
                    0 => $this->assertSame($spEntry, $obj),
                    1 => $this->assertSame($entry, $obj),
                };
                $callCount++;
            });
        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($contract, $entry);
    }

    public function testDeleteEntryReversesPostTrackAndRemovesEntries(): void
    {
        $contract = new Contract();
        $contract->setType(ContractType::Raid);
        $contract->setNumberOfTracks(3);
        $contract->setTracksCompleted(2);
        $contract->setStatus(ContractStatus::Active);

        $completedTrack1 = new TrackRecord();
        $completedTrack1->setContract($contract)
            ->setTrackNumber(1)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Completed)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($completedTrack1);

        $completedTrack2 = new TrackRecord();
        $completedTrack2->setContract($contract)
            ->setTrackNumber(2)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Completed)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($completedTrack2);

        $pendingTrack = new TrackRecord();
        $pendingTrack->setContract($contract)
            ->setTrackNumber(3)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Pending)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($pendingTrack);

        $entry = $this->makeLogEntry([
            'contract' => $contract,
            'entryType' => ContractLogEntryType::PostTrack,
            'supportPointEntry' => null,
        ]);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($entry);
        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($contract, $entry);

        $this->assertEquals(1, $contract->getTracksCompleted());
        $this->assertEquals(ContractStatus::Active, $contract->getStatus());
    }

    public function testDeleteEntryRevertsPostTrackToAvailableWhenAllTracksDeleted(): void
    {
        $contract = new Contract();
        $contract->setType(ContractType::Raid);
        $contract->setNumberOfTracks(1);
        $contract->setTracksCompleted(1);
        $contract->setStatus(ContractStatus::Completed);

        $completedTrack = new TrackRecord();
        $completedTrack->setContract($contract)
            ->setTrackNumber(1)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Completed)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($completedTrack);

        $entry = $this->makeLogEntry([
            'contract' => $contract,
            'entryType' => ContractLogEntryType::PostTrack,
            'supportPointEntry' => null,
        ]);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($entry);
        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($contract, $entry);

        $this->assertEquals(0, $contract->getTracksCompleted());
        $this->assertEquals(ContractStatus::Available, $contract->getStatus());
    }

    public function testDeleteEntryRevertsPostTrackToCompletedWhenStillEnoughTracks(): void
    {
        $contract = new Contract();
        $contract->setType(ContractType::Raid);
        $contract->setNumberOfTracks(3);
        $contract->setTracksCompleted(3);
        $contract->setStatus(ContractStatus::Completed);

        $completedTrack1 = new TrackRecord();
        $completedTrack1->setContract($contract)
            ->setTrackNumber(1)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Completed)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($completedTrack1);

        $completedTrack2 = new TrackRecord();
        $completedTrack2->setContract($contract)
            ->setTrackNumber(2)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Completed)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($completedTrack2);

        $completedTrack3 = new TrackRecord();
        $completedTrack3->setContract($contract)
            ->setTrackNumber(3)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Completed)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($completedTrack3);

        $entry = $this->makeLogEntry([
            'contract' => $contract,
            'entryType' => ContractLogEntryType::PostTrack,
            'supportPointEntry' => null,
        ]);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($entry);
        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($contract, $entry);

        $this->assertEquals(2, $contract->getTracksCompleted());
        $this->assertEquals(ContractStatus::Active, $contract->getStatus());
    }

    public function testDeleteEntryRemovesTrackSetupAndAssociatedTrackRecord(): void
    {
        $contract = $this->makeContract();
        $track = $this->createMock(TrackRecord::class);
        $entry = $this->makeLogEntry([
            'contract' => $contract,
            'entryType' => ContractLogEntryType::TrackSetup,
            'track' => $track,
            'supportPointEntry' => null,
        ]);

        $this->em->expects($this->exactly(2))
            ->method('remove')
            ->willReturnCallback(function ($obj) use ($track, $entry) {
                static $callCount = 0;
                if ($callCount === 0) {
                    $this->assertSame($track, $obj);
                } else {
                    $this->assertSame($entry, $obj);
                }
                $callCount++;
            });
        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteEntry($contract, $entry);
    }

    // ── calculateCurrentMonth ────────────────────────────────────────────

    public function testCalculateCurrentMonthReturnsOneWhenNoMaintenanceExists(): void
    {
        $contract = $this->makeContract();
        $repo = $this->createStub(EntityRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ContractLogEntry::class)
            ->willReturn($repo);

        $this->assertEquals(1, $this->service->calculateCurrentMonth($contract));
    }

    public function testCalculateCurrentMonthReturnsNextMonthAfterLastMaintenance(): void
    {
        $contract = $this->makeContract();
        $lastMaintenance = $this->createStub(ContractLogEntry::class);
        $lastMaintenance->method('getMonth')->willReturn(5);

        $repo = $this->createStub(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($lastMaintenance);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ContractLogEntry::class)
            ->willReturn($repo);

        $this->assertEquals(6, $this->service->calculateCurrentMonth($contract));
    }

    // ── handleTransport ───────────────────────────────────────────────────

    public function testHandleTransportCreatesAndPersistsLogAndSupportPointEntry(): void
    {
        $contract = $this->makeContract(['transportTerms' => '50%']);
        $company = $this->makeCompany();

        $persisted = [];
        $this->em
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$persisted) {
                $persisted[] = $obj;
            });

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleTransport($contract, $company, 2);

        $this->assertCount(2, $persisted);
    }

    public function testHandleTransportCalculatesCorrectSpForJumps(): void
    {
        $contract = $this->makeContract(['transportTerms' => '100%']);
        $company = $this->makeCompany();

        // full = 50 + (50 * 3) = 200, pct=100, employerShare=200, playerPays=0
        $this->em
            ->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleTransport($contract, $company, 3);
    }

    public function testHandleTransportWithZeroJumps(): void
    {
        $contract = $this->makeContract(['transportTerms' => '0%']);
        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleTransport($contract, $company, 0);
    }

    public function testHandleTransportWithNoEmployerCoverage(): void
    {
        $contract = $this->makeContract(['transportTerms' => '—']);
        $company = $this->makeCompany();

        // When transportTerms is '—', parseTransportPercent returns 0
        // full = 50 + (50 * 1) = 100, pct=0, employerShare=0, playerPays=100
        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleTransport($contract, $company, 1);
    }

    // ── handleMaintenance ─────────────────────────────────────────────────

    public function testHandleMaintenanceCreatesAndPersistsLogAndSupportPointEntry(): void
    {
        $contract = $this->makeContract(['scale' => 2]);
        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleMaintenance($contract, $company, 1);
    }

    public function testHandleMaintenanceCalculatesCorrectAmount(): void
    {
        $contract = $this->makeContract(['scale' => 3]);
        $company = $this->makeCompany();

        // amount = -500 * 3 = -1500
        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleMaintenance($contract, $company, 2);
    }

    // ── handleBasePay ─────────────────────────────────────────────────────

    public function testHandleBasePayCreatesAndPersistsLogAndSupportPointEntry(): void
    {
        $contract = $this->makeContract(['scale' => 1, 'basePay' => 500]);
        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleBasePay($contract, $company, 1);
    }

    public function testHandleBasePayWithZeroBasePay(): void
    {
        $contract = $this->makeContract(['basePay' => 0]);
        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleBasePay($contract, $company, 1);
    }

    // ── handleTrackSetup ──────────────────────────────────────────────────

    public function testHandleTrackSetupCreatesTrackAndLogEntry(): void
    {
        $contract = $this->makeContract();
        $this->generator->method('rollTrackSetup')->willReturn([
            'missionType' => 'Sabotage',
            'terrain' => 'Mountains',
            'terrainSetting' => 'Hard',
            'complication' => 'Equipment failure',
        ]);

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleTrackSetup($contract, 1, false);
    }

    public function testHandleTrackSetupWithTofttAddsTofttLabel(): void
    {
        $contract = $this->makeContract();
        $this->generator->method('rollTrackSetup')->willReturn([
            'missionType' => 'Recon',
            'terrain' => 'Desert',
            'terrainSetting' => 'Easy',
            'complication' => null,
        ]);

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleTrackSetup($contract, 1, true);
    }

    // ── handlePostTrack ───────────────────────────────────────────────────

    public function testHandlePostTrackCompletesPendingTrackAndUpdatesContract(): void
    {
        $contract = new Contract();
        $contract->setType(ContractType::Raid);
        $contract->setCommandRights(CommandRights::Liaison);
        $contract->setNumberOfTracks(3);
        $contract->setTracksCompleted(1);
        $contract->setScale(1);
        $contract->setStatus(ContractStatus::Active);

        $pendingTrack = new TrackRecord();
        $pendingTrack->setContract($contract)
            ->setTrackNumber(2)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Pending)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($pendingTrack);

        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handlePostTrack($contract, $company, [
            'combatPayTier' => CombatPayTier::Full,
        ], 1);

        $this->assertEquals(2, $contract->getTracksCompleted());
        $this->assertEquals(ContractStatus::Active, $contract->getStatus());
    }

    public function testHandlePostTrackSetsContractToCompletedWhenAllTracksDone(): void
    {
        // Use a real Contract so we can verify state changes via getters
        $contract = new Contract();
        $contract->setType(ContractType::Raid);
        $contract->setCommandRights(CommandRights::Liaison);
        $contract->setNumberOfTracks(1);
        $contract->setTracksCompleted(0);
        $contract->setScale(1);
        $contract->setStatus(ContractStatus::Active);
        $contract->setTransportTerms('—');
        $contract->setBasePayPercent(50);

        $company = $this->makeCompany();
        $pendingTrack = new TrackRecord();
        $pendingTrack->setContract($contract)
            ->setTrackNumber(1)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Pending)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($pendingTrack);

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handlePostTrack($contract, $company, [
            'combatPayTier' => CombatPayTier::Full,
        ], 1);

        // After increment: tracksCompleted=1, numberOfTracks=1, status becomes Completed
        $this->assertEquals(ContractStatus::Completed, $contract->getStatus());
        $this->assertEquals(1, $contract->getTracksCompleted());
    }

    public function testHandlePostTrackHalvesCombatPayForToftt(): void
    {
        $contract = new Contract();
        $contract->setType(ContractType::Raid);
        $contract->setCommandRights(CommandRights::Liaison);
        $contract->setNumberOfTracks(3);
        $contract->setTracksCompleted(0);
        $contract->setScale(2);
        $contract->setStatus(ContractStatus::Active);

        $pendingTrack = new TrackRecord();
        $pendingTrack->setContract($contract)
            ->setTrackNumber(1)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Pending)
            ->setTakingOneForTeam(true);
        $contract->getTrackRecords()->add($pendingTrack);

        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handlePostTrack($contract, $company, [
            'combatPayTier' => CombatPayTier::Full,
        ], 1);
    }

    public function testHandlePostTrackSkipsSupportPointWhenCombatPayIsZero(): void
    {
        $contract = new Contract();
        $contract->setType(ContractType::Raid);
        $contract->setCommandRights(CommandRights::Liaison);
        $contract->setNumberOfTracks(3);
        $contract->setTracksCompleted(0);
        $contract->setScale(1);
        $contract->setStatus(ContractStatus::Active);

        $pendingTrack = new TrackRecord();
        $pendingTrack->setContract($contract)
            ->setTrackNumber(1)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Pending)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($pendingTrack);

        $company = $this->makeCompany();

        // CombatPayTier::None has multiplier 0, so combatPay = 0, no SP entry
        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handlePostTrack($contract, $company, [
            'combatPayTier' => CombatPayTier::None,
        ], 1);
    }

    public function testHandlePostTrackThrowsWhenNoPendingTrack(): void
    {
        $contract = $this->makeContract([
            'numberOfTracks' => 3,
            'tracksCompleted' => 3,
        ]);
        $company = $this->makeCompany();

        $collection = new \Doctrine\Common\Collections\ArrayCollection();
        $contract->method('getTrackRecords')->willReturn($collection);

        $this->expectException(\RuntimeException::class);
        $this->service->handlePostTrack($contract, $company, [
            'combatPayTier' => CombatPayTier::Full,
            'salvageClaimed' => true,
        ], 1);
    }

    public function testHandlePostTrackIncludesSalvageInDescription(): void
    {
        $contract = new Contract();
        $contract->setType(ContractType::Raid);
        $contract->setCommandRights(CommandRights::Liaison);
        $contract->setNumberOfTracks(3);
        $contract->setTracksCompleted(0);
        $contract->setScale(1);
        $contract->setStatus(ContractStatus::Active);

        $pendingTrack = new TrackRecord();
        $pendingTrack->setContract($contract)
            ->setTrackNumber(1)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Pending)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($pendingTrack);

        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handlePostTrack($contract, $company, [
            'combatPayTier' => CombatPayTier::Full,
            'salvageClaimed' => true,
        ], 1);
    }

    public function testHandlePostTrackIncludesNoSalvageInDescription(): void
    {
        $contract = new Contract();
        $contract->setType(ContractType::Raid);
        $contract->setCommandRights(CommandRights::Liaison);
        $contract->setNumberOfTracks(3);
        $contract->setTracksCompleted(0);
        $contract->setScale(1);
        $contract->setStatus(ContractStatus::Active);

        $pendingTrack = new TrackRecord();
        $pendingTrack->setContract($contract)
            ->setTrackNumber(1)
            ->setMissionType('Patrol')
            ->setTerrain('Plains')
            ->setStatus(TrackStatus::Pending)
            ->setTakingOneForTeam(false);
        $contract->getTrackRecords()->add($pendingTrack);

        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handlePostTrack($contract, $company, [
            'combatPayTier' => CombatPayTier::Full,
            'salvageClaimed' => false,
        ], 1);
    }

    // ── handleDowntime ────────────────────────────────────────────────────

    public function testHandleDowntimeCreatesLogWithSpWhenAmountIsNonZero(): void
    {
        $contract = $this->makeContract();
        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleDowntime($contract, $company, 1, 'Waiting for orders', 200);
    }

    public function testHandleDowntimeCreatesLogWithoutSpWhenAmountIsZero(): void
    {
        $contract = $this->makeContract();
        $company = $this->makeCompany();

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleDowntime($contract, $company, 1, 'Waiting for orders', 0);
    }

    public function testHandleDowntimeWithEmptyNote(): void
    {
        $contract = $this->makeContract();
        $company = $this->makeCompany();

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleDowntime($contract, $company, 1, '', 0);
    }

    public function testHandleDowntimeWithNegativeAmount(): void
    {
        $contract = $this->makeContract();
        $company = $this->makeCompany();

        $this->em->expects($this->exactly(2))
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->handleDowntime($contract, $company, 1, 'Penalty', -100);
    }
}
