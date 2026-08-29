<?php
namespace App\DataTables;

class CommandComplicationTable {
    private const TABLES = [
        'Desert' => [
            1 => 'No Complication [cite: 887]',
            2 => 'No Complication [cite: 887]',
            3 => 'No Complication [cite: 887]',
            4 => 'No Complication [cite: 887]',
            5 => 'The desert sun is reducing the efficacy of your heatsinks. All heat tracking units suffer from +2 heat every turn. [cite: 888, 889]',
            6 => 'The desert winds have kicked up a sandstorm. All units\' DE and P weapons suffer from +1 to hit this track. [cite: 890]',
            7 => 'Maintenance was rushed, and the desert sand has found its way into one of your mechs actuators. Roll 1d6 to select your right or left arm, then roll on each arm\'s crit table, rerolling any result that is not an actuator. Mark off that actuator as damaged on the unit\'s data sheet. [cite: 891, 892, 893]',
            8 => 'The sand has heatsinks working overtime. Any time a unit has 5 or more heat after the heat phase, reduce the heat dissipation capacity of the mech by 1. This is not a critical hit to the heatsink of the mech, so needs separate tracking. A coolant truck can be used to replace any cooling capacity lost mid-battle if the mech and coolant truck end the movement phase in the same turn and the neither unit makes any attacks. [cite: 894, 895]',
            9 => 'Roll twice and apply both effects. Do not reroll No Complication, but reroll any other duplicate effects. [cite: 896]'
        ],
        'Grasslands' => [
            1 => 'No Complication [cite: 975]',
            2 => 'No Complication [cite: 975]',
            3 => 'No Complication [cite: 975]',
            4 => 'No Complication [cite: 975]',
            5 => 'All the mechanics went out drinking the night before, and ran into a bad batch of local beer, and so no vehicular support is available. When the player picks support/BSP units, any unit with tracked, wheeled, or hover MP is may not be selected. [cite: 977, 978]',
            6 => 'A point of Elementals has joined the battle on your opponent\'s side, seeking honor and glory in battle. Add a 3/4 Elemental III Battle Armor [Flamer] (Sqd5) to your opponent\'s force (this unit does not count toward their support unit limit). If you should destroy this unit using only a single one of your units, then at the conclusion of this track you will have the option to acquire the unit and add it to your roster permanently for 500 SP (If the squad takes any damage from a second unit then you will lose the ability to acquire this unit). If using BSPs, then just add 1 Veteran Elemental III unit to the opponent\'s support for this track. [cite: 979, 980, 981, 982]',
            7 => 'Overcast conditions in the morning have taken a turn for the worse as what was originally a light rainfall has only kept up and increased over the day. Doubly unfortunately, your opponent had the time to prepare for the weather. Your units (not the opponent\'s) suffer from a +1 to hit with all weapon attacks. [cite: 984, 985, 986]',
            8 => 'Local air support has been scrambled against you. Give your opponent 1d2 Light Strike BSPs this track (Page 76 Battlemech Manuel). [cite: 987]',
            9 => 'Roll twice and apply both effects. Do not reroll No Complication, but reroll any other duplicate effects. [cite: 988]'
        ],
        'A Game of Armored Combat' => [
            1 => 'No Complication [cite: 1011]',
            2 => 'No Complication [cite: 1011]',
            3 => 'No Complication [cite: 1011]',
            4 => 'No Complication [cite: 1011]',
            5 => 'News choppers have arrived to cover the battle. Whichever side is successful in this track will gain +1 reputation. Destroying one of the VTOLs will result in an immediate -1 reputation to whichever player destroyed the VTOL (accidental crashes or skidding off the map will cause no effect unless caused by intentional damage). Add two tokens to the map at elevation 6 to represent the VTOLs. Use the Warrior Attack Helicopter BSP to represent each unit or use the datasheet for a Sprint Scout Helicopter for players looking to avoid BSP rules. [cite: 1013, 1014, 1015, 1016]',
            6 => 'The mission was set for dawn. Add +1 to hit with all weapon attacks. [cite: 1017]',
            7 => 'Infiltration by enemy forces has resulted in target beacons being placed in all of your units. All missile attacks by the opponent have a -1 to hit against your Mech units. [cite: 1018, 1019]',
            8 => 'Support was delayed for this mission, so they sent a messenger ahead to alert you. For support units you receive a single 4/5 Savannah Master Hovercraft this track (do not select any other units with your remaining BV). If using BSPs, you receive a single Savannah Master Hovercraft BSP. [cite: 1021, 1022, 1023]',
            9 => 'Roll twice and apply both effects. Do not reroll No Complication, but reroll any other duplicate effects. [cite: 1024]'
        ],
        'Clan Invasion' => [
            1 => 'No Complication [cite: 1123]',
            2 => 'No Complication [cite: 1123]',
            3 => 'No Complication [cite: 1123]',
            4 => 'No Complication [cite: 1123]',
            5 => 'High mountain passes cause issues for some vehicles. All units with Hover or VTOL MP reduce their cruising MP by 2 due to the thin atmosphere. BSPs with hover or VTOL movement reduce their total MP by 2. [cite: 1125, 1126]',
            6 => 'The attack has conceded with a poor forecast in the mountains. A snowstorm applies a +1 to all weapon attacks, and all heat tracking units may sink 1 more heat this track. [cite: 1129, 1130]',
            7 => 'Storms have delayed the deployment of some support assets, and grounded others. The player may not select units or BSPs with WiGE or VTOL MP, and all support units or BSPs deploy on Round 2 from the player\'s home edge. [cite: 1131, 1132]',
            8 => 'A bad illness has brought down your best MechWarrior. The MechWarrior with the lowest total skill on your roster may not be deployed on this track (if there is a tie then determine randomly which one is incapacitated. Total skill may be determined by adding the piloting skill to the gunnery skill and comparing the totals). [cite: 1133, 1134]',
            9 => 'Roll twice and apply both effects. Do not reroll No Complication, but reroll any other duplicate effects. [cite: 1135]'
        ],
        'Savannahs' => [
            1 => 'No Complication [cite: 1183]',
            2 => 'No Complication [cite: 1183]',
            3 => 'No Complication [cite: 1183]',
            4 => 'No Complication [cite: 1183]',
            5 => 'A local farmer was incensed at the discount your procurement officer insisted on purchasing his livestock at. He has decided to make this a problem for you. Give your opponent 1 Thumper Artillery Strike BSP this track (Page 77 BattleMech Manuel). [cite: 1185, 1186, 1187]',
            6 => 'An ambush has allowed the opponent to flank you in a pincer movement, taking you off guard. The opponent may opt to deploy their support units/BSPs from any board edge except yours. (Only support units mechs or other full units must still deploy from the standard edge). [cite: 1188, 1189, 1190]',
            7 => 'Poor navigation took you through a patch of rough terrain. Randomly assign a foot critical to one of your mechs and mark it on that mech\'s datasheet. [cite: 1191, 1192]',
            8 => 'A bad lightning storm has taken you by surprise enroute. At the End Phase of every round, roll 1d6. If the result is 5 or higher, apply 5 damage to one randomly determined unit and resolve any consequences of that damage. This damage is a single grouping of 5 applied to the front arc, though conventional infantry take the damage as though the damage originated from another infantry unit. [cite: 1194, 1195, 1196, 1197]',
            9 => 'Roll twice and apply both effects. Do not reroll No Complication, but reroll any other duplicate effects. [cite: 1198]'
        ],
        'Urban' => [
            1 => 'No Complication [cite: 1242]',
            2 => 'No Complication [cite: 1242]',
            3 => 'No Complication [cite: 1242]',
            4 => 'No Complication [cite: 1242]',
            5 => 'Civilians and noncombatants that failed to evacuate the area are on the field. Add 4 unarmed infantry units or 4 infantry BSPs to your opponent\'s force. These units may not attack, and move as one initiative activation. Any player that destroys one of these units will suffer an immediate -1 penalty to their reputation for each unit destroyed. [cite: 1244, 1245, 1246, 1247]',
            6 => 'Ammo caches have been located. Pick three building hexes on the map and mark them on the map with the note tool. If you are the attacker for this track, then you must destroy these hexes or suffer a -50 VP to your score per hex you failed to destroy (up to -150). If you are the defender this track, then your opponent successfully destroying all three hexes will result in +100 VP for them on this track. [cite: 1248, 1249, 1250]',
            7 => 'Haste in deploying assets has left intel on this area spotty. All opponent Support vehicles may be deployed as Hidden Units anywhere on their half of the map. Refer to page 82 of BattleMech Manuel or page 259 of Total Warfare to find the Hidden Unit rules. [cite: 1252, 1253, 1254]',
            8 => 'Local insurgents have thrown their lot in with your opponent. Give your opponent 2 Veteran Jump Infantry BSPs. [cite: 1255]',
            9 => 'Roll twice and apply both effects. Do not reroll No Complication, but reroll any other duplicate effects. [cite: 1256]'
        ],
        'Solaris' => [
            1 => 'No Complication [cite: 1301]',
            2 => 'No Complication [cite: 1301]',
            3 => 'No Complication [cite: 1301]',
            4 => 'No Complication [cite: 1301]',
            5 => 'A VTOL carrying VIP spectators crashes on the field. Whichever side is successful in this track will gain +1 reputation. Destroying one of the VTOLs will result in an immediate -1 reputation to whichever player destroyed the VTOL (accidental crashes or skidding off the map will cause no effect unless caused by intentional damage). Add two tokens to the map at elevation 6 to represent the VTOLs. Use the Warrior Attack Helicopter BSP to represent each unit or use the datasheet for a Sprint Scout Helicopter for players looking to avoid BSP rules. [cite: 1303, 1304, 1305, 1306]',
            6 => 'The mission was set for dawn. Add +1 to hit with all weapon attacks. [cite: 1307]',
            7 => 'Enemy forces have placed target beacons on your units. All missile attacks by the opponent have a -1 to hit against your Mech units. [cite: 1308, 1309]',
            8 => 'Support was delayed for this tournament round. For support units you receive a single 4/5 Savannah Master Hovercraft this track (do not select any other units with your remaining BV). If using BSPs, you receive a single Savannah Master Hovercraft BSP. [cite: 1311, 1312, 1313]',
            9 => 'Roll twice and apply both effects. Do not reroll No Complication, but reroll any other duplicate effects. [cite: 1314]'
        ],
    ];

    // roll = 1d6 + commandRightsBonus (0-3). Result >= 9 means roll twice.
    public static function lookup(string $terrain, int $roll): string {
        $table = self::TABLES[$terrain];
        if ($roll >= 9) {
            return "Double complications! ". $table[max(1, min(8, $roll))] . " " . $table[max(1, min(8, $roll))];
        }
        return $table[max(1, min(8, $roll))];
    }
}
