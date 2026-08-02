<?php

namespace App\Tests\Unit\Service;

use App\Service\DiceRoller;
use PHPUnit\Framework\TestCase;

class DiceRollerTest extends TestCase
{
    private DiceRoller $dice;

    protected function setUp(): void
    {
        $this->dice = new DiceRoller();
    }

    public function testRollSingleDie(): void
    {
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = $this->dice->roll(1, 6);
        }
        $this->assertGreaterThanOrEqual(1, min($results));
        $this->assertLessThanOrEqual(6, max($results));
    }

    public function testRollMultipleDice(): void
    {
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = $this->dice->roll(2, 6);
        }
        $this->assertGreaterThanOrEqual(2, min($results));
        $this->assertLessThanOrEqual(12, max($results));
    }

    public function testRollManyDice(): void
    {
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = $this->dice->roll(3, 20);
        }
        $this->assertGreaterThanOrEqual(3, min($results));
        $this->assertLessThanOrEqual(60, max($results));
    }

    public function testRollLargeSides(): void
    {
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = $this->dice->roll(1, 100);
        }
        $this->assertGreaterThanOrEqual(1, min($results));
        $this->assertLessThanOrEqual(100, max($results));
    }
}
