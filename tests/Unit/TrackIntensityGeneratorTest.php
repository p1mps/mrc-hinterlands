<?php

namespace App\Tests\Unit;

use App\Service\TrackIntensityGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrackIntensityGeneratorTest extends TestCase
{
    private TrackIntensityGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new TrackIntensityGenerator();
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function validIntensityCases(): iterable
    {
        return [
            '3-month 1-track' => [3, 1],
            '3-month 2-tracks' => [3, 2],
            '3-month 3-tracks' => [3, 3],
            '6-month 1-track' => [6, 1],
            '6-month 2-tracks' => [6, 2],
            '6-month 3-tracks' => [6, 3],
            '6-month 4-tracks' => [6, 4],
            '6-month 5-tracks' => [6, 5],
            '6-month 6-tracks' => [6, 6],
        ];
    }

    #[DataProvider('validIntensityCases')]
    public function testGenerateReturnsValidIntensityString(int $months, int $tracks): void
    {
        // Run multiple times to test different random rolls
        for ($i = 0; $i < 100; $i++) {
            $intensity = $this->generator->generate($months, $tracks);
            $parts = explode('-', $intensity);
            
            // Number of parts must equal contract duration (one intensity value per month)
            $this->assertCount($months, $parts, "Intensity string '{$intensity}' for {$months}-month contract does not have {$months} parts.");

            // Each part must be a non-negative integer
            foreach ($parts as $part) {
                $this->assertMatchesRegularExpression('/^\d+$/', $part, "Intensity part '{$part}' is not a non-negative integer.");
            }
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
