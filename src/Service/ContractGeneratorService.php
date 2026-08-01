<?php
namespace App\Service;

use App\DataTables\CommandComplicationTable;
use App\DataTables\CommandRightsTable;
use App\DataTables\ContractStepsTable;
use App\DataTables\ContractTrackTable;
use App\DataTables\ContractTypeTable;
use App\DataTables\EmployerAffiliationTable;
use App\DataTables\EmployerTable;
use App\DataTables\NumberOfTracksTable;
use App\DataTables\PayRateTable;
use App\DataTables\PlanetTable;
use App\DataTables\SalvageRightsTable;
use App\DataTables\SupportTermsTable;
use App\DataTables\TerrainTable;
use App\DataTables\TransportationTable;
use App\Enum\CommandRights;
use App\Enum\ContractType;
use App\Entity\MercenaryCompany;

class ContractGeneratorService {
    public function __construct(private readonly DiceRoller $dice) {}

    public function generate(int $scale): array {
        $rolls = [];

        $typeRoll = $this->dice->roll(2, 6);
        $typeData = ContractTypeTable::lookup($typeRoll);
        $contractType = $typeData['type'];
        $rolls[] = ['label' => 'Contract Type', 'roll' => $typeRoll, 'result' => $contractType->value];

        $empRoll = $this->dice->roll(2, 6);
        $employer = EmployerTable::lookup($empRoll);
        $rolls[] = ['label' => 'Employer', 'roll' => $empRoll, 'result' => $employer];

        $affiliation = $this->rollAffiliation($employer, $rolls);

        $payRoll = $this->dice->roll(2, 6);
        $payMod  = PayRateTable::getModifier($employer, $affiliation, $contractType->value);
        $payStep = ContractStepsTable::clampStep(PayRateTable::getBaseStep($payRoll) + $payMod);
        $rolls[] = ['label' => 'Pay Rate', 'roll' => $payRoll, 'modifier' => $payMod, 'step' => $payStep];
        $basePayPercent = ContractStepsTable::getBasePayPercent($payStep);

        $supportRoll = $this->dice->roll(2, 6);
        $supportMod  = SupportTermsTable::getModifier($employer, $affiliation, $contractType->value);
        $supportStep = ContractStepsTable::clampStep(SupportTermsTable::getBaseStep($supportRoll) + $supportMod);
        $rolls[] = ['label' => 'Support', 'roll' => $supportRoll, 'modifier' => $supportMod, 'step' => $supportStep];
        $supportTerms = ContractStepsTable::getSupportTerms($supportStep);

        $salvageRoll = $this->dice->roll(2, 6);
        $salvageMod  = SalvageRightsTable::getModifier($employer, $affiliation, $contractType->value);
        $salvageStep = ContractStepsTable::clampStep(SalvageRightsTable::getBaseStep($salvageRoll) + $salvageMod);
        $rolls[] = ['label' => 'Salvage Rights', 'roll' => $salvageRoll, 'modifier' => $salvageMod, 'step' => $salvageStep];
        $salvageRights = ContractStepsTable::getSalvageRights($salvageStep);

        $transRoll = $this->dice->roll(2, 6);
        $transMod  = TransportationTable::getModifier($employer, $affiliation, $contractType->value);
        $transStep = ContractStepsTable::clampStep(TransportationTable::getBaseStep($transRoll) + $transMod);
        $rolls[] = ['label' => 'Transportation', 'roll' => $transRoll, 'modifier' => $transMod, 'step' => $transStep];
        $transportTerms = ContractStepsTable::getTransportTerms($transStep) ?? '—';

        $cmdRoll = $this->dice->roll(2, 6);
        $cmdMod  = CommandRightsTable::getModifier($employer, $affiliation, $contractType->value);
        $cmdStep = ContractStepsTable::clampStep(CommandRightsTable::getBaseStep($cmdRoll) + $cmdMod);
        $rolls[] = ['label' => 'Command Rights', 'roll' => $cmdRoll, 'modifier' => $cmdMod, 'step' => $cmdStep];
        $commandRights = ContractStepsTable::getCommandRights($cmdStep) ?? CommandRights::Liaison;

        $trackRoll      = $this->dice->roll(2, 6);
        $numberOfTracks = NumberOfTracksTable::lookup($contractType, $trackRoll);
        $rolls[] = ['label' => 'Number of Tracks', 'roll' => $trackRoll, 'result' => $numberOfTracks];

        return [
            'type'           => $contractType,
            'duration'       => $typeData['duration'],
            'employer'       => $employer,
            'affiliation'    => $affiliation,
            'scale'          => $scale,
            'basePayPercent' => $basePayPercent,
            'commandRights'  => $commandRights,
            'supportTerms'   => $supportTerms,
            'salvageRights'  => $salvageRights,
            'transportTerms' => $transportTerms,
            'numberOfTracks' => $numberOfTracks,
            'planet'         => PlanetTable::randomPlanet(),
            'rolls'          => $rolls,
        ];
    }

