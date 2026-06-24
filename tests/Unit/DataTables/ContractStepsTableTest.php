<?php
namespace App\Tests\Unit\DataTables;

use App\DataTables\ContractStepsTable;
use PHPUnit\Framework\TestCase;

class ContractStepsTableTest extends TestCase {
    public function testStep7BasePayIs100(): void {
        $this->assertEquals(100, ContractStepsTable::getBasePayPercent(7));
    }

    public function testStep1BasePayIsNull(): void {
        $this->assertNull(ContractStepsTable::getBasePayPercent(1));
    }

    public function testStep5SupportTerms(): void {
        $this->assertEquals('Straight/80%', ContractStepsTable::getSupportTerms(5));
    }

    public function testStep7SalvageRights(): void {
        $this->assertEquals('30%', ContractStepsTable::getSalvageRights(7));
    }

    public function testStep8TransportTerms(): void {
        $this->assertEquals('75%', ContractStepsTable::getTransportTerms(8));
    }

    public function testClampStepBelowMinReturns1(): void {
        $this->assertEquals(1, ContractStepsTable::clampStep(0));
    }

    public function testClampStepAboveMaxReturns13(): void {
        $this->assertEquals(13, ContractStepsTable::clampStep(99));
    }
}
