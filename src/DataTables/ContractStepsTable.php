<?php
namespace App\DataTables;
use App\Enum\CommandRights;

class ContractStepsTable {
    // 13-step Contract Steps Table matching source rules
    // Some steps are blank (null) for categories — cannot be selected, but cost reputation to skip over
    private const STEPS = [
        1  => [50,   null,            'None',         'None',          null],
        2  => [55,   null,            null,           'Straight/20%',  null],
        3  => [60,   CommandRights::Integrated, 'Exchange', 'Straight/40%',  null],
        4  => [70,   null,            '10%',          'Straight/60%',  null],
        5  => [80,   null,            '20%',          'Straight/80%',  '0%'],
        6  => [90,   null,            '30%',          'Straight/100%', '25%'],
        7  => [100,  CommandRights::House, '40%',     'Battle/10%',    '50%'],
        8  => [110,  CommandRights::Liaison, '50%',    'Battle/20%',    '75%'],
        9  => [120,  null,            '60%',          'Battle/30%',    '100%'],
        10 => [130,  null,            '70%',          'Battle/40%',    null],
        11 => [150,  CommandRights::Independent, '80%',  'Battle/50%',    null],
        12 => [175,  null,            '90%',          'Battle/75%',    null],
        13 => [200,  null,            '100%',         'Battle/100%',   null],
    ];

    public static function getBasePayPercent(int $step): ?int { return self::STEPS[$step][0] ?? null; }
    public static function getCommandRights(int $step): ?CommandRights { return self::STEPS[$step][1] ?? null; }
    public static function getSalvageRights(int $step): string { return self::STEPS[$step][2] ?? 'None'; }
    public static function getSupportTerms(int $step): string { return self::STEPS[$step][3] ?? 'None'; }
    public static function getTransportTerms(int $step): ?string { return self::STEPS[$step][4] ?? null; }
    public static function clampStep(int $step): int { return max(1, min(13, $step)); }

    public static function getStepValues(int $step): array {
        return self::STEPS[$step];
    }

    public static function getStepForCategory(int $step, string $category): int {
        $categoryIndex = match ($category) {
            'basePayPercent' => 0,
            'commandRights' => 1,
            'salvageRights' => 2,
            'supportTerms' => 3,
            'transportTerms' => 4,
            default => 0,
        };

        for ($i = 1; $i <= 13; $i++) {
            $values = self::STEPS[$i];
            if ($categoryIndex === 0) {
                if ($values[0] === self::STEPS[$step][0]) return $i;
            } elseif ($categoryIndex === 1) {
                if ($values[1] === self::STEPS[$step][1]) return $i;
            } elseif ($categoryIndex === 4) {
                if ($values[4] === self::STEPS[$step][4]) return $i;
            } else {
                if ($values[$categoryIndex] === self::STEPS[$step][$categoryIndex]) return $i;
            }
        }
        return $step;
    }

    public static function isStepReachable(int $currentStep, int $targetStep): bool {
        return $targetStep >= $currentStep && $targetStep - $currentStep <= 13;
    }
}