    public function generateOpposing(ContractType $primaryType, int $scale, int $numberOfTracks): array {
        $oppRoll      = $this->dice->roll(2, 6);
        $opposingType = ContractTypeTable::lookupOpposing($primaryType, $oppRoll);
        $result       = $this->generate($scale);
        $result['type']       = $opposingType;
        $result['isOpposing'] = true;
        $result['numberOfTracks'] = $numberOfTracks;
        return $result;
    }

    public function generateWithNegotiation(int $scale, int $reputation, array $negotiationChanges = []): array {
        $base = $this->generate($scale);

        $availableSteps = min($reputation, 2 * $scale);
        $swaps = 0;
        $maxSwaps = 2;

        foreach ($negotiationChanges as $category => $targetStep) {
            if ($category === 'swap_from' || $category === 'swap_to') continue;

            $currentStep = match ($category) {
                'basePayPercent' => $this->getStepForValue('basePayPercent', $base['basePayPercent']),
                'commandRights' => $this->getStepForValue('commandRights', $base['commandRights']->value),
                'salvageRights' => $this->getStepForValue('salvageRights', $base['salvageRights']),
                'supportTerms' => $this->getStepForValue('supportTerms', $base['supportTerms']),
                'transportTerms' => $this->getStepForValue('transportTerms', $base['transportTerms'] ?? '—'),
                default => null,
            };

            if ($currentStep === null || $targetStep === null) continue;

            $shift = $targetStep - $currentStep;
            if ($shift <= 0) continue;

            $shift = min($shift, $availableSteps);
            $availableSteps -= $shift;

            match ($category) {
                'basePayPercent' => $base['basePayPercent'] = ContractStepsTable::getBasePayPercent($targetStep),
                'commandRights' => $base['commandRights'] = ContractStepsTable::getCommandRights($targetStep) ?? CommandRights::Liaison,
                'salvageRights' => $base['salvageRights'] = ContractStepsTable::getSalvageRights($targetStep),
                'supportTerms' => $base['supportTerms'] = ContractStepsTable::getSupportTerms($targetStep),
                'transportTerms' => $base['transportTerms'] = ContractStepsTable::getTransportTerms($targetStep) ?? '—',
            };
        }

        if (isset($negotiationChanges['swap_from']) && isset($negotiationChanges['swap_to']) && $swaps < $maxSwaps) {
            $fromCategory = $negotiationChanges['swap_from'];
            $toCategory = $negotiationChanges['swap_to'];

            $fromStep = match ($fromCategory) {
                'basePayPercent' => $this->getStepForValue('basePayPercent', $base['basePayPercent']),
                'commandRights' => $this->getStepForValue('commandRights', $base['commandRights']->value),
                'salvageRights' => $this->getStepForValue('salvageRights', $base['salvageRights']),
                'supportTerms' => $this->getStepForValue('supportTerms', $base['supportTerms']),
                'transportTerms' => $this->getStepForValue('transportTerms', $base['transportTerms'] ?? '—'),
                default => null,
            };

            $toStep = match ($toCategory) {
                'basePayPercent' => $this->getStepForValue('basePayPercent', $base['basePayPercent']),
                'commandRights' => $this->getStepForValue('commandRights', $base['commandRights']->value),
                'salvageRights' => $this->getStepForValue('salvageRights', $base['salvageRights']),
                'supportTerms' => $this->getStepForValue('supportTerms', $base['supportTerms']),
                'transportTerms' => $this->getStepForValue('transportTerms', $base['transportTerms'] ?? '—'),
                default => null,
            };

            if ($fromStep !== null && $toStep !== null) {
                $canSacrifice = $fromStep - 1;
                if ($canSacrifice >= 1) {
                    $gain = min(2, $fromStep - 1);
                    $newFromStep = $fromStep - $gain;
                    $newToStep = min(13, $toStep + 1);

                    match ($fromCategory) {
                        'basePayPercent' => $base['basePayPercent'] = ContractStepsTable::getBasePayPercent($newFromStep),
                        'commandRights' => $base['commandRights'] = ContractStepsTable::getCommandRights($newFromStep) ?? CommandRights::Liaison,
                        'salvageRights' => $base['salvageRights'] = ContractStepsTable::getSalvageRights($newFromStep),
                        'supportTerms' => $base['supportTerms'] = ContractStepsTable::getSupportTerms($newFromStep),
                        'transportTerms' => $base['transportTerms'] = ContractStepsTable::getTransportTerms($newFromStep) ?? '—',
                    };

                    match ($toCategory) {
                        'basePayPercent' => $base['basePayPercent'] = ContractStepsTable::getBasePayPercent($newToStep),
                        'commandRights' => $base['commandRights'] = ContractStepsTable::getCommandRights($newToStep) ?? CommandRights::Liaison,
                        'salvageRights' => $base['salvageRights'] = ContractStepsTable::getSalvageRights($newToStep),
                        'supportTerms' => $base['supportTerms'] = ContractStepsTable::getSupportTerms($newToStep),
                        'transportTerms' => $base['transportTerms'] = ContractStepsTable::getTransportTerms($newToStep) ?? '—',
                    };

                    $swaps++;
                }
            }
        }

        $base['negotiationSummary'] = [
            'reputation' => $reputation,
            'availableSteps' => $availableSteps,
            'swapsUsed' => $swaps,
        ];

        return $base;
    }

