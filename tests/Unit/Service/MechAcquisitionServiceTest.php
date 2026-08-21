<?php

namespace App\Tests\Unit\Service;

use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use App\Entity\Unit;
use App\Enum\DamageState;
use App\Enum\UnitType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MechAcquisitionServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private \App\Service\SalvageCalculationService $salvageCalc;
    private \App\Service\MechAcquisitionService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->salvageCalc = $this->createStub(\App\Service\SalvageCalculationService::class);
        $this->service = new \App\Service\MechAcquisitionService($this->em, $this->salvageCalc);
    }

    private function makeSalvagedMech(array $overrides = []): SalvagedMech
    {
        $mechan = new SalvagedMech();
        $defaults = [
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bvCost' => 300,
            'scrapyard' => false,
            'repairCost' => null,
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
        $mechan->setScrapyard($merged['scrapyard'] ?? false);
        if (isset($merged['salvageRightsPercent'])) {
            $mechan->setSalvageRightsPercent($merged['salvageRightsPercent']);
        }
        if (isset($merged['repairCost'])) {
            $mechan->setRepairCost($merged['repairCost']);
        }

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

    public function testAcquireMechCreatesUnit(): void
    {
        $mechan = $this->makeSalvagedMech();
        $company = $this->makeCompany();

        // persist() is called once for the new Unit; SalvagedMech is NOT removed
        $this->em->expects($this->once())
            ->method('persist');

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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        // Verify the persisted Unit has the correct state
        $this->assertCount(1, $captured);
        $unit = $captured[0];
        $this->assertEquals('Gravino GRV-NI1', $unit->getName());
        $this->assertEquals('Gravino GRV-NI1', $unit->getChassis());
        $this->assertEquals(35, $unit->getTonnage());
        $this->assertEquals(75, $unit->getBv());
        $this->assertEquals(UnitType::Mech, $unit->getUnitType());
    }

    public function testAcquireMechSucceedsWhenContractIdIsNull(): void
    {
        $mechan = $this->makeSalvagedMech();
        $company = $this->makeCompany();

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertNull($mechan->getContractId());
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
        $this->expectExceptionMessage('Salvaged Mech must have a valid BV cost or salvage value.');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechThrowsWhenBvCostIsZero(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 0]);
        $company = $this->makeCompany();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Salvaged Mech must have a valid BV cost or salvage value.');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechThrowsWhenBvCostIsNegative(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => -50]);
        $company = $this->makeCompany();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Salvaged Mech must have a valid BV cost or salvage value.');

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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechFailsWhenInsufficientSupportPoints(): void
    {
        // baseSalvage = floor(800/2) = 400; balance 300 < 400 triggers exception
        $mechan = $this->makeSalvagedMech(['bvCost' => 800]);
        $company = $this->makeCompany(300);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient support points/');

        $this->service->acquireMech($mechan, $company);

        $this->assertNull($mechan->getContractId());
    }

    public function testAcquireMechDoesNotPersistOrRemoveWhenInsufficientFunds(): void
    {
        // baseSalvage = floor(800/2) = 400; balance 300 < 400 triggers exception
        $mechan = $this->makeSalvagedMech(['bvCost' => 800]);
        $company = $this->makeCompany(300);

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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        // When model is null, the ?? 'Unknown Chassis' fallback should apply
        $this->assertCount(1, $captured);
        $this->assertEquals('Unknown Chassis', $captured[0]->getChassis());
    }

    public function testAcquireMechHandlesZeroTonnage(): void
    {
        $mechan = $this->makeSalvagedMech(['tonnage' => 0, 'bvCost' => 200]);
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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        // When tonnage is 0, the ?? 0 fallback applies
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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertEquals(499, $captured[0]->getBv());
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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertEquals(UnitType::Mech, $captured[0]->getUnitType());
    }

    // ── Already Acquired Mech ─────────────────────────────────────────────

    public function testAcquireMechThrowsWhenContractIdIsSet(): void
    {
        $mechan = $this->makeSalvagedMech();
        $mechan->setContractId(42);
        $company = $this->makeCompany();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This mech has already been acquired.');

        $this->service->acquireMech($mechan, $company);

        $this->em->expects($this->never())
            ->method('persist');

        $this->em->expects($this->never())
            ->method('flush');
    }

    // ── Low Balance Edge Case ──────────────────────────────────────────────

    public function testAcquireMechSucceedsWhenBalanceEqualsBvCost(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 500]);
        $company = $this->makeCompany(500);

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechFailsWhenBalanceIsOneLessThanBvCost(): void
    {
        // baseSalvage = floor(500/2) = 250; balance 249 < 250 triggers exception
        $mechan = $this->makeSalvagedMech(['bvCost' => 500]);
        $company = $this->makeCompany(249);

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

    // ── Scrapyard Tests ────────────────────────────────────────────────────

    private function makeScrapyardMech(array $overrides = []): SalvagedMech
    {
        $defaults = array_merge([
            'model' => 'Catapult CAT-PU1',
            'tonnage' => 80,
            'bvCost' => 300,
            'scrapyard' => true,
        ], $overrides);
        return $this->makeSalvagedMech($defaults);
    }

    public function testAcquireMechScrapyardSetsCrippledDamageState(): void
    {
        $mechan = $this->makeScrapyardMech();
        $company = $this->makeCompany();

        $this->salvageCalc->method('calculateSalvageValue')->willReturn(150);

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->never())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertEquals(DamageState::Crippled, $captured[0]->getDamageState());
    }

    public function testAcquireMechScrapyardUsesHalfBVAsCost(): void
    {
        $mechan = $this->makeScrapyardMech(['bvCost' => 400]);
        $company = $this->makeCompany();

        $this->salvageCalc->method('calculateSalvageValue')->willReturn(200);

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->never())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertEquals(200, $captured[0]->getBv());
    }

    public function testAcquireMechScrapyardDoesNotRemoveSalvagedMech(): void
    {
        $mechan = $this->makeScrapyardMech();
        $company = $this->makeCompany();

        $this->salvageCalc->method('calculateSalvageValue')->willReturn(150);

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->never())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertNull($mechan->getContractId());
    }

    public function testAcquireMechScrapyardWithNullBvCost(): void
    {
        $mechan = $this->makeScrapyardMech(['bvCost' => null]);
        $company = $this->makeCompany();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Salvaged Mech must have a valid BV cost or salvage value.');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechScrapyardWithInsufficientFunds(): void
    {
        $mechan = $this->makeScrapyardMech(['bvCost' => 1000]);
        $company = $this->makeCompany(400);

        $this->salvageCalc->method('calculateSalvageValue')->willReturn(500);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Insufficient support points/');

        $this->service->acquireMech($mechan, $company);

        $this->assertNull($mechan->getContractId());
    }

    public function testAcquireMechNonScrapyardStillRemovesSalvagedMech(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 300]);
        $company = $this->makeCompany();

        $this->em->expects($this->once())
            ->method('persist');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertNull($mechan->getContractId());
    }

    public function testAcquireMechNonScrapyardUsesSalvageValueWhenSet(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 500]);
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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertEquals(250, $captured[0]->getBv());
    }

    public function testAcquireMechNonScrapyardFallsBackToBvCostWhenNoSalvageValue(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 400]);
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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertEquals(200, $captured[0]->getBv());
    }

    public function testAcquireMechNonScrapyardSetsNoneDamageState(): void
    {
        $mechan = $this->makeSalvagedMech(['bvCost' => 300]);
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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        $this->assertCount(1, $captured);
        $this->assertEquals(DamageState::None, $captured[0]->getDamageState());
    }

    public function testAcquireMechScrapyardAddsScrapyardLabelToDeduction(): void
    {
        $mechan = $this->makeScrapyardMech(['model' => 'Gravino GRV-NI1']);
        $company = $this->createMock(MercenaryCompany::class);

        $this->salvageCalc->method('calculateSalvageValue')->willReturn(150);

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->never())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $company->expects($this->once())
            ->method('deductSupportPoints')
            ->with(150, 'Acquisition of Gravino GRV-NI1 (Scrapyard)');

        $this->service->acquireMech($mechan, $company);
    }

    // ── Repair Cost Tests (P1 Bug Fix) ────────────────────────────────────

    public function testAcquireMechSetsUnitBvToBaseCostNotIncludingRepairCost(): void
    {
        $mechan = $this->makeSalvagedMech([
            'bvCost' => 300,
            'repairCost' => 50,
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
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        // Unit.bv should be base cost (300), NOT base + repair (350)
        $this->assertCount(1, $captured);
        $this->assertEquals(150, $captured[0]->getBv());
    }

    public function testAcquireMechDeductsRepairCostFromSupportPoints(): void
    {
        $mechan = $this->makeSalvagedMech([
            'bvCost' => 300,
            'model' => 'Gravino GRV-NI1',
            'repairCost' => 75,
        ]);
        $company = $this->createMock(MercenaryCompany::class);

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->once())
            ->method('flush');

        // SP deduction should be baseSalvage + repair = 150 + 75 = 225
        $company->expects($this->once())
            ->method('deductSupportPoints')
            ->with(225, 'Acquisition of Gravino GRV-NI1 (includes 75 SP repair)');

        $this->service->acquireMech($mechan, $company);
    }

    public function testAcquireMechScrapyardSetsUnitBvToBaseCostNotIncludingRepairCost(): void
    {
        $mechan = $this->makeScrapyardMech([
            'bvCost' => 400,
            'repairCost' => 100,
        ]);
        $company = $this->makeCompany();

        $this->salvageCalc->method('calculateSalvageValue')->willReturn(200);

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->never())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        $this->service->acquireMech($mechan, $company);

        // Unit.bv should be base cost (200 = half of 400), NOT base + repair (300)
        $this->assertCount(1, $captured);
        $this->assertEquals(200, $captured[0]->getBv());
    }

    public function testAcquireMechDeductsScrapyardBasePlusRepairFromSupportPoints(): void
    {
        $mechan = $this->makeScrapyardMech([
            'bvCost' => 400,
            'model' => 'Gravino GRV-NI1',
            'repairCost' => 100,
        ]);
        $company = $this->createMock(MercenaryCompany::class);

        $this->salvageCalc->method('calculateSalvageValue')->willReturn(200);

        $captured = [];
        $this->em
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$captured) {
                if ($obj instanceof Unit) {
                    $captured[] = $obj;
                }
            });

        $this->em->expects($this->never())
            ->method('remove');

        $this->em->expects($this->once())
            ->method('flush');

        // SP deduction should be base (200) + repair (100) = 300
        $company->expects($this->once())
            ->method('deductSupportPoints')
            ->with(300, 'Acquisition of Gravino GRV-NI1 (Scrapyard) (includes 100 SP repair)');

        $this->service->acquireMech($mechan, $company);
    }
}
