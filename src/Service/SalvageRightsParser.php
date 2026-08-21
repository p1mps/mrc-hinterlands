<?php

namespace App\Service;

use App\Entity\Contract;

/**
 * Parses the `salvageRights` string from a Contract into a numeric percentage.
 *
 * Supported formats (from the game rules):
 *   - "3", "4"  → numeric salvage rights (e.g., 3% or 4% — actual game value)
 *   - "Exchange" → null (prohibits acquisition, grants 25% SP payout)
 *   - "Exchange/50%" → null (prohibits acquisition, grants 50% SP payout)
 *   - "None" → 0 (prohibits acquisition)
 *   - "—" → 0 (no salvage rights)
 *
 * For numeric forms like "3" or "4", the value is used directly
 * as the percentage for the salvage rights calculation.
 */
class SalvageRightsParser
{
    /**
     * Parse the salvage rights string from a Contract into an integer percentage.
     *
     * Returns:
     *   - null  → "Exchange" or "Exchange/XX%" (acquisition prohibited, SP payout available)
     *   - 0     → "None" or "—" (no salvage)
     *   - int   → numeric salvage rights (e.g., 3, 4, 50)
     */
    public function parse(?string $salvageRights): ?int
    {
        if ($salvageRights === null || $salvageRights === '' || $salvageRights === '—') {
            return 0;
        }

        // "None" means no salvage rights
        if (strtolower(trim($salvageRights)) === 'none') {
            return 0;
        }

        // "Exchange" alone → null (acquisition prohibited, 25% SP payout)
        if (strtolower(trim($salvageRights)) === 'exchange') {
            return null;
        }

        // "Exchange/XX%" → null (acquisition prohibited, XX% SP payout)
        if (stripos($salvageRights, 'exchange') !== false) {
            return null;
        }

        // Pure numeric: "3", "4", "50"
        if (preg_match('/^(\d+)$/', trim($salvageRights), $m)) {
            return (int) $m[1];
        }

        // Fallback: try to extract a percentage
        if (preg_match('/(\d+)%?/', $salvageRights, $m)) {
            return (int) $m[1];
        }

        // Unknown format → treat as no salvage
        return 0;
    }

    /**
     * Check if acquisition is allowed given a raw salvage rights string.
     *
     * Returns true if the contract allows the player to acquire the mech.
     * Returns false if it's "Exchange", "None", or "—".
     */
    public function isAcquisitionAllowed(?string $salvageRights): bool
    {
        $percent = $this->parse($salvageRights);

        // null means "Exchange" which prohibits acquisition
        if ($percent === null) {
            return false;
        }

        // 0 means "None" which also prohibits acquisition
        if ($percent === 0) {
            return false;
        }

        return true;
    }

    /**
     * Get the human-readable description of the salvage rights.
     */
    public function formatDescription(?string $salvageRights): string
    {
        if ($salvageRights === null || $salvageRights === '') {
            return 'None';
        }

        $trimmed = trim($salvageRights);

        if (strtolower($trimmed) === 'exchange') {
            return 'Exchange (no acquisition)';
        }

        if (stripos($trimmed, 'exchange') !== false) {
            return 'Exchange/' . preg_replace('/^.*?\/?(\d+)%?$/', '$1', $trimmed);
        }

        if (strtolower($trimmed) === 'none' || $trimmed === '—') {
            return 'None';
        }

        return $trimmed;
    }
}
