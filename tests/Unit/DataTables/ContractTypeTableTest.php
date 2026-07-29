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

    public function testOpposingForExpeditionRoll2ReturnsGarrison(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Expedition, 2);
        $this->assertEquals(ContractType::Garrison, $result);
    }

    public function testOpposingForExpeditionRoll7ReturnsGarrison(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Expedition, 7);
        $this->assertEquals(ContractType::Garrison, $result);
    }

    public function testOpposingForExpeditionRoll8ReturnsRaid(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Expedition, 8);
        $this->assertEquals(ContractType::Raid, $result);
    }

    public function testOpposingForExpeditionRoll10ReturnsRaid(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Expedition, 10);
        $this->assertEquals(ContractType::Raid, $result);
    }

    public function testOpposingForExpeditionRoll11ReturnsRetainer(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Expedition, 11);
        $this->assertEquals(ContractType::Retainer, $result);
    }

    public function testOpposingForExpeditionRoll12ReturnsRetainer(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Expedition, 12);
        $this->assertEquals(ContractType::Retainer, $result);
    }

    public function testOpposingForInvasionRoll2ReturnsGarrison(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Invasion, 2);
        $this->assertEquals(ContractType::Garrison, $result);
    }

    public function testOpposingForInvasionRoll5ReturnsGarrison(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Invasion, 5);
        $this->assertEquals(ContractType::Garrison, $result);
    }

    public function testOpposingForInvasionRoll6ReturnsRetainer(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Invasion, 6);
        $this->assertEquals(ContractType::Retainer, $result);
    }

    public function testOpposingForInvasionRoll8ReturnsRetainer(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Invasion, 8);
        $this->assertEquals(ContractType::Retainer, $result);
    }

    public function testOpposingForInvasionRoll9ReturnsRaid(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Invasion, 9);
        $this->assertEquals(ContractType::Raid, $result);
    }

    public function testOpposingForInvasionRoll10ReturnsInvasion(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Invasion, 10);
        $this->assertEquals(ContractType::Invasion, $result);
    }

    public function testOpposingForInvasionRoll12ReturnsInvasion(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Invasion, 12);
        $this->assertEquals(ContractType::Invasion, $result);
    }

    public function testOpposingForRaidRoll2ReturnsExpedition(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Raid, 2);
        $this->assertEquals(ContractType::Expedition, $result);
    }

    public function testOpposingForRaidRoll5ReturnsExpedition(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Raid, 5);
        $this->assertEquals(ContractType::Expedition, $result);
    }

    public function testOpposingForRaidRoll6ReturnsGarrison(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Raid, 6);
        $this->assertEquals(ContractType::Garrison, $result);
    }

    public function testOpposingForRaidRoll8ReturnsGarrison(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Raid, 8);
        $this->assertEquals(ContractType::Garrison, $result);
    }

    public function testOpposingForRaidRoll9ReturnsRaid(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Raid, 9);
        $this->assertEquals(ContractType::Raid, $result);
    }

    public function testOpposingForRaidRoll10ReturnsRetainer(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Raid, 10);
        $this->assertEquals(ContractType::Retainer, $result);
    }

    public function testOpposingForRaidRoll11ReturnsRetainer(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Raid, 11);
        $this->assertEquals(ContractType::Retainer, $result);
    }

    public function testOpposingForRaidRoll12ReturnsInvasion(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Raid, 12);
        $this->assertEquals(ContractType::Invasion, $result);
    }

    public function testOpposingForRetainerRoll2ReturnsExpedition(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Retainer, 2);
        $this->assertEquals(ContractType::Expedition, $result);
    }

    public function testOpposingForRetainerRoll5ReturnsExpedition(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Retainer, 5);
        $this->assertEquals(ContractType::Expedition, $result);
    }

    public function testOpposingForRetainerRoll6ReturnsRaid(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Retainer, 6);
        $this->assertEquals(ContractType::Raid, $result);
    }

    public function testOpposingForRetainerRoll7ReturnsRaid(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Retainer, 7);
        $this->assertEquals(ContractType::Raid, $result);
    }

    public function testOpposingForRetainerRoll8ReturnsRetainer(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Retainer, 8);
        $this->assertEquals(ContractType::Retainer, $result);
    }

    public function testOpposingForRetainerRoll9ReturnsRetainer(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Retainer, 9);
        $this->assertEquals(ContractType::Retainer, $result);
    }

    public function testOpposingForRetainerRoll10ReturnsInvasion(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Retainer, 10);
        $this->assertEquals(ContractType::Invasion, $result);
    }

    public function testOpposingForRetainerRoll12ReturnsInvasion(): void {
        $result = ContractTypeTable::lookupOpposing(ContractType::Retainer, 12);
        $this->assertEquals(ContractType::Invasion, $result);
    }

    public function testInvalidRollThrows(): void {
        $this->expectException(\InvalidArgumentException::class);
        ContractTypeTable::lookup(1);
    }
}
