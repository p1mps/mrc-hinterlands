<?php
namespace App\DataTables;

class CommandComplicationTable {
    private const TABLES = [
        'Desert' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'Desert sun reducing heatsink efficacy. All heat tracking units suffer +2 heat every turn.',
            6=>'Sandstorm. All DE and P weapons suffer +1 to hit this track.',
            7=>'Rushed maintenance. Desert sand in actuators. Roll 1d6: arm affected. Mark one actuator damaged on that arm.',
            8=>'Sand in heatsinks. Any unit with 5+ heat after heat phase reduces heat dissipation by 1 for the track.',
        ],
        'Grasslands' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'No vehicular support. No tracked, wheeled, or hover units may be selected this track.',
            6=>'Elementals joined the battle. Add a 3/4 Elemental III Battle Armor [Flamer] (Sqd5) to the opponent.',
            7=>'Overcast conditions. Your units suffer +1 to hit with all weapon attacks.',
            8=>'Local air support scrambled against you. Opponent gains 1d2 Light Strike BSPs this track.',
        ],
        'Hills' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'Fog of war. All units suffer +1 to hit at ranges over 12 hexes.',
            6=>'Rough terrain. All ground units movement reduced by 1 MP this track.',
            7=>'Ambushing infantry. Opponent may deploy 1 infantry unit as hidden.',
            8=>'Landslide risk. Any unit that moves through level 3+ terrain must make a PSR.',
        ],
        'Light Industrial' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'Civilian evacuation. No area effect weapons allowed this track.',
            6=>'Hazardous materials. Any unit destroyed in a building hex causes an explosion (1d6 damage to adjacent units).',
            7=>'Power grid interference. All ECM and probe equipment is non-functional this track.',
            8=>'Armed workers. Opponent gains 1d2 Conventional Infantry units.',
        ],
        'Mountains' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'High altitude. All jump jets require double movement points.',
            6=>'Rockslide. 1d6 random hexes become impassable rubble.',
            7=>'Thin air. All units suffer +1 heat per turn.',
            8=>'Cliff face. No units may traverse directly between level 4 and level 1.',
        ],
        'Savannahs' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'Dust storm. All ranged attacks beyond 6 hexes suffer +2 to hit.',
            6=>'Brush fire. 1d6 light woods hexes become burning at start of turn 3.',
            7=>'Extreme heat. All units suffer +1 heat per turn.',
            8=>'Wildlife stampede. All ground units must make PSR or be displaced 1 hex.',
        ],
        'Urban' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'Media presence. No indirect fire allowed.',
            6=>'Civilian interference. 1d6 civilian vehicles on the field, treated as obstacles.',
            7=>'Security forces. Opponent gains 1 conventional infantry unit.',
            8=>'Building collapse. One random heavy building becomes rubble at start of turn 4.',
        ],
        'Wetlands' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'Swamp gas. All flamers and inferno weapons add +1 heat to the firing unit.',
            6=>'Deep mud. Wheeled and tracked units movement reduced by 2 MP.',
            7=>'Flash flood. 1d6 swamp hexes expand to adjacent hexes at start of turn 3.',
            8=>'Sinking ground. Any unit ending movement in a swamp hex must make a PSR or sink.',
        ],
        'Wooded' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'Forest fire risk. Any flamer or inferno weapon converts light woods to burning.',
            6=>'Dense canopy. Jump jets may not be used over heavy woods.',
            7=>'Concealed defenders. Opponent may deploy all units as hidden.',
            8=>'Logging obstructions. 1d6 hexes of clear terrain become light woods.',
        ],
        'Alien' => [
            1=>'No Complication', 2=>'No Complication', 3=>'No Complication', 4=>'No Complication',
            5=>'Unusual atmosphere. All heat sinks operate at 75% efficiency.',
            6=>'Alien fauna. 1d6 random hexes contain hostile creatures (treat as infantry).',
            7=>'Electromagnetic anomaly. All electronics non-functional turns 1-3.',
            8=>'Unstable ground. All units must make PSR when moving more than walking speed.',
        ],
    ];

    // roll = 1d6 + commandRightsBonus (0-3). Result >= 9 means roll twice.
    public static function lookup(string $terrain, int $roll): string {
        if ($roll >= 9) {
            return 'ROLL TWICE — apply both complications (reroll any "No Complication" or duplicate results)';
        }
        $table = self::TABLES[$terrain] ?? self::TABLES['Grasslands'];
        return $table[max(1, min(8, $roll))] ?? 'No Complication';
    }
}
