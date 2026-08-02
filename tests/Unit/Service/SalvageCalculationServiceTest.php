<?php

namespace App\Tests\Unit\Service;

use App\Enum\DamageState;
use App\Enum\TechBase;
use App\Service\SalvageCalculationService;
use PHPUnit\Framework\TestCase;

class SalvageCalculationServiceTest extends TestCase
{
    private SalvageCalculationService $service;

    protected function setUp(): void
    {
        $this->service = new SalvageCalculationService();
    }

    // ── calculateSalvageValue ──────────────────────────────────────────────

    public function testCalculateSalvageValueWithNullBvCost(): void
    {
        $this->assertNull($this->service->calculateSalvageValue(null));
    }

    public function testCalculateSalvageValueWithZeroBvCost(): void
    {
        $this->assertNull($this->service->calculateSalvageValue(0));
    }

    public function testCalculateSalvageValueWithNegativeBvCost(): void
    {
        $this->assertNull($this->service->calculateSalvageValue(-100));
    }

    public function testCalculateSalvageValueWithOddBv(): void
    {
        $this->assertEquals(3, $this->service->calculateSalvageValue(7));
    }

    public function testCalculateSalvageValueWithEvenBv(): void
    {
        $this->assertEquals(5, $this->service->calculateSalvageValue(10));
    }

    public function testCalculateSalvageValueWithLargeBv(): void
    {
        $this->assertEquals(2500, $this->service->calculateSalvageValue(5000));
    }

    public function testCalculateSalvageValueWithSmallOddBv(): void
    {
        $this->assertEquals(1, $this->service->calculateSalvageValue(3));
    }

    // ── calculateRepairCost ────────────────────────────────────────────────

    public function testCalculateRepairCostWithNullTonnage(): void
    {
        $this->assertNull($this->service->calculateRepairCost(null, DamageState::Structural, TechBase::IS));
    }

    public function testCalculateRepairCostWithZeroTonnage(): void
    {
        $this->assertNull($this->service->calculateRepairCost(0, DamageState::Structural, TechBase::IS));
    }

    public function testCalculateRepairCostWithNoneDamage(): void
    {
        $this->assertEquals(0, $this->service->calculateRepairCost(50, DamageState::None, TechBase::IS));
    }

    public function testCalculateRepairCostWithNullDamageState(): void
    {
        $this->assertEquals(0, $this->service->calculateRepairCost(50, null, TechBase::IS));
    }

    public function testCalculateRepairCostWithStructuralIS(): void
    {
        // 10t * 2.0 = 20
        $this->assertEquals(20, $this->service->calculateRepairCost(10, DamageState::Structural, TechBase::IS));
    }

    public function testCalculateRepairCostWithStructuralClan(): void
    {
        // 10t * 3.0 = 30
        $this->assertEquals(30, $this->service->calculateRepairCost(10, DamageState::Structural, TechBase::Clan));
    }

    public function testCalculateRepairCostWithStructuralMixed(): void
    {
        // 10t * 3.0 = 30 (Mixed = 1.5x IS base)
        $this->assertEquals(30, $this->service->calculateRepairCost(10, DamageState::Structural, TechBase::Mixed));
    }

    public function testCalculateRepairCostWithCrippledIS(): void
    {
        // 10t * 3.0 = 30
        $this->assertEquals(30, $this->service->calculateRepairCost(10, DamageState::Crippled, TechBase::IS));
    }

    public function testCalculateRepairCostWithCrippledClan(): void
    {
        // 10t * 4.5 = 45
        $this->assertEquals(45, $this->service->calculateRepairCost(10, DamageState::Crippled, TechBase::Clan));
    }

    public function testCalculateRepairCostWithCrippledMixed(): void
    {
        // 20t * 1.5 * 3.0 = 90
        $this->assertEquals(90, $this->service->calculateRepairCost(20, DamageState::Crippled, TechBase::Mixed));
    }

    public function testCalculateRepairCostWithDestroyedIS(): void
    {
        // 30t * 5.0 = 150
        $this->assertEquals(150, $this->service->calculateRepairCost(30, DamageState::Destroyed, TechBase::IS));
    }

    public function testCalculateRepairCostWithDestroyedClan(): void
    {
        // 20t * 7.5 = 150
        $this->assertEquals(150, $this->service->calculateRepairCost(20, DamageState::Destroyed, TechBase::Clan));
    }

