<?php

namespace App\Service;

/**
 * Generates Track Intensity distributions based on Hot Spots rules (Draconis Reach, p. 147).
 * 
 * The intensity is determined by rolling 1D6 and referencing the Track Intensity Table
 * based on the number of tracks versus the number of months in the contract.
 */
class TrackIntensityGenerator
{
    /**
     * Three-Month Contract Intensity Tables.
     * Format: [roll => [tracks => intensity_string]].
     */
    private const THREE_MONTH_TABLES = [
        1 => ['1' => '0-1-0', '2' => '0-1-1', '3' => '0-1-2'],
        2 => ['1' => '0-1-0', '2' => '0-1-1', '3' => '1-1-1'],
        3 => ['1' => '0-1-0', '2' => '0-1-1', '3' => '1-1-1'],
        4 => ['1' => '0-0-1', '2' => '1-0-1', '3' => '0-2-1'],
        5 => ['1' => '0-0-1', '2' => '0-0-2', '3' => '2-0-1'],
        6 => ['1' => '1-0-0', '2' => '0-2-0', '3' => '2-1-0'],
    ];

    /**
     * Six-Month Contract Intensity Tables.
     * Format: [roll => [tracks => intensity_string]].
     */
    private const SIX_MONTH_TABLES = [
        1 => [
            '1' => '0-0-1-0-0-0',
            '2' => '0-1-0-1-0-0',
            '3' => '0-1-0-1-0-1',
            '4' => '0-1-0-1-1-1',
            '5' => '0-1-1-1-1-1',
            '6' => '1-1-1-1-1-1',
        ],
        2 => [
            '1' => '0-0-0-0-1-0',
            '2' => '0-0-1-0-1-0',
            '3' => '0-1-0-1-1-0',
            '4' => '0-1-1-1-1-0',
            '5' => '0-1-1-1-0-2',
            '6' => '0-2-1-1-1-1',
        ],
        3 => [
            '1' => '0-0-1-0-0-0',
            '2' => '0-0-1-0-0-1',
            '3' => '0-1-1-1-0-0',
            '4' => '0-1-1-1-1-1',
            '5' => '0-2-0-2-0-1',
            '6' => '0-2-2-0-1-2',
        ],
        4 => [
            '1' => '1-0-0-0-0-0',
            '2' => '0-1-1-0-0-0',
            '3' => '0-0-0-1-1-1',
            '4' => '0-2-0-1-0-1',
            '5' => '0-2-1-0-1-1',
            '6' => '0-1-2-1-2-1',
        ],
        5 => [
            '1' => '0-0-0-0-1-0',
            '2' => '0-0-0-1-1-0',
            '3' => '0-2-0-1-0-0',
            '4' => '0-0-2-0-1-1',
            '5' => '0-2-2-1-0-0',
            '6' => '0-1-1-2-0-2',
        ],
        6 => [
            '1' => '0-0-0-1-0-0',
            '2' => '0-0-2-0-0-0',
            '3' => '0-0-0-2-1-0',
            '4' => '0-2-1-0-1-0',
            '5' => '0-0-0-2-2-1',
            '6' => '0-1-2-2-1-0',
        ],
    ];

    /**
     * Generate an intensity distribution string for a contract.
     *
     * @param int $months The duration of the contract (3 or 6 months).
     * @param int $tracks The total number of tracks in the contract.
     * @return string The intensity distribution string (e.g., "0-2-1-0-1-0").
     * @throws \InvalidArgumentException If the contract duration is not 3 or 6 months,
     *                                   or if the number of tracks is out of range for the table.
     */
    public function generate(int $months, int $tracks): string
    {
        $roll = random_int(1, 6);

        if ($months === 3) {
            return $this->getThreeMonthIntensity($roll, $tracks);
        } elseif ($months === 6) {
            return $this->getSixMonthIntensity($roll, $tracks);
        }

        throw new \InvalidArgumentException("Unsupported contract duration: {$months} months. Only 3 or 6 month contracts are supported.");
    }

    /**
     * Get intensity for a 3-month contract.
     */
    private function getThreeMonthIntensity(int $roll, int $tracks): string
    {
        if (!isset(self::THREE_MONTH_TABLES[$roll])) {
            throw new \InvalidArgumentException("Invalid roll value: {$roll}");
        }

        if (!isset(self::THREE_MONTH_TABLES[$roll][$tracks])) {
            throw new \InvalidArgumentException(
                "Invalid number of tracks ({$tracks}) for 3-month contract. Valid range: 1-3."
            );
        }

        return self::THREE_MONTH_TABLES[$roll][$tracks];
    }

    /**
     * Get intensity for a 6-month contract.
     */
    private function getSixMonthIntensity(int $roll, int $tracks): string
    {
        if (!isset(self::SIX_MONTH_TABLES[$roll])) {
            throw new \InvalidArgumentException("Invalid roll value: {$roll}");
        }

        if (!isset(self::SIX_MONTH_TABLES[$roll][$tracks])) {
            throw new \InvalidArgumentException(
                "Invalid number of tracks ({$tracks}) for 6-month contract. Valid range: 1-6."
            );
        }

        return self::SIX_MONTH_TABLES[$roll][$tracks];
    }
}
