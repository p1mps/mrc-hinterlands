<?php

namespace App\Tests\Unit\Service;

use App\Enum\DamageState;
use App\Service\DiceRoller;
use App\Service\SalvageCheckService;
use PHPUnit\Framework\TestCase;

class SalvageCheckServiceTest extends TestCase
{
    private SalvageCheckService $service;
    private DiceRoller $dice;

    protected function setUp(): void
    {
        $this->dice = new DiceRoller();
        $this->service = new SalvageCheckService($this->dice);
    }

    public function testRollSalvageCheckReturnsValidRange(): void
    {
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = $this->service->rollSalvageCheck();
        }
        $this->assertGreaterThanOrEqual(2, min($results));
        $this->assertLessThanOrEqual(12, max($results));
    }

    public function testIsTrulyDestroyedWithDestroyedDamageState(): void
    {
        $this->assertTrue($this->service->isTrulyDestroyed(DamageState::Destroyed));
    }

    public function testIsTrulyDestroyedWithCrippledDamageState(): void
    {
        $this->assertFalse($this->service->isTrulyDestroyed(DamageState::Crippled));
    }

    public function testIsTrulyDestroyedWithStructuralDamageState(): void
    {
        $this->assertFalse($this->service->isTrulyDestroyed(DamageState::Structural));
    }

    public function testIsTrulyDestroyedWithNoneDamageState(): void
    {
        $this->assertFalse($this->service->isTrulyDestroyed(DamageState::None));
    }

    public function testIsTrulyDestroyedWithNullDamageState(): void
    {
        $this->assertFalse($this->service->isTrulyDestroyed(null));
    }

    public function testGetSalvageCheckThresholdForMechanical(): void
    {
        $this->assertEquals(4, $this->service->getSalvageCheckThreshold('mechanical'));
    }

    public function testGetSalvageCheckThresholdForVehicle(): void
    {
        $this->assertEquals(6, $this->service->getSalvageCheckThreshold('vehicle'));
    }

    public function testGetSalvageCheckThresholdForBattleArmor(): void
    {
        $this->assertEquals(7, $this->service->getSalvageCheckThreshold('battle_armor'));
    }

    public function testGetSalvageCheckThresholdCaseInsensitive(): void
    {
        $this->assertEquals(6, $this->service->getSalvageCheckThreshold('VEHICLE'));
        $this->assertEquals(7, $this->service->getSalvageCheckThreshold('BATTLE_ARMOR'));
        $this->assertEquals(4, $this->service->getSalvageCheckThreshold('MeChAnIcAl'));
    }

    public function testGetSalvageCheckThresholdUnknownType(): void
    {
        $this->assertEquals(4, $this->service->getSalvageCheckThreshold('unknown_type'));
    }
}
