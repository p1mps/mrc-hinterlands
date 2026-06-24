<?php
namespace App\Tests\Unit\DataTables;

use App\DataTables\ContractTypeTable;
use App\Enum\ContractType;
use PHPUnit\Framework\TestCase;

class ContractTypeTableTest extends TestCase {
    public function testRoll2ReturnsExpedition(): void {
        $result = ContractTypeTable::lookup(2);
        $this->assertEquals(ContractType::Expedition, $result['type']);
        $this->assertEquals(6, $result['duration']);
    }

    public function testRoll7ReturnsRaid(): void {
        $result = ContractTypeTable::lookup(7);
        $this->assertEquals(ContractType::Raid, $result['type']);
        $this->assertEquals(3, $result['duration']);
    }

    public function testRoll12ReturnsInvasion(): void {
        $result = ContractTypeTable::lookup(12);
        $this->assertEquals(ContractType::Invasion, $result['type']);
    }

    public function testOpposingForGarrisonRoll2ReturnsExpedition(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Garrison, 2);
        $this->assertEquals(ContractType::Expedition, $result);
    }

    public function testOpposingForGarrisonRoll12ReturnsInvasion(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Garrison, 12);
        $this->assertEquals(ContractType::Invasion, $result);
    }

    public function testInvalidRollThrows(): void {
        $this->expectException(\InvalidArgumentException::class);
        ContractTypeTable::lookup(1);
    }
}
