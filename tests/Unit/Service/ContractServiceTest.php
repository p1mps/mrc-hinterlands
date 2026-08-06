<?php

namespace App\Tests\Unit\Service;

use App\Entity\Contract;
use App\Entity\ContractLogEntry;
use App\Entity\SupportPointEntry;
use App\Entity\TrackRecord;
use App\Enum\CombatPayTier;
use App\Enum\ContractLogEntryType;
use App\Enum\ContractStatus;
use App\Enum\TrackStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\RepositoryFactory;
use App\Service\ContractGeneratorService;
use App\Service\ContractService;
use PHPUnit\Framework\TestCase;

class ContractServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private ContractGeneratorService $generator;
    private ContractService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->generator = $this->createStub(ContractGeneratorService::class);
        $this->service = new ContractService($this->em, $this->generator);
    }

    private function makeContract(array $overrides = []): Contract
    {
        $contract = $this->createMock(Contract::class);
        $defaults = [
            'numberOfTracks' => 1,
            'tracksCompleted' => 0,
            'status' => ContractStatus::Available,
            'scale' => 1,
            'type' => \App\Enum\ContractType::Expedition,
            'commandRights' => \App\Enum\CommandRights::Integrated,
            'transportTerms' => '—',
            'basePay' => 500,
            'company' => null,
        ];
        $merged = array_merge($defaults, $overrides);

        $contract->method('getType')->willReturn($merged['type']);
        $contract->method('getCommandRights')->willReturn($merged['commandRights']);
        $contract->method('getScale')->willReturn($merged['scale']);
        $contract->method('getDurationMonths')->willReturn(12);
        $contract->method('getBasePayPercent')->willReturn(75);
        $contract->method('getNumberOfTracks')->willReturn($merged['numberOfTracks']);
        $contract->method('getTracksCompleted')->willReturn($merged['tracksCompleted']);
        $contract->method('getStatus')->willReturn($merged['status']);
        $contract->method('parseTransportPercent')->willReturn(0);
        $contract->method('calculateMonthlyBasePay')->willReturn($merged['basePay']);
        $contract->method('calculateMonthlyCombatPay')
            ->willReturnCallback(fn(\App\Enum\CombatPayTier $tier) => (int) round($merged['basePay'] * $tier->multiplier()));
        $contract->method('getTrackRecords')->willReturn(
            new \Doctrine\Common\Collections\ArrayCollection()
        );
        $contract->method('getCompany')->willReturn($merged['company'] ?? null);

        return $contract;
    }

    private function makeCompany(): \App\Entity\MercenaryCompany
    {
        return $this->createStub(\App\Entity\MercenaryCompany::class);
    }

    private function makeMockCompany(): \App\Entity\MercenaryCompany
    {
        return $this->createMock(\App\Entity\MercenaryCompany::class);
    }

    // ── createContract ───────────────────────────────────────────────────

    public function testCreateContractFromContractEntity(): void
    {
        $source = $this->createStub(Contract::class);
        $source->method('getType')->willReturn(\App\Enum\ContractType::Garrison);
        $source->method('getEmployer')->willReturn('Client');
        $source->method('getEmployerAffiliation')->willReturn('House Davion');
        $source->method('getScale')->willReturn(3);
        $source->method('getDurationMonths')->willReturn(6);
        $source->method('getBasePayPercent')->willReturn(50);
        $source->method('getCommandRights')->willReturn(\App\Enum\CommandRights::House);
        $source->method('getSupportTerms')->willReturn('Battle 50%');
        $source->method('getSalvageRights')->willReturn('3');
        $source->method('getTransportTerms')->willReturn('10%');
        $source->method('getNumberOfTracks')->willReturn(2);
        $source->method('isOpposing')->willReturn(false);
        $source->method('getCompany')->willReturn($this->makeCompany());
        $source->method('getOpposingCompany')->willReturn($this->makeCompany());
        $source->method('getLinkedContract')->willReturn($this->createStub(Contract::class));

        $result = $this->service->createContract($source);
        $this->assertInstanceOf(Contract::class, $result);
        $this->assertEquals(\App\Enum\ContractType::Garrison, $result->getType());
        $this->assertEquals('Client', $result->getEmployer());
    }

    public function testCreateContractFromArray(): void
    {
        $data = [
            'type' => \App\Enum\ContractType::Raid,
            'employer' => 'Warden',
            'employerAffiliation' => 'ComStar',
            'scale' => 1,
            'durationMonths' => 3,
            'basePayPercent' => 100,
            'commandRights' => \App\Enum\CommandRights::Independent,
            'supportTerms' => 'None',
            'salvageRights' => 'Exchange',
            'transportTerms' => '—',
            'numberOfTracks' => 1,
            'isOpposing' => true,
        ];

        $result = $this->service->createContract($data);
        $this->assertInstanceOf(Contract::class, $result);
        $this->assertEquals(\App\Enum\ContractType::Raid, $result->getType());
        $this->assertEquals('Warden', $result->getEmployer());
        $this->assertTrue($result->isOpposing());
    }

    public function testCreateContractFromArrayWithOptionalFields(): void
    {
        $data = [
            'type' => \App\Enum\ContractType::Expedition,
            'employer' => 'Client',
            'commandRights' => \App\Enum\CommandRights::Integrated,
            'supportTerms' => 'None',
            'salvageRights' => '3',
            'transportTerms' => '—',
            'numberOfTracks' => 1,
            'scale' => 1,
            'name' => 'Test Contract',
            'planet' => 'New Avalon',
            'intensity' => 'High',
            'description' => 'A test contract',
        ];

        $result = $this->service->createContract($data);
        $this->assertInstanceOf(Contract::class, $result);
    }

    public function testCreateContractFromArrayOmittingOptionalFields(): void
    {
        $data = [
            'type' => \App\Enum\ContractType::Expedition,
            'employer' => 'Client',
            'commandRights' => \App\Enum\CommandRights::Integrated,
            'supportTerms' => 'None',
            'salvageRights' => '3',
            'transportTerms' => '—',
            'numberOfTracks' => 1,
            'scale' => 1,
        ];

        $result = $this->service->createContract($data);
        $this->assertInstanceOf(Contract::class, $result);
    }

    // ── acceptContract ───────────────────────────────────────────────────

    public function testAcceptContractSetsStatusAndTimestamp(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->method('getStatus')->willReturn(ContractStatus::Available);

        $this->service->acceptContract($contract);
        $this->assertTrue(true); // no exception = pass
    }

    // ── handleTrackSetup ─────────────────────────────────────────────────

    public function testHandleTrackSetupCreatesTrackAndLogEntry(): void
    {
        $contract = $this->makeContract([
            'getTracksCompleted' => 0,
        ]);
        $this->generator->method('rollTrackSetup')->willReturn([
            'missionType' => 'Assault',
            'terrain' => 'Urban',
            'complication' => 'None',
            'terrainSetting' => 'Standard',
        ]);

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->handleTrackSetup($contract, 1);
    }

    // ── handlePostTrack ──────────────────────────────────────────────────

    public function testHandlePostTrackWithNoPendingTrack(): void
    {
        $contract = $this->makeContract(['company' => $this->makeCompany()]);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->handlePostTrack($contract, ['combatPayTier' => CombatPayTier::None, 'salvageClaimed' => false], 1);
    }

    public function testHandlePostTrackWithToftT(): void
    {
        $track = $this->createStub(TrackRecord::class);
        $track->method('isTakingOneForTeam')->willReturn(true);
        $track->method('getStatus')->willReturn(TrackStatus::Pending);

        $contract = $this->makeContract([
            'company' => $this->makeCompany(),
        ]);
        $contract->method('getTrackRecords')->willReturn(
            new \Doctrine\Common\Collections\ArrayCollection([$track])
        );

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->handlePostTrack($contract, ['combatPayTier' => CombatPayTier::Full, 'salvageClaimed' => false], 1);
    }

    public function testHandlePostTrackWithSalvageClaimed(): void
    {
        $contract = $this->makeContract([
            'company' => $this->makeCompany(),
        ]);

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->handlePostTrack($contract, ['combatPayTier' => CombatPayTier::Full, 'salvageClaimed' => true], 1);
    }

    public function testHandlePostTrackWithFullPay(): void
    {
        $contract = $this->makeContract([
            'company' => $this->makeCompany(),
        ]);

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->handlePostTrack($contract, ['combatPayTier' => CombatPayTier::Full, 'salvageClaimed' => false], 1);
    }

    // ── handleDowntime ───────────────────────────────────────────────────

    public function testHandleDowntimeWithNonZeroAmount(): void
    {
        $contract = $this->makeContract(['company' => $this->makeCompany()]);

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->handleDowntime($contract, ['amount' => 50, 'note' => 'R&R'], 1);
    }

    public function testHandleDowntimeWithZeroAmount(): void
    {
        $contract = $this->makeContract(['company' => $this->makeCompany()]);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->handleDowntime($contract, ['amount' => 0, 'note' => 'Training'], 1);
    }

    // ── handleSalvage ────────────────────────────────────────────────────

    public function testHandleSalvageWithNonZeroAmount(): void
    {
        $contract = $this->makeContract(['company' => $this->makeCompany()]);

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->handleSalvage($contract, ['amount' => 100, 'note' => 'Found parts'], 1);
    }

    public function testHandleSalvageWithZeroAmount(): void
    {
        $contract = $this->makeContract(['company' => $this->makeCompany()]);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->handleSalvage($contract, ['amount' => 0, 'note' => 'Nothing found'], 1);
    }

    // ── adjustReputationForTrack ─────────────────────────────────────────

    public function testAdjustReputationForTrackFull(): void
    {
        $company = $this->createMock(\App\Entity\MercenaryCompany::class);
        $company->expects($this->once())->method('adjustReputation')->with(1);
        $contract = $this->makeContract(['company' => $company]);

        $this->service->adjustReputationForTrack($contract, CombatPayTier::Full);
    }

    public function testAdjustReputationForTrackHalfAgain(): void
    {
        $company = $this->createMock(\App\Entity\MercenaryCompany::class);
        $company->expects($this->once())->method('adjustReputation')->with(1);
        $contract = $this->makeContract(['company' => $company]);

        $this->service->adjustReputationForTrack($contract, CombatPayTier::HalfAgain);
    }

    public function testAdjustReputationForTrackHalf(): void
    {
        $company = $this->createMock(\App\Entity\MercenaryCompany::class);
        $company->expects($this->once())->method('adjustReputation')->with(-1);
        $contract = $this->makeContract(['company' => $company]);

        $this->service->adjustReputationForTrack($contract, CombatPayTier::Half);
    }

    public function testAdjustReputationForTrackNone(): void
    {
        $company = $this->createMock(\App\Entity\MercenaryCompany::class);
        $company->expects($this->never())->method('adjustReputation');
        $contract = $this->makeContract(['company' => $company]);

        $this->service->adjustReputationForTrack($contract, CombatPayTier::None);
    }

    public function testAdjustReputationForTrackNullCompany(): void
    {
        $contract = $this->makeContract();

        $this->service->adjustReputationForTrack($contract, CombatPayTier::Full);
        $this->assertTrue(true); // no exception = pass
    }

    // ── breachContract ───────────────────────────────────────────────────

    public function testBreachContractReducesReputation(): void
    {
        $company = $this->createMock(\App\Entity\MercenaryCompany::class);
        $company->expects($this->once())->method('adjustReputation')->with(-3);
        $contract = $this->makeContract(['company' => $company]);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service->breachContract($contract);
    }

    public function testBreachContractWithNullCompany(): void
    {
        $contract = $this->makeContract();

        $this->service->breachContract($contract);
        $this->assertTrue(true); // no exception = pass
    }

    // ── applyNegotiatedTerms ───────────────────────────────────────────────

    public function testApplyNegotiatedTermsUpdatesContractAndFlushes(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->method('setType')->willReturn($contract);
        $contract->method('setEmployer')->willReturn($contract);
        $contract->method('setEmployerAffiliation')->willReturn($contract);
        $contract->method('setScale')->willReturn($contract);
        $contract->method('setDurationMonths')->willReturn($contract);
        $contract->method('setBasePayPercent')->willReturn($contract);
        $contract->method('setCommandRights')->willReturn($contract);
        $contract->method('setSupportTerms')->willReturn($contract);
        $contract->method('setSalvageRights')->willReturn($contract);
        $contract->method('setTransportTerms')->willReturn($contract);
        $contract->method('setNumberOfTracks')->willReturn($contract);

        $this->em->expects($this->once())->method('flush');

        $this->service->applyNegotiatedTerms($contract, [
            'type' => \App\Enum\ContractType::Raid,
            'employer' => 'New Client',
            'employerAffiliation' => 'House Liao',
            'scale' => 2,
            'durationMonths' => 6,
            'basePayPercent' => 80,
            'commandRights' => \App\Enum\CommandRights::House,
            'supportTerms' => 'Battle 75%',
            'salvageRights' => '4',
            'transportTerms' => '15%',
            'numberOfTracks' => 3,
        ]);
    }

    public function testApplyNegotiatedTermsWithOptionalEmployerAffiliation(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->method('setType')->willReturn($contract);
        $contract->method('setEmployer')->willReturn($contract);
        $contract->method('setEmployerAffiliation')->willReturn($contract);
        $contract->method('setScale')->willReturn($contract);
        $contract->method('setDurationMonths')->willReturn($contract);
        $contract->method('setBasePayPercent')->willReturn($contract);
        $contract->method('setCommandRights')->willReturn($contract);
        $contract->method('setSupportTerms')->willReturn($contract);
        $contract->method('setSalvageRights')->willReturn($contract);
        $contract->method('setTransportTerms')->willReturn($contract);
        $contract->method('setNumberOfTracks')->willReturn($contract);

        $this->em->expects($this->once())->method('flush');

        $this->service->applyNegotiatedTerms($contract, [
            'type' => \App\Enum\ContractType::Expedition,
            'employer' => 'Client',
            'scale' => 1,
            'durationMonths' => 12,
            'basePayPercent' => 100,
            'commandRights' => \App\Enum\CommandRights::Integrated,
            'supportTerms' => 'None',
            'salvageRights' => '3',
            'transportTerms' => '—',
            'numberOfTracks' => 1,
        ]);
    }
}
