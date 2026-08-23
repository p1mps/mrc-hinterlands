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
        $result  = $service->generateOpposing(ContractType::Garrison, 1, 3);
        $this->assertInstanceOf(ContractType::class, $result['type']);
    }

    public function testGenerateOpposingSetsIsOpposingTrue(): void {
        $service = $this->makeService(array_fill(0, 30, 7));
        $result  = $service->generateOpposing(ContractType::Garrison, 1, 3);
        $this->assertTrue($result['isOpposing']);
    }

    public function testGenerateOpposingUsesPrimaryNumberOfTracks(): void {
        $service = $this->makeService(array_fill(0, 30, 7));
        $result  = $service->generateOpposing(ContractType::Garrison, 1, 5);
        $this->assertEquals(5, $result['numberOfTracks']);
    }

    public function testScaleIsStoredInResult(): void {
        $service = $this->makeService(array_fill(0, 25, 7));
        $result  = $service->generate(2);
        $this->assertEquals(2, $result['scale']);
    }
    public function testRollTrackSetupReturnsRequiredKeys(): void
    {
        $service = $this->makeService(array_fill(0, 5, 3));
        $result  = $service->rollTrackSetup(ContractType::Raid, \App\Enum\CommandRights::Liaison);
        foreach (['missionType','terrain','terrainSetting','complication'] as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    public function testGenerateWithNegotiationReturnsRolls(): void
    {
        $service = $this->makeService(array_fill(0, 25, 7));
        $result  = $service->generateWithNegotiation(1, 5);
        $this->assertArrayHasKey('rolls', $result);
        $this->assertNotEmpty($result['rolls']);
    }

    public function testGenerateWithNegotiationRollsHaveCorrectSteps(): void
    {
        $rolls = array_fill(0, 25, 7);
        $service = $this->makeService($rolls);
        $result  = $service->generateWithNegotiation(1, 5);

        $rollLabels = [
            'Pay Rate' => 'basePayPercent',
            'Command Rights' => 'commandRights',
            'Salvage Rights' => 'salvageRights',
            'Support' => 'supportTerms',
            'Transportation' => 'transportTerms',
        ];

        foreach ($rollLabels as $label => $category) {
            $matchingRolls = array_values(array_filter($result['rolls'], fn($r) => $r['label'] === $label));
            $this->assertNotEmpty($matchingRolls, "Roll for {$label} should exist");
            $this->assertArrayHasKey('step', $matchingRolls[0], "Roll for {$label} should have a step key");
            $this->assertIsInt($matchingRolls[0]['step'], "Step for {$label} should be an integer");
            $this->assertGreaterThanOrEqual(1, $matchingRolls[0]['step']);
            $this->assertLessThanOrEqual(13, $matchingRolls[0]['step']);
        }
    }

    public function testGenerateWithNegotiationRollsReflectNegotiationChanges(): void
    {
        $service = $this->makeService(array_fill(0, 25, 7));
        $baseResult = $service->generate(1);

        $negotiationChanges = [
            'basePayPercent' => 7,
            'commandRights' => 8,
        ];

        $result = $service->generateWithNegotiation(1, 10, $negotiationChanges);

        $payRateRolls = array_values(array_filter($result['rolls'], fn($r) => $r['label'] === 'Pay Rate'));
        $this->assertNotEmpty($payRateRolls);
        $this->assertEquals(7, $payRateRolls[0]['step']);

        $cmdRightsRolls = array_values(array_filter($result['rolls'], fn($r) => $r['label'] === 'Command Rights'));
        $this->assertNotEmpty($cmdRightsRolls);
        $this->assertEquals(8, $cmdRightsRolls[0]['step']);
    }

    public function testRollOpposingTrackSetupInheritsMissionAndTerrain(): void
    {
        $linkedContract = $this->createStub(\App\Entity\Contract::class);
        $linkedContract->method('getTrackRecords')->willReturn(
            new \Doctrine\Common\Collections\ArrayCollection([
                $this->makeTrackRecord(1, 'Assault', 'Forest'),
            ])
        );
        $linkedContract->method('getType')->willReturn(\App\Enum\ContractType::Raid);

        $service = $this->makeService(array_fill(0, 5, 3));
        $result  = $service->rollOpposingTrackSetup($linkedContract, \App\Enum\CommandRights::Liaison);

        $this->assertEquals('Assault', $result['missionType']);
        $this->assertEquals('Forest', $result['terrain']);
        $this->assertTrue($result['inherited'] ?? false);
        $this->assertArrayHasKey('complication', $result);
        $this->assertArrayHasKey('complicationRoll', $result);
    }

    public function testRollOpposingTrackSetupFallsBackToFullRollWhenNoTracks(): void
    {
        $linkedContract = $this->createStub(\App\Entity\Contract::class);
        $linkedContract->method('getTrackRecords')->willReturn(
            new \Doctrine\Common\Collections\ArrayCollection([])
        );
        $linkedContract->method('getType')->willReturn(\App\Enum\ContractType::Raid);

        $service = $this->makeService(array_fill(0, 5, 3));
        $result  = $service->rollOpposingTrackSetup($linkedContract, \App\Enum\CommandRights::Liaison);

        $this->assertArrayHasKey('missionRoll', $result);
        $this->assertArrayHasKey('terrainRoll', $result);
        $this->assertArrayNotHasKey('inherited', $result);
    }

    public function testRollOpposingTrackSetupUsesLastTrack(): void
    {
        $linkedContract = $this->createStub(\App\Entity\Contract::class);
        $linkedContract->method('getTrackRecords')->willReturn(
            new \Doctrine\Common\Collections\ArrayCollection([
                $this->makeTrackRecord(1, 'Mission1', 'Terrain1'),
                $this->makeTrackRecord(2, 'Mission2', 'Terrain2'),
            ])
        );
        $linkedContract->method('getType')->willReturn(\App\Enum\ContractType::Raid);

        $service = $this->makeService(array_fill(0, 5, 3));
        $result  = $service->rollOpposingTrackSetup($linkedContract, \App\Enum\CommandRights::Liaison);

        $this->assertEquals('Mission2', $result['missionType']);
        $this->assertEquals('Terrain2', $result['terrain']);
    }

    private function makeTrackRecord(int $trackNumber, string $missionType, string $terrain): \App\Entity\TrackRecord
    {
        $track = $this->createStub(\App\Entity\TrackRecord::class);
        $track->method('getTrackNumber')->willReturn($trackNumber);
        $track->method('getMissionType')->willReturn($missionType);
        $track->method('getTerrain')->willReturn($terrain);
        return $track;
    }
}
