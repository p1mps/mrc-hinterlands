<?php

namespace App\Tests\Unit\Service;

use App\Entity\MercenaryCompany;
use App\Entity\Pilot;
use App\Entity\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class RosterServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private \App\Service\RosterService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new \App\Service\RosterService($this->em);
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

        $this->assertSame($units, $result);
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
}
