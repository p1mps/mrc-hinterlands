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

    private function makeDropship(int $id = 1, int $maxCapacity = 5, ?MercenaryCompany $company = null, ?string $name = null): Dropship
    {
        $dropship = new Dropship();
        $dropship->setId($id);
        $dropship->setMaxCapacity($maxCapacity);
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

        $result = $this->service->createDropship($company, 5, 'Test Dropship');

        $this->assertCount(1, $captured);
        $this->assertEquals($company, $captured[0]->getCompany());
        $this->assertEquals(5, $captured[0]->getMaxCapacity());
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
        $this->expectExceptionMessage('Dropship maxCapacity must be a positive integer.');

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
        $this->expectExceptionMessage('Dropship maxCapacity must be a positive integer.');

        $this->service->createDropship($company, -1);
    }

    public function testCreateDropshipRejectsSecondDropshipForCompany(): void
    {
        $company = $this->makeCompany();
        $existing = $this->makeDropship(1, 3, $company);

        $repoMock = $this->createStub(\App\Repository\DropshipRepository::class);
        $repoMock->method('findOneBy')->willReturn($existing);

        $this->em
            ->method('getRepository')
            ->willReturn($repoMock);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This company already has a dropship. Each company may only have one dropship.');

        $this->service->createDropship($company, 5);
    }

    // ── Update Dropship Tests ─────────────────────────────────────────────

    public function testUpdateDropshipAllowsShrinkingBelowCurrentCount(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 5, $company);

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

        $this->service->updateDropship($dropship, 2);

        $this->assertEquals(2, $dropship->getMaxCapacity());
    }

    public function testUpdateDropshipRejectsZeroCapacity(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 5, $company);

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
        $this->expectExceptionMessage('Dropship maxCapacity must be a positive integer.');

        $this->service->updateDropship($dropship, 0);
    }

    public function testUpdateDropshipRejectsNegativeCapacity(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 5, $company);

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
        $this->expectExceptionMessage('Dropship maxCapacity must be a positive integer.');

        $this->service->updateDropship($dropship, -3);
    }

    // ── Assign Mech to Dropship Tests ──────────────────────────────────────
    // Capacity enforcement is tested in DropshipIntegrationTest via DB constraints.

    // ── Delete Dropship Tests ──────────────────────────────────────────────

    public function testDeleteDropshipWithoutMechsSucceeds(): void
    {
        $company = $this->makeCompany();
        $dropship = $this->makeDropship(1, 5, $company);

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
}
