<?php
namespace App\Tests\Unit\DataTables;

use App\DataTables\ContractStepsTable;
use PHPUnit\Framework\TestCase;

class ContractStepsTableTest extends TestCase {
    public function testStep7BasePayIs100(): void {
        $this->assertEquals(100, ContractStepsTable::getBasePayPercent(7));
    }

    public function testStep13BasePayIs200(): void {
        $this->assertEquals(200, ContractStepsTable::getBasePayPercent(13));
    }

    public function testStep1BasePayIsNull(): void {
        $this->assertEquals(50, ContractStepsTable::getBasePayPercent(1));
    }

    public function testStep3SupportTerms(): void {
        $this->assertEquals('Straight/40%', ContractStepsTable::getSupportTerms(3));
    }

    public function testStep7SalvageRights(): void {
        $this->assertEquals('40%', ContractStepsTable::getSalvageRights(7));
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

    public function testStep7CommandRightsIsHouse(): void {
        $this->assertEquals(\App\Enum\CommandRights::House, ContractStepsTable::getCommandRights(7));
    }

    public function testStep11CommandRightsIsIndependent(): void {
        $this->assertEquals(\App\Enum\CommandRights::Independent, ContractStepsTable::getCommandRights(11));
    }

    public function testStep3CommandRightsIsIntegrated(): void {
        $this->assertEquals(\App\Enum\CommandRights::Integrated, ContractStepsTable::getCommandRights(3));
    }

    public function testStep1HasNoCommandRights(): void {
        $this->assertNull(ContractStepsTable::getCommandRights(1));
    }

    public function testStep2SalvageRightsIsNull(): void {
        $this->assertEquals('None', ContractStepsTable::getSalvageRights(2));
    }

    public function testStep13TransportTermsIsNull(): void {
        $this->assertNull(ContractStepsTable::getTransportTerms(13));
    }

    public function testStep5TransportTerms(): void {
        $this->assertEquals('0%', ContractStepsTable::getTransportTerms(5));
    }

    public function testStep9TransportTerms(): void {
        $this->assertEquals('100%', ContractStepsTable::getTransportTerms(9));
    }

    public function testStep3SalvageRightsIsExchange(): void {
        $this->assertEquals('Exchange', ContractStepsTable::getSalvageRights(3));
    }

    public function testStep13SupportTermsIsBattle100Percent(): void {
        $this->assertEquals('Battle/100%', ContractStepsTable::getSupportTerms(13));
    }
}
