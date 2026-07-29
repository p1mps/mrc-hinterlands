<?php

namespace App\Tests\Unit\Service;

use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use App\Entity\Unit;
use App\Enum\UnitType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MechAcquisitionServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private \App\Service\MechAcquisitionService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new \App\Service\MechAcquisitionService($this->em);
    }

    private function makeSalvagedMech(array $overrides = []): SalvagedMech
    {
        $mechan = new SalvagedMech();
        $defaults = [
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bvCost' => 300,
            'acquired' => false,
        ];
        foreach ($defaults as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($mechan, $setter)) {
                $mechan->$setter($value);
            }
        }
        $merged = array_merge($defaults, $overrides);
        $mechan->setModel($merged['model']);
        $mechan->setTonnage($merged['tonnage']);
        $mechan->setBvCost($merged['bvCost']);
        $mechan->setAcquired($merged['acquired']);

        return $mechan;
    }

    private function makeCompany(int $balance = 1000): MercenaryCompany
    {
        $company = $this->createStub(MercenaryCompany::class);
        $company->method('getSupportPointsBalance')->willReturn($balance);
        $company->method('deductSupportPoints')
            ->willReturnCallback(function ($amount, $reason) use ($balance) {
                if ($amount > $balance) {
                    throw new \Exception("Insufficient support points. Current balance: {$balance}, Requested deduction: {$amount}");
                }
            });

        return $company;
    }

    // ── Happy Path ────────────────────────────────────────────────────────

    public function testAcquireMechCreatesUnitAndRemovesSalvagedMech(): void
    {
        $mechan = $this->makeSalvagedMech();
        $company = $this->makeCompany();

        // persist() is called once for the new Unit; SalvagedMech is already managed so setAcquired()
        // doesn't need a separate persist() — only remove() marks it for deletion
        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('remove')
            ->with($mechan);

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechMapsSalvagedMechFieldsToNewUnit(): void
    {
        $mechan = $this->makeSalvagedMech([
            'model' => 'Gravino GRV-NI1',
            'tonnage' => 35,
            'bvCost' => 150,
        ]);
        $company = $this->makeCompany();

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        // Verify the persisted Unit has the correct state
        $this->assertCount(1, $captured);
        $unit = $captured[0];
        $this->assertEquals('Gravino GRV-NI1', $unit->getName());
        $this->assertEquals('Gravino GRV-NI1', $unit->getChassis());
        $this->assertEquals(35, $unit->getTonnage());
        $this->assertEquals(150, $unit->getBv());
        $this->assertEquals(UnitType::Mech, $unit->getUnitType());
    }

    public function testAcquireMechMarksSalvagedMechAsAcquired(): void
    {
        $mechan = $this->makeSalvagedMech();
        $company = $this->makeCompany();

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertTrue($mechan->isAcquired());
    }

    public function testAcquireMechLinksUnitToCompany(): void
    {
        $mechan = $this->makeSalvagedMech();
        $company = $this->makeCompany();

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertSame($company, $captured[0]->getCompany());
    }

    // ── BV Cost Validation ────────────────────────────────────────────────

    public function testAcquireMechThrowsWhenBvCostIsNull(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => null]);
        $company = $this->makeCompany();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Salvaged Mech must have a valid BV cost.');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechThrowsWhenBvCostIsZero(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 0]);
        $company = $this->makeCompany();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Salvaged Mech must have a valid BV cost.');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechThrowsWhenBvCostIsNegative(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => -50]);
        $company = $this->makeCompany();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Salvaged Mech must have a valid BV cost.');

        $this->service->acquireMech($mechan, $company);
    }

    // ── Support Points Deduction ──────────────────────────────────────────

    public function testAcquireMechDeductsCorrectSupportPoints(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 450, 'model' => 'Thunderbird TBR-1']);
        $company = $this->makeCompany(500);

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechFailsWhenInsufficientSupportPoints(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 800]);
        $company = $this->makeCompany(500);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient support points/');

        $this->service->acquireMech($mechan, $company);

        // Verify the SalvagedMech was NOT marked as acquired
        $this->assertFalse($mechan->isAcquired());
    }

    public function testAcquireMechDoesNotPersistOrRemoveWhenInsufficientFunds(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 800]);
        $company = $this->makeCompany(500);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient support points/');

        // persist/remove/flush should never be called because deductSupportPoints
        // throws before we reach the persist/flush calls
        $this->em->expects($this->never())
            ->method('persist');

        $this->em->expects($this->never())
            ->method('remove');

        $this->em->expects($this->never())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);
    }

    // ── Null/Default Field Handling ────────────────────────────────────────

    public function testAcquireMechHandlesNullModel(): void
    {
        $mechan = $this->makeSalvagedMech(['model' => null, 'bvCost' => 200]);
        $company = $this->makeCompany();

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        // When model is null, the ?? 'Unknown Chassis' fallback should apply
        $this->assertCount(1, $captured);
        $this->assertEquals('Unknown Chassis', $captured[0]->getChassis());
    }

    public function testAcquireMechHandlesNullTonnage(): void
    {
        $mechan = $this->makeSalvagedMech(['tonnage' => null, 'bvCost' => 200]);
        $company = $this->makeCompany();

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        // When tonnage is null, the ?? 0 fallback should apply
        $this->assertCount(1, $captured);
        $this->assertEquals(0, $captured[0]->getTonnage());
    }

    // ── Unit State Verification ───────────────────────────────────────────

    public function testAcquireMechSetsUnitNameToModelString(): void
    {
        $mechan = $this->makeSalvagedMech();
        $company = $this->makeCompany();

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        // setName uses the model string (fixed from the original null bug)
        $this->assertEquals('Catapult CAT-PU1', $captured[0]->getName());
    }

    public function testAcquireMechSetsUnitBvFromSalvagedMechBvCost(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 999]);
        $company = $this->makeCompany();

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertEquals(999, $captured[0]->getBv());
    }

    public function testAcquireMechSetsUnitTypeToMech(): void
    {
        $mechan = $this->makeSalvagedMech();
        $company = $this->makeCompany();

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertEquals(UnitType::Mech, $captured[0]->getUnitType());
    }

    // ── Already Acquired Mech ─────────────────────────────────────────────

    public function testAcquireMechWorksOnAlreadyAcquiredMech(): void
    {
        $mechan = $this->makeSalvagedMech(['acquired' => true]);
        $company = $this->makeCompany();

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        // Even if already acquired, calling setAcquired(true) again is idempotent
        $this->assertTrue($mechan->isAcquired());
    }

    // ── Low Balance Edge Case ──────────────────────────────────────────────

    public function testAcquireMechSucceedsWhenBalanceEqualsBvCost(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 500]);
        $company = $this->makeCompany(500);

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechFailsWhenBalanceIsOneLessThanBvCost(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 500]);
        $company = $this->makeCompany(499);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient support points/');

        $this->service->acquireMech($mechan, $company);

        $this->em->expects($this->never())
            ->method('persist');

        $this->em->expects($this->never())
            ->method('remove');

        $this->em->expects($this->never())
            ->method('flush');
    }
}
