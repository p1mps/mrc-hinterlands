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
use App\Entity\Contract;
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

    public function generateOpposing(ContractType $type, int $scale, int $numberOfTracks, ?string $planet = null, ?string $intensity = null): array {
        $oppRoll      = $this->dice->roll(2, 6);
        $opposingType = ContractTypeTable::lookupOpposing($type, $oppRoll);
        $result       = $this->generate($scale);
        $result['type']         = $opposingType;
        $result['isOpposing']   = true;
        $result['numberOfTracks'] = $numberOfTracks;
        $result['planet']       = $planet;
        $result['intensity']    = $intensity;
        return $result;
    }

    public function negotiateExistingContract(Contract $contract, int $reputation, array $negotiationChanges = []): array {
        // Start from existing contract values, not random generation
        $result = [
            'type' => $contract->getType(),
            'duration' => $contract->getDurationMonths(),
            'employer' => $contract->getEmployer(),
            'affiliation' => $contract->getEmployerAffiliation(),
            'scale' => $contract->getScale(),
            'basePayPercent' => $contract->getBasePayPercent(),
            'commandRights' => $contract->getCommandRights(),
            'supportTerms' => $contract->getSupportTerms(),
            'salvageRights' => $contract->getSalvageRights(),
            'transportTerms' => $contract->getTransportTerms(),
            'numberOfTracks' => $contract->getNumberOfTracks(),
            'rolls' => $this->buildCurrentStateRolls($contract),
            'negotiationSummary' => [
                'reputation' => $reputation,
                'availableSteps' => $reputation,
            ],
        ];

        // Apply negotiation changes on top
        foreach ($negotiationChanges as $category => $targetStep) {
            $currentStep = match ($category) {
                'basePayPercent' => $this->getStepForValue('basePayPercent', $result['basePayPercent']),
                'commandRights' => $this->getStepForValue('commandRights', $result['commandRights']),
                'salvageRights' => $this->getStepForValue('salvageRights', $result['salvageRights']),
                'supportTerms' => $this->getStepForValue('supportTerms', $result['supportTerms']),
                'transportTerms' => $this->getStepForValue('transportTerms', $result['transportTerms'] ?? '—'),
                default => null,
            };

            if ($currentStep === null || $targetStep === null) continue;

            match ($category) {
                'basePayPercent' => $result['basePayPercent'] = ContractStepsTable::getBasePayPercent($targetStep),
                'commandRights' => $result['commandRights'] = ContractStepsTable::getCommandRights($targetStep) ?? CommandRights::Liaison,
                'salvageRights' => $result['salvageRights'] = ContractStepsTable::getSalvageRights($targetStep),
                'supportTerms' => $result['supportTerms'] = ContractStepsTable::getSupportTerms($targetStep),
                'transportTerms' => $result['transportTerms'] = ContractStepsTable::getTransportTerms($targetStep) ?? '—',
            };
        }

        // Build stepsTable
        $stepsTable = [];
        foreach (range(1, 13) as $step) {
            $stepsTable[$step] = [
                'basePayPercent' => ContractStepsTable::getBasePayPercent($step),
                'commandRights' => ContractStepsTable::getCommandRights($step)?->value ?? null,
                'salvageRights' => ContractStepsTable::getSalvageRights($step),
                'supportTerms' => ContractStepsTable::getSupportTerms($step),
                'transportTerms' => ContractStepsTable::getTransportTerms($step),
            ];
        }
        $result['stepsTable'] = $stepsTable;

        return $result;
    }

    private function buildCurrentStateRolls(Contract $contract): array {
        $rolls = [];

        // Pay Rate
        $payStep = $this->getStepForValue('basePayPercent', $contract->getBasePayPercent());
        $rolls[] = ['label' => 'Pay Rate', 'roll' => $payStep, 'step' => $payStep];

        // Command Rights
        $cmdStep = $this->getStepForValue('commandRights', $contract->getCommandRights());
        $rolls[] = ['label' => 'Command Rights', 'roll' => $cmdStep, 'step' => $cmdStep];

        // Salvage Rights
        $salvageStep = $this->getStepForValue('salvageRights', $contract->getSalvageRights());
        $rolls[] = ['label' => 'Salvage Rights', 'roll' => $salvageStep, 'step' => $salvageStep];

        // Support
        $supportStep = $this->getStepForValue('supportTerms', $contract->getSupportTerms());
        $rolls[] = ['label' => 'Support', 'roll' => $supportStep, 'step' => $supportStep];

        // Transportation
        $transStep = $this->getStepForValue('transportTerms', $contract->getTransportTerms());
        $rolls[] = ['label' => 'Transportation', 'roll' => $transStep, 'step' => $transStep];

        // Number of Tracks
        $rolls[] = ['label' => 'Number of Tracks', 'roll' => $contract->getNumberOfTracks(), 'result' => $contract->getNumberOfTracks()];

        return $rolls;
    }

    public function generateWithNegotiation(int $scale, int $reputation, array $negotiationChanges = []): array {
        $base = $this->generate($scale);

        // Apply final state from negotiationChanges (already validated by controller)
        foreach ($negotiationChanges as $category => $targetStep) {
            $currentStep = match ($category) {
                'basePayPercent' => $this->getStepForValue('basePayPercent', $base['basePayPercent']),
                'commandRights' => $this->getStepForValue('commandRights', $base['commandRights']),
                'salvageRights' => $this->getStepForValue('salvageRights', $base['salvageRights']),
                'supportTerms' => $this->getStepForValue('supportTerms', $base['supportTerms']),
                'transportTerms' => $this->getStepForValue('transportTerms', $base['transportTerms'] ?? '—'),
                default => null,
            };

            if ($currentStep === null || $targetStep === null) continue;

            match ($category) {
                'basePayPercent' => $base['basePayPercent'] = ContractStepsTable::getBasePayPercent($targetStep),
                'commandRights' => $base['commandRights'] = ContractStepsTable::getCommandRights($targetStep) ?? CommandRights::Liaison,
                'salvageRights' => $base['salvageRights'] = ContractStepsTable::getSalvageRights($targetStep),
                'supportTerms' => $base['supportTerms'] = ContractStepsTable::getSupportTerms($targetStep),
                'transportTerms' => $base['transportTerms'] = ContractStepsTable::getTransportTerms($targetStep) ?? '—',
            };
        }

        // Update the rolls array to reflect the negotiated step values
        $rollMap = [
            'Pay Rate' => 'basePayPercent',
            'Command Rights' => 'commandRights',
            'Salvage Rights' => 'salvageRights',
            'Support' => 'supportTerms',
            'Transportation' => 'transportTerms',
        ];

        foreach ($rollMap as $rollLabel => $category) {
            $step = match ($category) {
                'basePayPercent' => $this->getStepForValue('basePayPercent', $base['basePayPercent']),
                'commandRights' => $this->getStepForValue('commandRights', $base['commandRights']->value),
                'salvageRights' => $this->getStepForValue('salvageRights', $base['salvageRights']),
                'supportTerms' => $this->getStepForValue('supportTerms', $base['supportTerms']),
                'transportTerms' => $this->getStepForValue('transportTerms', $base['transportTerms'] ?? '—'),
                default => null,
            };

            if ($step === null) continue;

            foreach ($base['rolls'] as &$roll) {
                if ($roll['label'] === $rollLabel) {
                    $roll['step'] = $step;
                    break;
                }
            }
            unset($roll);
        }

        // Update Number of Tracks roll
        foreach ($base['rolls'] as &$roll) {
            if ($roll['label'] === 'Number of Tracks') {
                $roll['result'] = $base['numberOfTracks'];
                break;
            }
        }
        unset($roll);

        $base['negotiationSummary'] = [
            'reputation' => $reputation,
            'availableSteps' => $reputation,
        ];

        $stepsTable = [];
        foreach (range(1, 13) as $step) {
            $stepsTable[$step] = [
                'basePayPercent' => ContractStepsTable::getBasePayPercent($step),
                'commandRights' => ContractStepsTable::getCommandRights($step)?->value ?? null,
                'salvageRights' => ContractStepsTable::getSalvageRights($step),
                'supportTerms' => ContractStepsTable::getSupportTerms($step),
                'transportTerms' => ContractStepsTable::getTransportTerms($step),
            ];
        }
        $base['stepsTable'] = $stepsTable;

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
