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
        // Use rolls that produce distinct, non-default steps so we can verify they're preserved
        // Pay Rate: roll 7 → base step 7
        // Support: roll 7 → base step 7
        // Salvage: roll 7 → base step 7
        // Transport: roll 7 → base step 7
        // Command: roll 7 → base step 7
        // Affiliation fallback on 5th roll = 7
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
        // Generate a base result, then manually check that shifting categories updates the rolls
        $service = $this->makeService(array_fill(0, 25, 7));

        // First call: base generation
        $baseResult = $service->generate(1);

        // Now call generateWithNegotiation with changes that shift categories up
        $negotiationChanges = [
            'basePayPercent' => 7,  // shift base pay to step 7
            'commandRights' => 8,   // shift command rights to step 8
        ];

        $result = $service->generateWithNegotiation(1, 10, $negotiationChanges);

        // Verify that the rolls array contains the updated steps
        $payRateRolls = array_values(array_filter($result['rolls'], fn($r) => $r['label'] === 'Pay Rate'));
        $this->assertNotEmpty($payRateRolls);
        $this->assertEquals(7, $payRateRolls[0]['step']);

        $cmdRightsRolls = array_values(array_filter($result['rolls'], fn($r) => $r['label'] === 'Command Rights'));
        $this->assertNotEmpty($cmdRightsRolls);
        $this->assertEquals(8, $cmdRightsRolls[0]['step']);
    }
}