    public function testCalculateRepairCostWithNullTechBaseDefaultsToIS(): void
    {
        // 10t * 2.0 = 20 (null tech = IS)
        $this->assertEquals(20, $this->service->calculateRepairCost(10, DamageState::Structural, null));
    }

    public function testCalculateRepairCostWithArmorOnlyIS(): void
    {
        // 20t * 0.5 = 10
        $this->assertEquals(10, $this->service->calculateRepairCost(20, DamageState::ArmorOnly, TechBase::IS));
    }

    public function testCalculateRepairCostWithArmorOnlyClan(): void
    {
        // 20t * 0.75 = 15
        $this->assertEquals(15, $this->service->calculateRepairCost(20, DamageState::ArmorOnly, TechBase::Clan));
    }

    // ── calculateAcquisitionCost ───────────────────────────────────────────

    public function testCalculateAcquisitionCostWithNullSalvageValue(): void
    {
        $this->assertNull($this->service->calculateAcquisitionCost(null, 30));
    }

    public function testCalculateAcquisitionCostWithZeroPercent(): void
    {
        // 100 * (1 - 0/100) = 100
        $this->assertEquals(100, $this->service->calculateAcquisitionCost(100, 0));
    }

    public function testCalculateAcquisitionCostWithThirtyThreePercent(): void
    {
        // floor(100 * 0.67) = 67
        $this->assertEquals(67, $this->service->calculateAcquisitionCost(100, 33));
    }

    public function testCalculateAcquisitionCostWithHundredPercent(): void
    {
        // 100 * (1 - 100/100) = 0
        $this->assertEquals(0, $this->service->calculateAcquisitionCost(100, 100));
    }

    public function testCalculateAcquisitionCostWithNullPercent(): void
    {
        // null percent treated as 0% (full value)
        $this->assertEquals(100, $this->service->calculateAcquisitionCost(100, null));
    }

    public function testCalculateAcquisitionCostWithFiftyPercent(): void
    {
        // 100 * 0.5 = 50
        $this->assertEquals(50, $this->service->calculateAcquisitionCost(100, 50));
    }

    // ── calculateSpPayout ──────────────────────────────────────────────────

    public function testCalculateSpPayoutWithNullSalvageValue(): void
    {
        $this->assertNull($this->service->calculateSpPayout(null, 50));
    }

    public function testCalculateSpPayoutWithNullPercent(): void
    {
        // Exchange path: 100 * 0.25 = 25
        $this->assertEquals(25, $this->service->calculateSpPayout(100, null));
    }

    public function testCalculateSpPayoutWithZeroPercent(): void
    {
        // 100 * 0/100 = 0
        $this->assertEquals(0, $this->service->calculateSpPayout(100, 0));
    }

    public function testCalculateSpPayoutWithThirtyThreePercent(): void
    {
        // floor(100 * 33/100) = 33
        $this->assertEquals(33, $this->service->calculateSpPayout(100, 33));
    }

    public function testCalculateSpPayoutWithHundredPercent(): void
    {
        // 100 * 100/100 = 100
        $this->assertEquals(100, $this->service->calculateSpPayout(100, 100));
    }

    public function testCalculateSpPayoutWithNullPercentOnSmallValue(): void
    {
        // Exchange: floor(50 * 0.25) = 12
        $this->assertEquals(12, $this->service->calculateSpPayout(50, null));
    }

    // ── isAcquisitionAllowed ───────────────────────────────────────────────

    public function testIsAcquisitionAllowedWithNullPercent(): void
    {
        // null = "Exchange" → not allowed
        $this->assertFalse($this->service->isAcquisitionAllowed(null));
    }

    public function testIsAcquisitionAllowedWithZeroPercent(): void
    {
        // 0 = "None" → not allowed
        $this->assertFalse($this->service->isAcquisitionAllowed(0));
    }

    public function testIsAcquisitionAllowedWithPositivePercent(): void
    {
        $this->assertTrue($this->service->isAcquisitionAllowed(3));
    }

    public function testIsAcquisitionAllowedWithFiftyPercent(): void
    {
        $this->assertTrue($this->service->isAcquisitionAllowed(50));
    }
}
