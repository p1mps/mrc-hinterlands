<?php

namespace App\Tests\Unit\Service;

use App\Service\SalvageCalculationService;
use App\Service\RansomService;
use PHPUnit\Framework\TestCase;

class RansomServiceTest extends TestCase
{
    private RansomService $service;

    protected function setUp(): void
    {
        $salvageCalc = new SalvageCalculationService();
        $this->service = new RansomService($salvageCalc);
    }

    public function testCalculateMechRansomCostWithNullSalvageValue(): void
    {
        $this->assertNull($this->service->calculateMechRansomCost(null));
    }

    public function testCalculateMechRansomCostWithPositiveSalvageValue(): void
    {
        $this->assertEquals(100, $this->service->calculateMechRansomCost(100));
    }

    public function testCalculateMechRansomCostWithZeroSalvageValue(): void
    {
        $this->assertEquals(0, $this->service->calculateMechRansomCost(0));
    }

    public function testCalculatePilotRansomCostWithHighSkill(): void
    {
        // gunnery=4, piloting=5 → total=9 → (10-9)*100 = 100
        $this->assertEquals(100, $this->service->calculatePilotRansomCost(4, 5));
    }

    public function testCalculatePilotRansomCostWithMaxSkill(): void
    {
        // gunnery=5, piloting=5 → total=10 → (10-10)*100 = 0
        $this->assertEquals(0, $this->service->calculatePilotRansomCost(5, 5));
    }

    public function testCalculatePilotRansomCostWithLowSkill(): void
    {
        // gunnery=0, piloting=0 → total=0 → (10-0)*100 = 1000
        $this->assertEquals(1000, $this->service->calculatePilotRansomCost(0, 0));
    }

    public function testCalculatePilotRansomCostWithExactTen(): void
    {
        // gunnery=5, piloting=5 → total=10 → 0
        $this->assertEquals(0, $this->service->calculatePilotRansomCost(5, 5));
    }

    public function testCalculatePilotRansomCostWithMidSkill(): void
    {
        // gunnery=3, piloting=3 → total=6 → (10-6)*100 = 400
        $this->assertEquals(400, $this->service->calculatePilotRansomCost(3, 3));
    }

    public function testIsRansomAllowedForActOfPiracy(): void
    {
        $this->assertFalse($this->service->isRansomAllowed('act_of_piracy'));
    }

    public function testIsRansomAllowedForEscort(): void
    {
        $this->assertTrue($this->service->isRansomAllowed('escort'));
    }

    public function testIsRansomAllowedForRecon(): void
    {
        $this->assertTrue($this->service->isRansomAllowed('recon'));
    }

    public function testIsRansomAllowedForExpedition(): void
    {
        $this->assertTrue($this->service->isRansomAllowed('expedition'));
    }
}
