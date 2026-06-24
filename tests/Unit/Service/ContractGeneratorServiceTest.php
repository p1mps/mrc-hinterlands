<?php
namespace App\Tests\Unit\Service;

use App\Enum\ContractType;
use App\Service\ContractGeneratorService;
use App\Service\DiceRoller;
use PHPUnit\Framework\TestCase;

class ContractGeneratorServiceTest extends TestCase {
    private function makeService(array $rolls): ContractGeneratorService {
        $i    = 0;
        $mock = $this->createStub(DiceRoller::class);
        $mock->method('roll')->willReturnCallback(function() use (&$i, $rolls) {
            return $rolls[$i++] ?? 7;
        });
        return new ContractGeneratorService($mock);
    }

    public function testGenerateReturnsRequiredKeys(): void {
        $service = $this->makeService(array_fill(0, 25, 7));
        $result  = $service->generate(1);
        foreach (['type','employer','affiliation','basePayPercent','commandRights','supportTerms','salvageRights','transportTerms','numberOfTracks','rolls'] as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    public function testRollsAreRecordedInResult(): void {
        $service = $this->makeService(array_fill(0, 25, 7));
        $result  = $service->generate(1);
        $this->assertNotEmpty($result['rolls']);
    }

    public function testGenerateOpposingReturnsValidType(): void {
        $service = $this->makeService(array_fill(0, 30, 7));
        $result  = $service->generateOpposing(ContractType::Garrison, 1);
        $this->assertInstanceOf(ContractType::class, $result['type']);
    }

    public function testScaleIsStoredInResult(): void {
        $service = $this->makeService(array_fill(0, 25, 7));
        $result  = $service->generate(2);
        $this->assertEquals(2, $result['scale']);
    }

    public function testRollTrackSetupReturnsRequiredKeys(): void {
        $service = $this->makeService(array_fill(0, 5, 3));
        $result  = $service->rollTrackSetup(ContractType::Raid, \App\Enum\CommandRights::Liaison);
        foreach (['missionType','terrain','terrainSetting','complication'] as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }
}
