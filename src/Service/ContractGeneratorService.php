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
use App\DataTables\SalvageRightsTable;
use App\DataTables\SupportTermsTable;
use App\DataTables\TerrainTable;
use App\DataTables\TransportationTable;
use App\Enum\CommandRights;
use App\Enum\ContractType;

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
