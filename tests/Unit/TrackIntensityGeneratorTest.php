<?php

namespace App\Tests\Unit;

use App\Service\TrackIntensityGenerator;
use PHPUnit\Framework\TestCase;

class TrackIntensityGeneratorTest extends TestCase
{
    private TrackIntensityGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new TrackIntensityGenerator();
    }

    #[\PHPUnit\Framework\TestWith([3, 1])]
    #[\PHPUnit\Framework\TestWith([3, 2])]
    #[\PHPUnit\Framework\TestWith([3, 3])]
    #[\PHPUnit\Framework\TestWith([6, 1])]
    #[\PHPUnit\Framework\TestWith([6, 2])]
    #[\PHPUnit\Framework\TestWith([6, 3])]
    #[\PHPUnit\Framework\TestWith([6, 4])]
    #[\PHPUnit\Framework\TestWith([6, 5])]
    #[\PHPUnit\Framework\TestWith([6, 6])]
    public function testGenerateReturnsValidIntensityString(int $months, int $tracks): void
    {
        // Run multiple times to test different random rolls
        for ($i = 0; $i < 100; $i++) {
            $intensity = $this->generator->generate($months, $tracks);
            $parts = explode('-', $intensity);
            
            // Sum of parts must equal total tracks
            $totalTracks = array_sum(array_map('intval', $parts));
            $this->assertEquals($tracks, $totalTracks, "Intensity string '{$intensity}' for {$months}-month/{$tracks}-track contract does not sum to {$tracks}.");

            // Number of parts must equal contract duration
            $this->assertCount($months, $parts, "Intensity string '{$intensity}' for {$months}-month contract does not have {$months} parts.");
        }
    }

    public function testInvalidDurationThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->generate(12, 4);
    }

    public function testInvalidTracksFor3MonthContractThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->generate(3, 4); // 3-month contracts only support 1-3 tracks
    }

    public function testInvalidTracksFor6MonthContractThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->generate(6, 7); // 6-month contracts only support 1-6 tracks
    }
}
