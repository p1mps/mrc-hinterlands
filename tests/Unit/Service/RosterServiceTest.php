<?php

namespace App\Tests\Unit\Service;

use App\Entity\MercenaryCompany;
use App\Entity\Pilot;
use App\Entity\Unit;
use App\Enum\DamageState;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class RosterServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private \App\Service\SalvageCalculationService $salvageCalc;
    private \App\Repository\ContractRepository $contractRepo;
    private \App\Service\RosterService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->salvageCalc = $this->createStub(\App\Service\SalvageCalculationService::class);
        $this->contractRepo = $this->createStub(\App\Repository\ContractRepository::class);
        $this->service = new \App\Service\RosterService($this->em, $this->salvageCalc, $this->contractRepo);
    }

    private function makeCompany(): MercenaryCompany
    {
        return $this->createStub(MercenaryCompany::class);
    }

    private function makePilot(MercenaryCompany $company, ?int $id = 1): Pilot
    {
        $pilot = $this->createStub(Pilot::class);
        $pilot->method('getId')->willReturn($id);
        $pilot->method('getCompany')->willReturn($company);

        return $pilot;
    }

    private function makeUnit(?object $pilot = null): Unit
    {
        $unit = $this->createMock(Unit::class);
        $unit->method('getPilot')->willReturn($pilot);

        // Configure fluent setters to return $this
        $unit->method('setCompany')->willReturnSelf();
        $unit->method('setPilot')->willReturnSelf();

        return $unit;
    }

    // ── getUnits ──────────────────────────────────────────────────────────

    public function testGetUnitsDelegatesToCompany(): void
    {
        $company = $this->makeCompany();
        $units = new ArrayCollection();

        $company->method('getUnits')->willReturn($units);

        $result = $this->service->getUnits($company);

        $this->assertEquals($units, $result);
    }

    // ── getPilots ─────────────────────────────────────────────────────────

    public function testGetPilotsDelegatesToCompany(): void
    {
        $company = $this->makeCompany();
        $pilots = new ArrayCollection();

        $company->method('getPilots')->willReturn($pilots);

        $result = $this->service->getPilots($company);

        $this->assertSame($pilots, $result);
    }

    // ── createUnit ────────────────────────────────────────────────────────

    public function testCreateUnitSetsCompanyPersistsAndFlushes(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeUnit();

        $this->em->expects($this->once())
            ->method('persist')
            ->with($unit);

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->createUnit($company, $unit);
    }

    public function testCreateUnitCallsSetCompanyOnUnit(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeUnit();

        $unit->expects($this->once())
            ->method('setCompany')
            ->with($company);

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->createUnit($company, $unit);
    }

    // ── updateUnit ────────────────────────────────────────────────────────

    public function testUpdateUnitFlushesEntityManager(): void
    {
        $unit = $this->makeUnit();

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->updateUnit($unit);
    }

    public function testUpdateUnitDoesNotPersist(): void
    {
        $unit = $this->makeUnit();

        $this->em->expects($this->never())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->updateUnit($unit);
    }

    // ── assignPilotToUnit ─────────────────────────────────────────────────

    public function testAssignPilotToUnitWithNullIdUnassignsPilot(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeUnit($this->makePilot($company));

        $this->em->expects($this->once())
            ->method('flush');

        $unit->expects($this->once())
            ->method('setPilot')
            ->with(null);

        $result = $this->service->assignPilotToUnit($unit, null, $company);

        $this->assertNull($result);
    }

    public function testAssignPilotToUnitWithZeroIdUnassignsPilot(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeUnit($this->makePilot($company));

        $this->em->expects($this->once())
            ->method('flush');

        $unit->expects($this->once())
            ->method('setPilot')
            ->with(null);

        $result = $this->service->assignPilotToUnit($unit, 0, $company);

        $this->assertNull($result);
    }

    public function testAssignPilotToUnitFindsPilotFromRepository(): void
    {
        $company = $this->makeCompany();
        $pilot = $this->makePilot($company);
        $unit = $this->makeUnit();
        $repo = $this->createStub(EntityRepository::class);

        $repo->method('find')->willReturn($pilot);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Pilot::class)
            ->willReturn($repo);

        $this->em->expects($this->once())
            ->method('flush');

        $unit->expects($this->once())
            ->method('setPilot')
            ->with($pilot);

        $result = $this->service->assignPilotToUnit($unit, 42, $company);

        $this->assertNull($result);
    }

    public function testAssignPilotToUnitReturnsErrorWhenPilotNotFound(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeUnit();
        $repo = $this->createStub(EntityRepository::class);

        $repo->method('find')->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Pilot::class)
            ->willReturn($repo);

        // flush should NOT be called when pilot is not found
        $this->em->expects($this->never())
            ->method('flush');

        $unit->expects($this->never())
            ->method('setPilot');

        $result = $this->service->assignPilotToUnit($unit, 999, $company);

        $this->assertEquals('Pilot not found or does not belong to this company.', $result);
    }

    public function testAssignPilotToUnitReturnsErrorWhenPilotBelongsToDifferentCompany(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $pilot = $this->makePilot($companyB);
        $unit = $this->makeUnit();
        $repo = $this->createStub(EntityRepository::class);

        $repo->method('find')->willReturn($pilot);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Pilot::class)
            ->willReturn($repo);

        // flush should NOT be called when pilot belongs to a different company
        $this->em->expects($this->never())
            ->method('flush');

        $unit->expects($this->never())
            ->method('setPilot');

        $result = $this->service->assignPilotToUnit($unit, 1, $companyA);

        $this->assertEquals('Pilot not found or does not belong to this company.', $result);
    }

    public function testAssignPilotToUnitSucceedsWhenPilotBelongsToSameCompany(): void
    {
        $company = $this->makeCompany();
        $pilot = $this->makePilot($company);
        $unit = $this->makeUnit();
        $repo = $this->createStub(EntityRepository::class);

        $repo->method('find')->willReturn($pilot);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Pilot::class)
            ->willReturn($repo);

        $this->em->expects($this->once())
            ->method('flush');

        $unit->expects($this->once())
            ->method('setPilot')
            ->with($pilot);

        $result = $this->service->assignPilotToUnit($unit, 1, $company);

        $this->assertNull($result);
    }

    // ── deleteUnit ────────────────────────────────────────────────────────

    public function testDeleteUnitRemovesAndFlushes(): void
    {
        $unit = $this->makeUnit();

        $this->em->expects($this->once())
            ->method('remove')
            ->with($unit);

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteUnit($unit);
    }

    public function testDeleteUnitDoesNotPersist(): void
    {
        $unit = $this->makeUnit();

        $this->em->expects($this->never())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteUnit($unit);
    }

    // ── repairUnit ────────────────────────────────────────────────────────

    private function makeRepairUnit(
        ?DamageState $damageState = DamageState::Structural,
        int $tonnage = 100,
        ?string $name = 'Thunderbird TBR-1',
        ?string $chassis = 'Thunderbird TBR-1'
    ): Unit {
        $unit = $this->createMock(Unit::class);
        $unit->method('getDamageState')->willReturn($damageState);
        $unit->method('getTonnage')->willReturn($tonnage);
        $unit->method('getName')->willReturn($name);
        $unit->method('getChassis')->willReturn($chassis);
        $unit->method('setDamageState')->willReturnSelf();

        return $unit;
    }

    public function testRepairUnitSucceeds(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeRepairUnit();

        $this->salvageCalc->method('calculateRepairCost')->willReturn(200);

        $this->em->expects($this->once())
            ->method('flush');

        $unit->expects($this->once())
            ->method('setDamageState')
            ->with(DamageState::None);

        $result = $this->service->repairUnit($unit, $company);

        $this->assertNull($result);
    }

    public function testRepairUnitSetsDamageStateToNone(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeRepairUnit(DamageState::Crippled);

        $this->salvageCalc->method('calculateRepairCost')->willReturn(300);

        $unit->expects($this->once())
            ->method('setDamageState')
            ->with(DamageState::None);

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->repairUnit($unit, $company);

        $this->assertNull($result);
    }

    public function testRepairUnitFailsWhenAlreadyNone(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeRepairUnit(DamageState::None);

        $this->salvageCalc->method('calculateRepairCost')->willReturn(0);

        $this->em->expects($this->never())
            ->method('flush');

        $result = $this->service->repairUnit($unit, $company);

        $this->assertEquals('Unit is already fully repaired.', $result);
    }

    public function testRepairUnitFailsInsufficientFunds(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeRepairUnit();

        $this->salvageCalc->method('calculateRepairCost')->willReturn(200);

        $company->method('deductSupportPoints')
            ->willThrowException(new \Exception('Insufficient support points. Current balance: 100, Requested deduction: 200'));

        $this->em->expects($this->never())
            ->method('flush');

        $result = $this->service->repairUnit($unit, $company);

        $this->assertStringContainsString('Insufficient support points', $result);
    }

    public function testRepairUnitFailsWhenCalculateCostReturnsNull(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeRepairUnit();

        $this->salvageCalc->method('calculateRepairCost')->willReturn(null);

        $this->em->expects($this->never())
            ->method('flush');

        $result = $this->service->repairUnit($unit, $company);

        $this->assertEquals('Could not calculate repair cost.', $result);
    }

    public function testRepairUnitWithArmorOnlyDamage(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeRepairUnit(DamageState::ArmorOnly, 80);

        $this->salvageCalc->method('calculateRepairCost')->willReturn(40);

        $unit->expects($this->once())
            ->method('setDamageState')
            ->with(DamageState::None);

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->repairUnit($unit, $company);

        $this->assertNull($result);
    }

    public function testRepairUnitWithDestroyedDamage(): void
    {
        $company = $this->makeCompany();
        $unit = $this->makeRepairUnit(DamageState::Destroyed, 120);

        $this->salvageCalc->method('calculateRepairCost')->willReturn(600);

        $unit->expects($this->once())
            ->method('setDamageState')
            ->with(DamageState::None);

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->repairUnit($unit, $company);

        $this->assertNull($result);
    }

    public function testRepairUnitDeductsCorrectSupportPoints(): void
    {
        $company = $this->createMock(MercenaryCompany::class);
        $unit = $this->makeRepairUnit(DamageState::Structural, 100);

        $this->salvageCalc->method('calculateRepairCost')->willReturn(200);

        $company->expects($this->once())
            ->method('deductSupportPoints')
            ->with(200, 'Repair of Thunderbird TBR-1 (Thunderbird TBR-1)');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->repairUnit($unit, $company);
    }
}
