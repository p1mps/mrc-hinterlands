<?php

namespace App\Tests\Unit\Service;

use App\Service\TrackIntensityGenerator;
use PHPUnit\Framework\TestCase;

class TrackIntensityGeneratorTest extends TestCase
{
    private TrackIntensityGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new TrackIntensityGenerator();
    }

    // ── 3-Month Contract Tests ────────────────────────────────────────────

    public function testGenerateWithThreeMonthsReturnsValidIntensity(): void
    {
        // We can't control random_int, but we verify it returns a valid string
        $result = $this->generator->generate(3, 1);
        $this->assertIsString($result);
        // Valid intensity strings follow the pattern "0-1-0", "0-1-1", etc.
        $this->assertMatchesRegularExpression('/^\d+-\d+-\d+$/', $result);
    }

    public function testGenerateWithThreeMonthsAndThreeTracksReturnsValidIntensity(): void
    {
        $result = $this->generator->generate(3, 3);
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d+-\d+-\d+$/', $result);
    }

    public function testGenerateWithThreeMonthsThrowsForInvalidTracks(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid number of tracks/');
        $this->generator->generate(3, 4);
    }

    public function testGenerateWithThreeMonthsThrowsForZeroTracks(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid number of tracks/');
        $this->generator->generate(3, 0);
    }

    // ── 6-Month Contract Tests ────────────────────────────────────────────

    public function testGenerateWithSixMonthsReturnsValidIntensity(): void
    {
        $result = $this->generator->generate(6, 1);
        $this->assertIsString($result);
        // 6-month intensity has 6 numbers: "0-0-1-0-0-0"
        $this->assertMatchesRegularExpression('/^\d+-\d+-\d+-\d+-\d+-\d+$/', $result);
    }

    public function testGenerateWithSixMonthsAndSixTracksReturnsValidIntensity(): void
    {
        $result = $this->generator->generate(6, 6);
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d+-\d+-\d+-\d+-\d+-\d+$/', $result);
    }

    public function testGenerateWithSixMonthsThrowsForInvalidTracks(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid number of tracks/');
        $this->generator->generate(6, 7);
    }

    public function testGenerateWithSixMonthsThrowsForZeroTracks(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid number of tracks/');
        $this->generator->generate(6, 0);
    }

    // ── Invalid Duration Tests ─────────────────────────────────────────────

    public function testGenerateThrowsForInvalidMonths(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported contract duration: 12 months/');
        $this->generator->generate(12, 3);
    }

    public function testGenerateThrowsForOneMonth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported contract duration: 1 months/');
        $this->generator->generate(1, 1);
    }

    public function testGenerateThrowsForFourMonths(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported contract duration: 4 months/');
        $this->generator->generate(4, 2);
    }
}
