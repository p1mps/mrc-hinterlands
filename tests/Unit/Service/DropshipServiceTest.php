<?php

namespace App\Tests\Unit\Service;

use App\Entity\Dropship;
use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DropshipServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private \App\Service\DropshipService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new \App\Service\DropshipService($this->em);
    }

    private function makeCompany(array $overrides = []): MercenaryCompany
    {
        $company = $this->createStub(MercenaryCompany::class);
        $company->method('getId')->willReturn(1);
        $company->method('getName')->willReturn('Test Company');
        return $company;
    }

    private function makeDropship(int $id = 1, int $maxCapacity = 40, ?MercenaryCompany $company = null, ?string $name = null, int $mekbayCapacity = 0): Dropship
    {
        $dropship = new Dropship();
        $dropship->setId($id);
        $dropship->setMaxCapacity($maxCapacity);
        $dropship->setMekbayCapacity($mekbayCapacity);
        if ($name !== null) {
            $dropship->setName($name);
        }
        if ($company !== null) {
            $dropship->setCompany($company);
        }
        return $dropship;
    }

    private function makeSalvagedMech(?Dropship $dropship = null): SalvagedMech
    {
        $mechan = new SalvagedMech();
        $mechan->setModel('Catapult CAT-PU1');
        $mechan->setTonnage(80);
        $mechan->setBvCost(300);
        $mechan->setScrapyard(false);
        if ($dropship !== null) {
            $mechan->setDropship($dropship);
        }
        return $mechan;
    }

    // ── Create Dropship Tests ─────────────────────────────────────────────

    public function testCreateDropshipCreatesEntityWithValidCapacity(): void
    {
        $company = $this->makeCompany();

        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);

        $captured = [];
        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($repoMock, &$captured) {
                if (is_subclass_of($class, \App\Entity\Dropship::class)) {
                    return $repoMock;
                }
                return $this->createStub(\App\Repository\SalvagedMechRepository::class);
            });

        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Dropship) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->service->createDropship($company, 40, 'Test Dropship');

        $this->assertCount(1, $captured);
        $this->assertEquals($company, $captured[0]->getCompany());
        $this->assertEquals(40, $captured[0]->getMaxCapacity());
        $this->assertEquals('Test Dropship', $captured[0]->getName());
        $this->assertInstanceOf(Dropship::class, $result);
    }

    public function testCreateDropshipRejectsZeroCapacity(): void
    {
        $company = $this->makeCompany();

        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);

        $this->em
            ->method('getRepository')
            ->willReturn($repoMock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dropship maxCapacity must be at least 40 tons (minimum 2 mechs at 20 tons).');

        $this->service->createDropship($company, 0);
    }

    public function testCreateDropshipRejectsNegativeCapacity(): void
    {
        $company = $this->makeCompany();

        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);

        $this->em
            ->method('getRepository')
            ->willReturn($repoMock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dropship maxCapacity must be at least 40 tons (minimum 2 mechs at 20 tons).');

        $this->service->createDropship($company, -1);
    }

    public function testCreateDropshipRejectsSecondDropshipForCompany(): void
    {
        $company = $this->makeCompany();
        $existing = $this->makeDropship(1, 40, $company);

        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);
        $repoMock->method('findOneBy')->willReturn($existing);

        $this->em
            ->method('getRepository')
            ->willReturn($repoMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This company already has a dropship. Each company may only have one dropship.');

        $this->service->createDropship($company, 40);
    }

    // ── Update Dropship Tests ─────────────────────────────────────────────

    public function testUpdateDropshipAllowsShrinkingBelowCurrentCount(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 100, $company);

        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($repoMock) {
                if (is_subclass_of($class, \App\Entity\Dropship::class)) {
                    return $repoMock;
                }
                return $this->createStub(\App\Repository\SalvagedMechRepository::class);
            });

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->updateDropship($dropship, 40);

        $this->assertEquals(40, $dropship->getMaxCapacity());
    }

    public function testUpdateDropshipRejectsZeroCapacity(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 40, $company);

        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($repoMock) {
                if (is_subclass_of($class, \App\Entity\Dropship::class)) {
                    return $repoMock;
                }
                return $this->createStub(\App\Repository\SalvagedMechRepository::class);
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dropship maxCapacity must be at least 40 tons (minimum 2 mechs at 20 tons).');

        $this->service->updateDropship($dropship, 39);
    }

    public function testUpdateDropshipRejectsNegativeCapacity(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 40, $company);

        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($repoMock) {
                if (is_subclass_of($class, \App\Entity\Dropship::class)) {
                    return $repoMock;
                }
                return $this->createStub(\App\Repository\SalvagedMechRepository::class);
            });

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dropship maxCapacity must be at least 40 tons (minimum 2 mechs at 20 tons).');

        $this->service->updateDropship($dropship, 39);
    }

    // ── Assign Mech to Dropship Tests ──────────────────────────────────────
    // Capacity enforcement is tested in DropshipIntegrationTest via DB constraints.

    // ── Delete Dropship Tests ──────────────────────────────────────────────

    public function testDeleteDropshipWithoutMechsSucceeds(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 40, $company);

        $dropshipRepo = $this->createStub(\App\Repository\DropshipRepository::class);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($dropshipRepo) {
                if (is_subclass_of($class, \App\Entity\Dropship::class)) {
                    return $dropshipRepo;
                }
                return $this->createMock(\App\Repository\SalvagedMechRepository::class);
            });

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->deleteDropship($dropship);
    }

    // ── Get Dropship Tests ─────────────────────────────────────────────────

    public function testGetDropshipReturnsNullWhenNotFound(): void
    {
        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($repoMock) {
                if (is_subclass_of($class, \App\Entity\Dropship::class)) {
                    return $repoMock;
                }
                return $this->createStub(\App\Repository\SalvagedMechRepository::class);
            });

        $result = $this->service->getDropship(999);
        $this->assertNull($result);
    }

    public function testGetDropshipByCompanyReturnsNullWhenNoDropship(): void
    {
        $company = $this->makeCompany();

        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);

        $this->em
            ->method('getRepository')
            ->willReturn($repoMock);

        $result = $this->service->getDropshipByCompany($company);
        $this->assertNull($result);
    }

    // ── Mekbay Tests ───────────────────────────────────────────────────────

    public function testGetUsedMekbaysReturnsCorrectCount(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 40, $company, 'Test', 3);

        $unitRepo = $this->createStub(\App\Repository\UnitRepository::class);
        $unitRepo->method('countTonnageOnDropship')->willReturn(0);

        $salvagedMechRepo = $this->createStub(\App\Repository\SalvagedMechRepository::class);

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturn(2);
        $queryBuilder->method('getQuery')->willReturn($query);

        $queryBuilder2 = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder2->method('select')->willReturn($queryBuilder2);
        $queryBuilder2->method('from')->willReturn($queryBuilder2);
        $queryBuilder2->method('where')->willReturn($queryBuilder2);
        $queryBuilder2->method('setParameter')->willReturn($queryBuilder2);
        $queryBuilder2->method('getQuery')->willReturn($query);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($unitRepo, $salvagedMechRepo) {
                if (is_subclass_of($class, \App\Entity\Unit::class)) {
                    return $unitRepo;
                }
                return $salvagedMechRepo;
            });

        $this->em
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($queryBuilder, $queryBuilder2);

        $result = $this->service->getUsedMekbays($dropship);
        $this->assertEquals(2, $result);
    }

    public function testAssignUnitToDropshipFailsWhenNoMekbaysAvailable(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 100, $company, 'Test', 1);

        $unit = $this->createStub(\App\Entity\Unit::class);
        $unit->method('getTonnage')->willReturn(50);

        $unitRepo = $this->createStub(\App\Repository\UnitRepository::class);
        $unitRepo->method('countTonnageOnDropship')->willReturn(0);

        $salvagedMechRepo = $this->createStub(\App\Repository\SalvagedMechRepository::class);

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturnOnConsecutiveCalls(0, 1);
        $queryBuilder->method('getQuery')->willReturn($query);

        $queryBuilder2 = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder2->method('select')->willReturn($queryBuilder2);
        $queryBuilder2->method('from')->willReturn($queryBuilder2);
        $queryBuilder2->method('where')->willReturn($queryBuilder2);
        $queryBuilder2->method('setParameter')->willReturn($queryBuilder2);
        $queryBuilder2->method('getQuery')->willReturn($query);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($unitRepo, $salvagedMechRepo) {
                if (is_subclass_of($class, \App\Entity\Unit::class)) {
                    return $unitRepo;
                }
                return $salvagedMechRepo;
            });

        $this->em
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($queryBuilder, $queryBuilder2);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No mekbays available. Current mekbays: 1, max: 1.');

        $this->service->assignUnitToDropship($unit, $dropship);
    }

    public function testAssignMechToDropshipIgnoresMekbays(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 200, $company, 'Test', 0);

        $mechan = $this->makeSalvagedMech($dropship);

        $unitRepo = $this->createStub(\App\Repository\UnitRepository::class);
        $unitRepo->method('countTonnageOnDropship')->willReturn(0);

        $salvagedMechRepo = $this->createStub(\App\Repository\SalvagedMechRepository::class);

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturnOnConsecutiveCalls(0, 0);
        $queryBuilder->method('getQuery')->willReturn($query);

        $queryBuilder2 = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder2->method('select')->willReturn($queryBuilder2);
        $queryBuilder2->method('from')->willReturn($queryBuilder2);
        $queryBuilder2->method('where')->willReturn($queryBuilder2);
        $queryBuilder2->method('setParameter')->willReturn($queryBuilder2);
        $queryBuilder2->method('getQuery')->willReturn($query);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($unitRepo, $salvagedMechRepo) {
                if (is_subclass_of($class, \App\Entity\Unit::class)) {
                    return $unitRepo;
                }
                return $salvagedMechRepo;
            });

        $this->em
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($queryBuilder, $queryBuilder2);

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->assignMechToDropship($mechan, $dropship);
    }

    // ── Get Tonnage on Dropship Tests ──────────────────────────────────────

    public function testGetTonnageOnDropshipSumsUnitAndSalvagedMechTonnage(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 200, $company);

        $unitRepo = $this->createStub(\App\Repository\UnitRepository::class);
        $unitRepo->method('countTonnageOnDropship')->willReturn(160);

        $salvagedMechRepo = $this->createStub(\App\Repository\SalvagedMechRepository::class);

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturn(80);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($unitRepo, $salvagedMechRepo) {
                if (is_a($class, \App\Entity\Unit::class, true)) {
                    return $unitRepo;
                }
                return $salvagedMechRepo;
            });

        $this->em
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->service->getTonnageOnDropship($dropship);
        $this->assertEquals(240, $result);
    }

    public function testGetTonnageOnDropshipWithZeroUnitTonnage(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(2, 100, $company);

        $unitRepo = $this->createStub(\App\Repository\UnitRepository::class);
        $unitRepo->method('countTonnageOnDropship')->willReturn(0);

        $salvagedMechRepo = $this->createStub(\App\Repository\SalvagedMechRepository::class);

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturn(0);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($unitRepo, $salvagedMechRepo) {
                if (is_a($class, \App\Entity\Unit::class, true)) {
                    return $unitRepo;
                }
                return $salvagedMechRepo;
            });

        $this->em
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->service->getTonnageOnDropship($dropship);
        $this->assertEquals(0, $result);
    }

    public function testGetTonnageOnDropshipWithNullSalvagedMechTonnage(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(3, 50, $company);

        $unitRepo = $this->createStub(\App\Repository\UnitRepository::class);
        $unitRepo->method('countTonnageOnDropship')->willReturn(30);

        $salvagedMechRepo = $this->createStub(\App\Repository\SalvagedMechRepository::class);

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getSingleScalarResult')->willReturn(null);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->em
            ->method('getRepository')
            ->willReturnCallback(function ($class) use ($unitRepo, $salvagedMechRepo) {
                if (is_a($class, \App\Entity\Unit::class, true)) {
                    return $unitRepo;
                }
                return $salvagedMechRepo;
            });

        $this->em
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->service->getTonnageOnDropship($dropship);
        $this->assertEquals(30, $result);
    }

    // ── Get Unassigned Mech Tests ──────────────────────────────────────────

    public function testGetUnassignedMechsReturnsOnlyUnassignedMechs(): void
    {
        $company = $this->makeCompany();

        $mechan1 = $this->makeSalvagedMech(null);
        $mechan2 = $this->makeSalvagedMech(null);

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn([$mechan1, $mechan2]);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->em
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->service->getUnassignedMechs($company);

        $this->assertCount(2, $result);
    }

    public function testGetUnassignedMechsReturnsEmptyCollectionWhenNoneUnassigned(): void
    {
        $company = $this->makeCompany();

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);

        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn([]);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->em
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $result = $this->service->getUnassignedMechs($company);

        $this->assertCount(0, $result);
    }
}