    private function getStepForValue(string $category, mixed $value): ?int {
        for ($i = 1; $i <= 13; $i++) {
            $values = ContractStepsTable::getStepValues($i);
            $match = match ($category) {
                'basePayPercent' => $values[0] === $value,
                'commandRights' => $values[1] instanceof CommandRights ? $values[1]->value === $value : $value === null,
                'salvageRights' => $values[2] === $value,
                'supportTerms' => $values[3] === $value,
                'transportTerms' => $values[4] === $value,
                default => false,
            };
            if ($match) return $i;
        }
        return null;
    }

    public function rollTrackSetup(ContractType $contractType, CommandRights $commandRights): array {
        $missionRoll = $this->dice->roll(1, 6);
        $missionType = ContractTrackTable::lookup($contractType, $missionRoll);

        $terrainRoll = $this->dice->roll(2, 6);
        $terrainData = TerrainTable::lookup($terrainRoll);

        $compRoll    = $this->dice->roll(1, 6) + $commandRights->complicationBonus();
        $complication = CommandComplicationTable::lookup($terrainData['terrain'], $compRoll);

        return [
            'missionType'      => $missionType,
            'missionRoll'      => $missionRoll,
            'terrain'          => $terrainData['terrain'],
            'terrainSetting'   => $terrainData['setting'],
            'terrainRoll'      => $terrainRoll,
            'complication'     => $complication,
            'complicationRoll' => $compRoll,
        ];
    }

    private function rollAffiliation(string $employer, array &$rolls): string {
        for ($i = 0; $i < 5; $i++) {
            $affRoll    = $this->dice->roll(2, 6);
            $secondRoll = $this->dice->roll(2, 6);
            $affiliation = EmployerAffiliationTable::lookup($affRoll, $employer, $secondRoll);
            if (EmployerAffiliationTable::isCompatible($employer, $affiliation)) {
                $rolls[] = ['label' => 'Employer Affiliation', 'roll' => $affRoll, 'secondRoll' => $secondRoll, 'result' => $affiliation];
                return $affiliation;
            }
        }
        $rolls[] = ['label' => 'Employer Affiliation', 'roll' => 7, 'secondRoll' => 7, 'result' => 'Mercenary', 'note' => 'fallback'];
        return 'Mercenary';
    }
}
