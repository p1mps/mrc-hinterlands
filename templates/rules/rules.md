# MRC Hinterlands League — Rules Summary

> This is a summary of the core rules from the MRC Hinterlands League rulebook. Full rules are a supplement to Hot Spots: Hinterlands. Only pages 1–8 are essential for league play; the rest are additional options.

---

## Building Your Force

* **Faction Selection:** Select a faction from the Master Unit List (masterunitlist.info) and choose starting units from that faction's MUL.
* **Starting Funds:** Start with 3000 BV to spend and 3000 Support Points (SP).
* **Unit Composition:** You must select at least one Mech. You may add a second Mech, a vehicle, or two Battle Armor units.
* **Restrictions:** No aerospace units, no AE-damage units (artillery, Arrow IV), no Unique units.
* **IlClan Faction:** All units must come from the same IlClan faction MUL. Experimental tech is allowed.
* **Starting Experience:** Each unit starts at Gunnery 4 / Piloting 5 with 150 XP to allocate. Battle Armor squads get 75 XP each.

---

## League Play Structure

* **Monthly Contracts:** Each month a Hot Spot contract is posted in Discord. Players pair up, choose one of the two opposing contracts, and complete it. Alternatively, roll a random contract via Dobless Information Services or the MRC Contract Generator.
* **Contract Limit:** A company may only receive credit for one contract per month.
* **Downtime:** After each contract, players receive one month of downtime at a Hiring Hall of their choice. Downtime at a Hiring Hall costs a flat 50 SP regardless of scale.
* **Overtime (Taking One for the Team):** If contracts for the month are complete, players may use the Taking One for the Team rules to run additional contracts with a new mercenary company.
* **Responsibilities:** Players maintain their own Force record sheets. Incomplete contracts reset all accrued damage, salvage, and payments.

---

## Reputation

### Gaining and Losing Reputation

* All companies start with Reputation 1.
* Winning a full contract (at least half the tracks) gains +1 reputation.
* You never lose reputation for losing a contract, only for breaching it (–3) or abandoning objectives.

### Spending Reputation

* Spend 1 reputation to shift a RAT result by ±1, or to negate a Command Complication roll.
* You must keep at least 1 reputation to spend.

### Reputation Progression

Your company's reputation determines your force scale and military rank as you grow:

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>Reputation</th><th>Scale</th><th>Rank</th></tr>
  </thead>
  <tbody>
    <tr><td>0–2</td><td>1</td><td>Sergeant / Point Commander</td></tr>
    <tr><td>3–10</td><td>2</td><td>Lieutenant / Star Commander</td></tr>
    <tr><td>11–20</td><td>3</td><td>Captain / Star Captain</td></tr>
  </tbody>
</table>

* **Support Rating:** Modifies contract terms based on your company's standing. A Support Rating of C gives a 75% modifier.
* **Loss of Scale:** Losing reputation can cause you to lose force scale (but not rank).

---

## Salvage

### General Salvage Rules

Salvage is the right to claim destroyed or crippled enemy units left on the field after a track. The victor may claim salvage if they successfully complete the track. Salvaged mechs are in their end-of-battle condition and must be repaired. If you decline salvage, the original owner may ransom the unit back.

### Scrapyard Mechs

When purchasing from the scrapyard:

* **Acquisition cost:** BV / 2 (in SP)
* **Repair cost:** Tonnage × 3 (IS) or × 4.5 (Mixed/Clan) — since they're always Crippled
* **BV for assignment:** Full BV (as if undamaged)
* **Condition:** Always arrive in Crippled condition — they cannot be repaired to better than Crippled.
* **No contract attachment:** Scrapyard mechs do not attach to active contracts, so salvage rights percentages are irrelevant.

### Battlefield Salvage

After a track, the victor may attempt to salvage enemy units left on the field. A salvage check is made by rolling 2d6 with thresholds based on unit type (Mech: 4+, Vehicle: 6+, Battle Armor: 7+). Failure means the mech is destroyed — no salvage.

If successful, the mech is acquired in its end-of-battle condition. The actual cost depends on your active contract's salvage rights terms.

### Salvage Rights

Salvage rights is a contract term that determines how the cost of acquiring a salvaged mech is split between you and the employer:

* **Percentage (e.g., 50%, 75%, 100%):** You keep that percentage of the salvage value. The acquisition cost is: floor(BV/2) × (1 − salvageRightsPercent/100). A 50% salvage rights means you pay half the salvage value; 100% means you pay the full salvage value.
* **Exchange:** The employer covers all repair costs. You receive a payout of 25% of the salvage value (floor(BV/2) × 0.25) as your share.
* **None (0%):** Acquisition is not allowed — you cannot claim the mech.

Salvage value (the base reference for all calculations) = floor(BV / 2). Repair cost is calculated separately based on the mech's damage state: Tonnage × 3 (Crippled, IS) or × 4.5 (Mixed/Clan), Tonnage × 5 (Destroyed, IS) or × 7.5 (Mixed/Clan), etc. Both the acquisition cost and repair cost must be paid in SP to bring the mech into your roster.

---

## Force Scale & Combat Pay

### Force Scale

The force scale determines the maximum force size and associated costs:

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>Scale</th><th>Force</th><th>BV</th></tr>
  </thead>
  <tbody>
    <tr><td>1</td><td>Mixed Lance of 'Mechs and Support</td><td>3,000 BV</td></tr>
    <tr><td>3</td><td>Mixed Company</td><td>9,000 BV</td></tr>
  </tbody>
</table>

* **Deployment Limit:** The force you may deploy per track is capped by the contract's scale. In this league, the limit is 3000 BV and 2 units per scale.
* **Unit limits:** Checked once at the start of each contract.
* **Infantry:** Battle armor and conventional infantry may be fielded at 2-for-1 against other unit slots, but this does not bypass the BV cap.

### Maintenance and Combat Pay

* **Maintenance:** 500 SP × contract scale per month, paid regardless of whether a track is played.
* **Combat Pay (No objectives achieved):** No pay
* **Combat Pay (At least one objective, fewer Victory Points):** Half pay (250 × scale)
* **Combat Pay (More Victory Points than opponent):** Full pay (500 × scale)
* **Combat Pay (All objectives achieved):** Half-again pay (750 × scale)

---

## Special Rules

* **Commander:** Each force designates one Commander unit per track (written down, not revealed). No initiative penalty if the Commander is destroyed.
* **Forced Withdrawal:** Not in effect for player-generated forces. Crippled units may not perform pickup or scan actions. Forces without a company roster (opposing force table units) do use Forced Withdrawal.
* **Raid:** Mechs carrying Jump MP components must make a PSR at +3 or lose the components. All units carrying components have Run/Flank MP reduced by 1.
* **Scanning:** A unit within 2 hexes may declare a scan instead of firing. Roll 2d6 in the End Phase — 7+ succeeds. Modifiers: +2 (no physical attack), +2 (Active Probe within 2 hexes), +2 (started within 2 hexes), –2 (used jump/VTOL MP), –2 (fired weapons, unless carrying unjammed Active Probe). A successful scan forces the defender to reveal relevant unit information.

---

## Optional Rules

Used by mutual agreement. Veteran players should defer to less experienced players. Assumed active for MRC games using Total Warfare:

* Floating Critical Hits (BMM p. 45 / TO:AR p. 75)
* Careful Stand (BMM p. 19 / TO:AR p. 22)
* Skin-of-the-teeth Ejection (BMM p. 81 / TO:AR p. 165)
* One-Armed Prone Fire (BMM p. 30 / TO:AR p. 83)
* ECCM (TO:AR p. 98–99)

Special Munitions and Command Consoles may also be used by agreement.

---

## Vehicles & Infantry

* **Full Units:** On the company roster with a pilot/crew. Pay all normal repair and training costs. May be salvaged. Conventional infantry is purchased in "barracks" — one barracks provides one unit per contract.
* **Support Units:** Available at any track without being on the roster. Must be from the Periphery MUL, have no AE weapons, and be non-Mech/non-aerospace. Always 4/5 skill. Cannot be salvaged. Max 500 BV × scale.
* **BSAs (Battlefield Support Assets):** Optional for in-person or Tabletop Simulator play. Cannot be used alongside Support Units in the same track.

---

## Contract Structure & Terms

### The Step Table

Every contract is defined by five terms, each assigned a step on a 13-step table. Higher steps mean better terms for the mercenary company. Each term is rolled independently on 2d6, with possible modifiers from the employer, affiliation, and contract type. The final step is clamped to the range 1–13.

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>Step</th><th>Base Pay</th><th>Command Rights</th><th>Salvage Rights</th><th>Support Terms</th><th>Transportation</th></tr>
  </thead>
  <tbody>
    <tr><td>1</td><td>50%</td><td>—</td><td>—</td><td>-</td><td>—</td></tr>
    <tr><td>2</td><td>55%</td><td>—</td><td>—</td><td>Straight / 20%</td><td>—</td></tr>
    <tr><td>4</td><td>70%</td><td>—</td><td>10%</td><td>Straight / 60%</td><td>—</td></tr>
    <tr><td>6</td><td>90%</td><td>—</td><td>30%</td><td>Straight / 100%</td><td>25%</td></tr>
    <tr><td>8</td><td>110%</td><td>Liaison</td><td>50%</td><td>Battle / 20%</td><td>75%</td></tr>
    <tr><td>10</td><td>130%</td><td>—</td><td>70%</td><td>Battle / 40%</td><td>—</td></tr>
    <tr><td>12</td><td>175%</td><td>—</td><td>90%</td><td>Battle / 75%</td><td>—</td></tr>
    <tr><td>20</td><td colspan="5">Special high-value contract (negotiated terms)</td></tr>
  </tbody>
</table>

### Term Definitions

* **Base Pay:** The base monthly payment is calculated as 500 SP × contract scale × (basePayPercent / 100). Base pay is paid per month of contract duration, regardless of how many tracks are completed.
* **Command Rights (Integrated +3):** Full command authority over allies.
* **Command Rights (House +2):** High authority, common with noble employers.
* **Command Rights (Liaison +1):** Advisory role; default when no command rights are specified.
* **Command Rights (Independent +0):** No authority over allies.
* **Salvage Rights:** Determines what percentage of a destroyed or crippled enemy unit's value the mercenary company is entitled to.
* **Support Terms (Straight / X%):** Reduces all repair costs by X%. At 100%, repairs are free.
* **Support Terms (Battle / X%):** Provides a support point payout equal to X% of a destroyed unit's value, compensating for the loss of the unit.
* **Support Terms (None):** No support benefits.
* **Transportation:** Covers the cost of moving your force to and from the contract location. Cost is 300 SP × scale per journey.
* **Contract Type (Raid):** 3 months. Quick strike operations.
* **Contract Type (Expedition):** 6 months. Extended operations away from home.
* **Contract Type (Garrison):** 6 months. Defensive holding contracts.
* **Contract Type (Retainer):** 6 months. Standing force on call.
* **Contract Type (Invasion):** 6 months. Offensive push into enemy territory.

### Negotiations

Before accepting a contract, you may negotiate its terms by spending reputation points (maximum 2 × scale). You can shift any term up or down by one step on the table. For every 2 steps you sacrifice in one term, you may gain 1 step in another — this exchange can be used twice per contract. Number of Tracks (1–5) is negotiated separately and is not on the step table.

---

## Contract Types & Mission Types

### Mission Types Per Contract Type

When generating a contract, a 1d6 roll on the Track Intensity Table determines the mission type for each track. Different contract types offer different mission pools:

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1d6</th><th>Raid</th><th>Expedition</th><th>Garrison</th><th>Invasion</th><th>Retainer</th></tr>
  </thead>
  <tbody>
    <tr><td>1</td><td>Crush the Head</td><td>Ambush</td><td>Straight Fight</td><td>Straight Fight</td><td>Straight Fight</td></tr>
    <tr><td>2</td><td>Secure</td><td>Evacuation</td><td>Secure</td><td>Crush the Head</td><td>Evacuation</td></tr>
    <tr><td>3</td><td>Ambush</td><td>Reconnaissance</td><td>Secure</td><td>Secure</td><td>Reinforce</td></tr>
    <tr><td>4</td><td>Reconnaissance</td><td>Reconnaissance</td><td>Reconnaissance</td><td>Reinforce</td><td>Reinforce</td></tr>
    <tr><td>5</td><td>Bounding Retreat</td><td>Bounding Retreat</td><td>Duel</td><td>Calamity</td><td>Duel</td></tr>
    <tr><td>6</td><td>Demolition</td><td>Calamity</td><td>Calamity</td><td>Demolition</td><td>Demolition</td></tr>
  </tbody>
</table>

### Quick Reference by Mission Type

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>Mission</th><th>Available On</th><th>Description</th></tr>
  </thead>
  <tbody>
    <tr><td>Straight Fight</td><td>Garrison, Invasion, Retainer</td><td>Destroy >50% of the opponent's force. Classic engagement.</td></tr>
    <tr><td>Crush the Head</td><td>Raid, Invasion</td><td>Target the enemy commander. Capture for maximum VP.</td></tr>
    <tr><td>Secure</td><td>Raid, Garrison, Invasion</td><td>Control the central objective point.</td></tr>
    <tr><td>Ambush</td><td>Raid, Expedition</td><td>Defender hides units; attacker must scan objective hexes.</td></tr>
    <tr><td>Evacuation</td><td>Expedition, Retainer</td><td>Attacker extracts VIPs; Defender holds the line.</td></tr>
    <tr><td>Reconnaissance</td><td>Raid, Expedition, Garrison</td><td>Attacker scans enemy units and withdraws.</td></tr>
    <tr><td>Reinforce</td><td>Invasion, Retainer</td><td>Attacker must break through and exit the Defender's edge.</td></tr>
    <tr><td>Duel</td><td>Garrison, Retainer</td><td>One-on-one fight; interference by other units is penalised.</td></tr>
    <tr><td>Bounding Retreat</td><td>Raid, Expedition</td><td>Defender holds then withdraws in good order; Attacker routs them.</td></tr>
    <tr><td>Calamity</td><td>Expedition, Garrison, Invasion</td><td>Both sides deploy hidden; ECM is doubled, active probes disabled.</td></tr>
    <tr><td>Demolition</td><td>Raid, Invasion, Retainer</td><td>Attacker plants demolition charges on objective buildings.</td></tr>
  </tbody>
</table>

---

## Command Complications

At the start of each track, the primary player rolls 1d6 to determine possible complications. Add +1 if the player has Liaison Command, +2 for House Command, and +3 for Integrated Command. Roll Mechanic: 1d6 + commandRightsBonus (0–3).

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6 + Bonus</th><th>Effect</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate results.</td></tr>
  </tbody>
</table>

### Terrain Complication Tables

**Desert**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>The desert sun is reducing the efficacy of your heatsinks. All heat-tracking units suffer from +2 heat every turn.</td></tr>
    <tr><td>6</td><td>The desert winds have kicked up a sandstorm. All units' DE and P weapons suffer from +1 to hit this track.</td></tr>
    <tr><td>7</td><td>Maintenance was rushed, and desert sand has found its way into one of your mech's actuators. Roll 1d6 to select your right or left arm, then roll on each arm's crit table, rerolling any result that is not an actuator.</td></tr>
    <tr><td>8</td><td>The sand has heatsinks working overtime. Any time a unit has 5+ heat after the heat phase, reduce the heat dissipation capacity of the mech by 1. A coolant truck can be used to replace any cooling capacity lost mid-battle.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

**Grasslands**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>Bad batch of local beer means no vehicular support is available. Any unit with tracked, wheeled, or hover MP may not be selected.</td></tr>
    <tr><td>6</td><td>Add a 3/4 Elemental III Battle Armor [Flamer] (Sqd5) to your opponent's force. Destroying it with a single unit allows you to acquire it for 500 SP.</td></tr>
    <tr><td>7</td><td>Overcast conditions have worsened. Your units (not the opponent's) suffer from a +1 to hit with all weapon attacks.</td></tr>
    <tr><td>8</td><td>Local air support has been scrambled against you. Give your opponent 1d2 Light Strike BSPs this track.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

**Hills**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>News choppers have arrived. Whichever side is successful gains +1 reputation. Destroying one of the VTOLs results in an immediate −1 reputation penalty.</td></tr>
    <tr><td>6</td><td>The mission was set for dawn. Add +1 to hit with all weapon attacks.</td></tr>
    <tr><td>7</td><td>Infiltration by enemy forces has resulted in target beacons being placed in your units. All missile attacks by the opponent have a −1 to hit against your Mech units.</td></tr>
    <tr><td>8</td><td>For support units you receive a single 4/5 Savannah Master Hovercraft this track.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

**Light Industrial**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>Your presence has been noted by local security forces. Reduce the turn limit on this track by 1d3 to avoid entanglements.</td></tr>
    <tr><td>6</td><td>The industry in the area is playing havoc with target locks. Your Missile weapons suffer from a −1 to cluster rolls at medium range, and a −2 at long range.</td></tr>
    <tr><td>7</td><td>This site has been marked as strategically important. If any buildings take damage from your mechs you will receive a −100 VP penalty.</td></tr>
    <tr><td>8</td><td>(Attacker Only) The locals have found a Schrek PPC Carrier. Add a 5/6 Schrek PPC Carrier to your opponent's force as a support unit this track.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

**Mountains**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>High mountain passes cause issues for some vehicles. All units with Hover or VTOL MP reduce their cruising MP by 2.</td></tr>
    <tr><td>6</td><td>A snowstorm applies a +1 to all weapon attacks, and all heat-tracking units may sink 1 more heat this track.</td></tr>
    <tr><td>7</td><td>The player may not select units or BSPs with WiGE or VTOL MP, and all support units or BSPs deploy on Round 2 from the player's home edge.</td></tr>
    <tr><td>8</td><td>A bad illness has brought down your best MechWarrior. The MechWarrior with the lowest total skill on your roster may not be deployed.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

**Savannahs**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>A local farmer has decided to make this a problem for you. Give your opponent 1 Thumper Artillery Strike BSP this track.</td></tr>
    <tr><td>6</td><td>The opponent may opt to deploy their support units/BSPs from any board edge except yours.</td></tr>
    <tr><td>7</td><td>Poor navigation took you through a patch of rough terrain. Randomly assign a foot critical to one of your mechs.</td></tr>
    <tr><td>8</td><td>At the End Phase of every round, roll 1d6. If 5 or higher, apply 5 damage to one randomly determined unit.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

**Urban**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>Add 4 unarmed infantry units or 4 infantry BSPs to your opponent's force. Any player that destroys one suffers an immediate −1 reputation penalty.</td></tr>
    <tr><td>6</td><td>Pick three building hexes on the map. The attacker must destroy these hexes or suffer a −50 VP penalty per hex. If defending, your opponent gains +100 VP for destroying all three.</td></tr>
    <tr><td>7</td><td>All opponent Support vehicles may be deployed as Hidden Units anywhere on their half of the map.</td></tr>
    <tr><td>8</td><td>Local insurgents have thrown their lot in with your opponent. Give your opponent 2 Veteran Jump Infantry BSPs.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

**Wetlands**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>Reduce all your units with either Wheeled or Tracked MP by 1 for the first three rounds of combat.</td></tr>
    <tr><td>6</td><td>Local support was limited at best. All your support units are 5/6.</td></tr>
    <tr><td>7</td><td>All opponent Mechs subtract 1 MP from all movement costs through swamp hexes and do not need to make bog down checks.</td></tr>
    <tr><td>8</td><td>The terrain of the AO has your force off balance. Roll 3d6 and drop the highest number for your initiative for this track.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

**Wooded**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>Your opponent gains an additional 3 4/4 Beast Infantry (Tariq) (Auto-rifle) for this track.</td></tr>
    <tr><td>6</td><td>Fire danger is at its peak. Triple any damage dealt to woods hexes with H, DE, or P weapons.</td></tr>
    <tr><td>7</td><td>Local forces have come out in opposition to you. Add 1 4/5 Myrmidon Medium Tanks to your opponent's team.</td></tr>
    <tr><td>8</td><td>The opponent is being supported by artillery. Give your opponent 1 Long Tom Artillery Strike BSP.</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

**Alien**
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>1D6</th><th>Complication</th></tr>
  </thead>
  <tbody>
    <tr><td>1–4</td><td>No Complication.</td></tr>
    <tr><td>5</td><td>Any conventional infantry fielded must be XCT troopers, and any vehicles fielded must have the Environmental Sealing chassis modification.</td></tr>
    <tr><td>6</td><td>You must bring a unit with a Remote Sensor Dispenser and deploy it in all 4 quadrants of the map this round. Failing to deploy any nets −1 reputation.</td></tr>
    <tr><td>7</td><td>Choose one type of weapon — DE, DB, or M. All weapons with that type of damage gain −1 to hit, all other weapon types gain +1 to hit.</td></tr>
    <tr><td>8</td><td>Your Gauss Rifles may not be fired for 1d4 rounds. All PPCs run the risk of exploding (on a to-hit roll of 2, the PPC will detonate for its damage value as an ammunition explosion).</td></tr>
    <tr><td>9+</td><td>Roll twice and apply both effects. Do not reroll "No Complication," but reroll any other duplicate effects.</td></tr>
  </tbody>
</table>

---

## Time Between Tracks — SP Activity Costs

### Activity Cost Table

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>Activity</th><th>Cost</th></tr>
  </thead>
  <tbody>
    <tr><td>Repair (Armor damage only)</td><td>Tonnage × 0.5 (IS) / × 0.75 (Mixed/Clan)</td></tr>
    <tr><td>Repair (Structure/Critical damage)</td><td>Tonnage × 2 (IS) / × 3.5 (Mixed/Clan)</td></tr>
    <tr><td>Repair (Crippled)</td><td>Tonnage × 3 (IS) / × 4.5 (Mixed/Clan)</td></tr>
    <tr><td>Repair (Destroyed)</td><td>Tonnage × 5 (IS) / × 7.5 (Mixed/Clan)</td></tr>
    <tr><td>Reconfigure Unit</td><td>Tonnage / 2</td></tr>
    <tr><td>Purchase Mech</td><td>Battle Value</td></tr>
    <tr><td>Sell a Mech</td><td>Battle Value / 2</td></tr>
    <tr><td>Rearm (per ton of ammo)</td><td>10 (20 for special ammo)</td></tr>
    <tr><td>Hire Unnamed MechWarrior/Crew</td><td>100</td></tr>
    <tr><td>Hire Named MechWarrior/Crew</td><td>150</td></tr>
    <tr><td>Heal Pilot</td><td>30 per Wound box</td></tr>
    <tr><td>Heal/Replace BA Trooper</td><td>20 per trooper lost</td></tr>
    <tr><td>Clan Doctrine Training</td><td>200</td></tr>
  </tbody>
</table>

### General Rules

* **Repair Time:** Repairing a unit makes it unavailable for the rest of the month.
* **Truly Destroyed:** A unit is truly destroyed only if its center torso internal structure is eliminated — it is gone permanently.

### Named Pilots

* Max 4 on roster.
* Earn XP (1 SP per XP, up to 200 XP × scale per track).
* MVP of each track earns +20 bonus XP.

### SP Allocation Between Tracks

* **Combat Pay Allocation:** Dedicate a portion of combat pay to train Named Pilots who participated in that mission.
* **Limits by Scale:** Maximum 200 SP per Scale of the track to your participating pilots as a group.
* **Even Distribution:** SP is divided evenly among all Named Pilots who participated in the track.
* **Individual Cap:** Maximum 100 SP per pilot per track from this allocation.

### MVP Bonus

Name one surviving pilot who participated as the MVP. The MVP receives a bonus of 20 SP (free bonus SP).

### Edge System

* **Starting Edge:** Every Named Pilot begins with 1 Edge token.
* **Acquiring Edge:** Accumulating 60 SP unlocks a second token, 120 SP unlocks a third, and 200 SP unlocks a fourth.
* **Handicap Effect:** Every Edge token a pilot holds increases their Handicap value.
* **Add to Attack Roll:** Spend one Edge token to add +1 to the final result of an attack roll.
* **Reroll Motive Systems Damage:** Spend one Edge token to reroll a Motive Systems Damage Table result.
* **Reroll Critical Hit:** Spend one Edge token to completely reroll a Determining Critical Hits Table result.
* **Activate Edge Abilities:** Spend Edge tokens to trigger unlocked special Edge Abilities.

### Edge Abilities (SPAs) - Total Warfare Rules

A pilot unlocks a new ability slot when their allocated SP reaches 60, 180, 360, 600, and 900 SP.

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>Ability</th><th>Cost</th><th>Effect</th></tr>
  </thead>
  <tbody>
    <tr><td>Assassin</td><td>1 Edge</td><td>Increase rear-arc damage rating by 1, or adjust hit location roll by 1 to try and strike rear torso armor.</td></tr>
    <tr><td>Bulwark</td><td>1 Edge</td><td>Reduce physical attack damage received by 5.</td></tr>
    <tr><td>Cautious</td><td>1 Edge</td><td>Instantly change facing to any direction at the start of Combat Phase.</td></tr>
    <tr><td>Coolant Flush</td><td>Up to 2 Edge</td><td>Reduce heat by 4 per token spent.</td></tr>
    <tr><td>Forward Observer</td><td>1 Edge</td><td>Make your own attack this Combat Phase without suffering the +1 Target Number penalty when spotting.</td></tr>
    <tr><td>Jumping Jack</td><td>1 Edge</td><td>Reduce short-range jumping Target Number penalty to +2.</td></tr>
    <tr><td>Marksman</td><td>1 Edge</td><td>Add 1 to Crit Table roll.</td></tr>
    <tr><td>Melee Specialist</td><td>1 Edge</td><td>Completely reroll a Physical or Melee attack.</td></tr>
    <tr><td>Nimble</td><td>1–2 Edge</td><td>Gain +1 TMM and move through enemy units. Costs 2 Edge if sprinted TMM is 3+ or Walking MP is 7+.</td></tr>
    <tr><td>Patient</td><td>1 Edge</td><td>Completely reroll all attacks if your unit used Standstill movement.</td></tr>
    <tr><td>Protector</td><td>1 Edge</td><td>Prevent entire damage from one weapon to a friendly, smaller unit within 1 hex.</td></tr>
    <tr><td>Speed Demon</td><td>1 Edge</td><td>Gain +1 MP, or +2 MP if sprinting.</td></tr>
  </tbody>
</table>

### Unnamed Pilots

* Always cost 100 SP to hire.
* Always Gunnery 4 / Piloting 5.
* Cannot earn XP.

### Handicap

The player with the lower average handicap earns a BSP bonus equal to the handicap difference × 10. Unnamed pilots in a unit of 500 BV or more count as 0 handicap.

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>Skill</th><th>Gunnery</th><th>Piloting / Anti-Mech</th></tr>
  </thead>
  <tbody>
    <tr><td>6</td><td>0</td><td>0</td></tr>
    <tr><td>5</td><td>0</td><td>0</td></tr>
    <tr><td>4</td><td>0</td><td>4</td></tr>
    <tr><td>3</td><td>12</td><td>8</td></tr>
    <tr><td>2</td><td>28</td><td>28</td></tr>
    <tr><td>1</td><td>48</td><td>48</td></tr>
    <tr><td>0</td><td>88</td><td>88</td></tr>
  </tbody>
</table>

#### Edge tokens handicap
* 2 Edge Tokens: 2 Handicap
* 3 Edge Tokens: 5 Handicap
* 4 Edge Tokens: 8 Handicap
* 5 Edge Tokens: 12 Handicap
* 6 Edge Tokens: 17 Handicap
* 7 Edge Tokens: 22 Handicap
* 8 Edge Tokens: 29 Handicap
* 9 Edge Tokens: 36 Handicap
* 10 Edge Tokens: 44 Handicap

---

## Dropship Mechanics

Your company gets one dropship (unique per company at the DB level). It serves as your mobile repair bay.

### Two Limits: Tonnage + Mekbays

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>LIMIT</th><th>WHAT IT TRACKS</th></tr>
  </thead>
  <tbody>
    <tr><td>Tonnage (maxCapacity)</td><td>Total tons of all units on board (roster + salvage)</td></tr>
    <tr><td>Mekbays (mekbayCapacity)</td><td>Number of roster units currently aboard (each unit = 1 mekbay)</td></tr>
  </tbody>
</table>

* Salvaged mechs only consume tonnage — they don't use mekbays.
* When you board a roster unit, the system checks if the tonnage and mekbay capacity limits are met.

### Creating a Dropship

* Minimum 40 tons (allows at least 2 × 20-ton mechs).
* Initial mekbay capacity starts at 0.
* You specify name, maxCapacity, and mekbayCapacity when creating.

### Upgrading: 200 SP Per Upgrade

<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary">
    <tr><th>UPGRADE OPTION</th><th>EFFECT</th></tr>
  </thead>
  <tbody>
    <tr><td>+30 tons</td><td>Increase maxCapacity by 30</td></tr>
    <tr><td>+1 mekbay</td><td>Increase mekbayCapacity by 1</td></tr>
  </tbody>
</table>

### Why Board Salvage to the Dropship?

To repair them, you must board them onto your dropship (costs tonnage only) and pay the repair SP. The dropship is your only repair facility.

### Key Rules

* Only one dropship per company.
* You can unassign mech/units at any time (free).
* You can delete the dropship (but all mechs are unassigned first).

---

## Debt & Breaking Contract

* **Debt:** If you can't pay maintenance or transport, you go into debt. While in debt, SP Activities and unit training are suspended. A force cannot be in debt for more than 6 league months. While in debt you may sell assets, retire your force, or skip one track (–1 reputation, available once).
* **Breaking Contract:** Incurs a –3 reputation penalty (–1 if force is below 2/3 of the scale BV limit). The contract ends immediately. The other player gains their reputation bonus for completing.

---

## Big Leagues

Big Leagues lets you join special mercenary commands with unique requirements, benefits, and purchase options.

### 21st Centauri Lancers

* **Requirements:** No broken contracts, no piracy, Scale 2+, Reputation 3+, Esprit de Corps Special Command
* **Benefits:** Treated as one higher Reputation in negotiations; half-cost Formations.
* **Purchase Options:** Crossbow T, BattleMaster C, Rifleman C 2, Hunchback IIC 5, LRM Carrier C, SRM Carrier C, Vedette Medium Tank V9, Condor Heavy Hover Tank Ultra.

### Shadowblacks

* **Requirements:** Must have participated in piracy, Scale 2+, 500 SP initiation fee.
* **Benefits:** 20% bonus to piracy loot sale value; half-cost Formations; Clan tech upgrade options.

---

## Taking One For The Team (Overtime)

Regular military commands (House/Clan) can also use this option instead of standard mercenary play:

* **Reserve Forces:** House/Clan forces get +50% BV for reserve forces.
* **Support Points:** Regular military commands get 300 SP per Scale.
* **No Negotiation:** Regular military commands have no contract terms or negotiation.
* **Promotion:** 0–2 Reputation = Scale 1 (Sergeant); 3–10 = Scale 2 (Lieutenant); 11–20 = Scale 3 (Captain).
* **Losing Reputation:** Can cause loss of force scale (but not rank).

---

## Plotting the Monthly Schedule (The Dash Notation)

Roll 1D6 on the Track Intensity Table to determine the sequence of numbers separated by dashes (e.g., 0-1-1). Each number represents the exact number of tracks you must deploy for in that month.

### Examples

* **Intensity 0-1-1:** Quiet Month 1, one battle in Month 2, and one battle in Month 3.
* **Intensity 0-0-2:** No combat for the first two months, two consecutive battles in Month 3.

### Campaign Implications: The Danger of "Double-Track" Months

* **No automatic full recovery:** Standard rules state that any repaired unit is unavailable for the next track in that same month.
* **Repair Time Rolls:** Roll 2D6 on the Repair Time Table between the two tracks to determine if you have time for Hasty Repairs, Standard Repairs, Extended Repairs, or All the Time in the World.

#### Repair Time Rolls
* **Roll of 2 (Hasty Repairs):** Your techs are completely rushed. There is only enough time to perform armor repairs and rearm your ammunition. Both you and your opponent are forced to use the exact same units from the previous track where possible (though truly destroyed units can be replaced up to your campaign's Scale limits).
* **Roll of 3–9 (Standard Repairs):** You must abide by the standard repair time rules. Any of your units undergoing structure, critical, or crippling repairs are unavailable for the second track. Only Mechs undergoing armor-only repairs or rearming can deploy.
* **Roll of 10–11 (Extended Repairs):** Your techs catch a break. In addition to standard armor repairs, any non-crippled and non-destroyed unit repairs (such as fixing structure damage or critical hits) can be fully completed before the second track begins. This allows those repaired units to deploy immediately instead of sitting out.
* **Roll of 12 (All the Time in the World):** The ultimate stroke of logistical luck. Your techs have unlimited time: all non-destroyed unit repairs (even repairing heavily crippled Mechs) and all MechWarrior wounds healed for that month are fully resolved before the next track begins.

---

## Random Contracts (Dobless Information Services)

Generate one random contract offer per month at the Almotacen Hiring Hall.

### Jumping Between Systems (Deprecated)

Roll 1D6 for the number of jumps from Almotacen, then 1D6 for the specific system.

### Contract Generator

Contracts are generated in order: contract type → employer → pay rate → salvage rights → support terms → transportation → command rights. The employer type adds or subtracts steps from the rolled result.

### BSP

---

#### Battlefield Support Table
<table class="table table-sm table-bordered mb-0">
  <thead class="table-secondary text-center">
    <tr>
      <th>Support Type</th>
      <th>Target Number*</th>
      <th>Damage Value Groupings**</th>
      <th>Damage Type</th>
      <th>BSP Cost</th>
    </tr>
  </thead>
  <tbody class="text-center">
    <tr>
      <td colspan="5"><strong>Offensive Aerospace Support</strong></td>
    </tr>
    <tr>
      <td>Light Strike</td>
      <td>5</td>
      <td>2</td>
      <td>N/A</td>
      <td>2</td>
    </tr>
    <tr>
      <td>Light Bombing&dagger;</td>
      <td>5</td>
      <td>3</td>
      <td>AE</td>
      <td>3</td>
    </tr>
    <tr>
      <td>Heavy Strike</td>
      <td>6</td>
      <td>4</td>
      <td>N/A</td>
      <td>3</td>
    </tr>
    <tr>
      <td>Heavy Bombing&dagger;</td>
      <td>7</td>
      <td>6</td>
      <td>AE</td>
      <td>4</td>
    </tr>
    <tr>
      <td>Strafing</td>
      <td>7</td>
      <td>3</td>
      <td>N/A</td>
      <td>5</td>
    </tr>
    <tr>
      <td colspan="5"><strong>Defensive Aerospace Support</strong></td>
    </tr>
    <tr>
      <td>Light Air Cover</td>
      <td>&mdash;</td>
      <td>N/A</td>
      <td>N/A</td>
      <td>1</td>
    </tr>
    <tr>
      <td><em>&mdash; Light Strike</em></td>
      <td>3</td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td><em>&mdash; Light Bombing</em></td>
      <td>4</td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td><em>&mdash; Heavy Strike</em></td>
      <td>9</td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td><em>&mdash; Strafing/Heavy Bombing</em></td>
      <td>11</td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td>Heavy Air Cover</td>
      <td>&mdash;</td>
      <td>N/A</td>
      <td>N/A</td>
      <td>2</td>
    </tr>
    <tr>
      <td><em>&mdash; Light Strike/Bombing</em></td>
      <td>9</td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td><em>&mdash; Heavy Strike</em></td>
      <td>5</td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td><em>&mdash; Strafing/Heavy Bombing</em></td>
      <td>6</td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td colspan="5"><strong>Artillery Support&dagger;</strong></td>
    </tr>
    <tr>
      <td>Thumper</td>
      <td>8</td>
      <td>3/1&dagger;&dagger;</td>
      <td>AE</td>
      <td>3</td>
    </tr>
    <tr>
      <td>Sniper</td>
      <td>8</td>
      <td>4/2&dagger;&dagger;</td>
      <td>AE</td>
      <td>4</td>
    </tr>
    <tr>
      <td>Long Tom</td>
      <td>8</td>
      <td>5/3/1&dagger;&dagger;</td>
      <td>AE</td>
      <td>6</td>
    </tr>
    <tr>
      <td><em>(Copperhead)</em></td>
      <td>&Dagger;</td>
      <td>&Dagger;</td>
      <td>N/A</td>
      <td>&Dagger;</td>
    </tr>
    <tr>
      <td><em>(Illumination/Smoke)</em></td>
      <td>8</td>
      <td>&Dagger;&Dagger;</td>
      <td>N/A</td>
      <td>&Dagger;&Dagger;</td>
    </tr>
    <tr>
      <td colspan="5"><strong>Minefield Support</strong></td>
    </tr>
    <tr>
      <td>Light Density</td>
      <td>9</td>
      <td>2</td>
      <td>AE</td>
      <td>0.5</td>
    </tr>
    <tr>
      <td>Medium Density</td>
      <td>8</td>
      <td>4</td>
      <td>AE</td>
      <td>2</td>
    </tr>
    <tr>
      <td>Heavy Density</td>
      <td>7</td>
      <td>6</td>
      <td>AE</td>
      <td>4</td>
    </tr>
  </tbody>
</table>

*\* No modifiers are applied to this Target Number, except for standard Artillery Support [7].*
*\*\* All Damage Value groupings represent 5 points of damage each [7].*
*† Scatters if the attack roll misses [7].*
*†† Affects multiple hexes (Target / Adjacent / Radius-2) [7].*
*‡ See Copperhead special rules [7].*
*‡‡ See Illumination/Smoke special rules [7].*

---

#### Core Support Mechanics

##### Force Construction and Scaling
*   **BSP Allotments:** If a scenario does not define support budgets, players select one of the following starting tiers by mutual agreement [1]:
    *   *Extraction Raid Support:* 5 BSPs [8]
    *   *Objective Raid Support:* 12 BSPs [8]
    *   *Diversionary Raid Support:* 20 BSPs [8]
    *   *Planetary Assault Support:* 35 BSPs [8]
*   **Acreage Scaling:** These rules are designed for a standard four-mapsheet playing area (31 x 35 hexes) [9]. For every two additional mapsheets added to the play area, increase each player's starting BSP budget by 50% [9].
*   **One-Time Use:** Each purchased instance of a support type is usable exactly once per game, whether it hits or misses (with the exception of minefields, which remain active until depleted) [9]. Unspent BSPs are discarded once play begins [10].
*   **Damage Resolution:** All damage value groupings consist of 5 points of damage each [11]. If an attack hits, a separate hit location roll is made for each individual grouping [11].
*   **Attack Direction:** For successful aerospace strikes and direct artillery hits, roll 1D6 to determine the direction of impact: a result of 1–4 strikes the target's front armor, while 5–6 strikes the rear armor [12].
*   **Environmental Restrictions:** Units completely submerged in Depth 2+ water cannot be targeted by any battlefield support attacks [13]. Any scattered hits or area-effect damage that lands on submerged units is ignored [13]. Standing targets in Depth 1 water resolve hits using the Punch Hit Location Table [14].

---

#### Support Category Rules

##### Offensive Aerospace Support (Strikes, Bombings, and Strafing)
*   **Strikes (Light/Heavy):** Successful attacks directly strike the targeted hex [14]. Unsuccessful attacks have no effect [15].
*   **Bombings (Light/Heavy) [AE]:** Area-effect damage [15]. If the target hex is a building currently occupied by a 'Mech, the full damage is applied simultaneously to both the building and the unit [15].
    *   *Scattering on Miss:* If the attack roll fails, calculate the Margin of Failure (MoF) [15]. The bomb scatters a number of hexes equal to the MoF [15]. Roll 1D6 and consult the Scatter Diagram to determine the direction of the scatter [16]. Any unit occupying the scatter destination hex (friendly or enemy) is automatically hit, with the attack direction treated as originating from the originally targeted hex [16].
*   **Strafing:** The attacker designates a straight line of 1 to 5 consecutive hexes [16]. The attacker makes a separate attack roll against TN 7 for every unit currently occupying those designated hexes [16]. If successful, the target takes 3 groupings of 5 damage each [11, 17]. Misses in any single hex do not affect attacks in other hexes along the strafing line [18].

##### Defensive Aerospace Support (Air Cover)
Defensive Air Cover is used to intercept and nullify declared enemy Offensive Aerospace attacks before they resolve [18, 19].
*   **Resolution Sequence:** After the attacker declares all Offensive Aerospace attacks for the turn, the defender may reveal their Air Cover selection [18, 19]. Before the attacker makes any rolls, the defender rolls 2D6 against the target number corresponding to the declared attack type [18, 19].
*   **Successful Roll:** The target Offensive Aerospace attack is completely nullified and discarded with no effect [20, 21].
*   **Unsuccessful Roll:** The attacker resolves their Offensive Aerospace attack as normal [20, 22]. Multiple air covers can be assigned to a single attack to maximize the chance of interception, but all assignments must be declared before rolling [20, 21].

##### Artillery Support [AE]
*   **Pre-Designated Hexes:** Before the game begins, each player secretly selects up to 5 pre-plotted target hexes for each purchased Artillery Support selection [22]. Artillery attacks launched against these pre-plotted hexes hit automatically without requiring an attack roll [22]. No player may place more than 5 pre-plotted target hexes on any single mapsheet [23].
*   **Off-Target Firing:** To target a non-pre-designated hex, the player must secretly write down the target hex during the Weapon Attack Phase [24]. The shells arrive during the following turn's Weapon Attack Phase [24].
*   **Spotting:** If a friendly unit spots the target hex on both the turn of declaration and the turn of arrival, apply a -2 Target Number modifier to the attack [24]. If the spotter fires its own weapons during the turn the artillery arrives, the spotter suffers a +1 attack penalty, and the artillery only receives a -1 modifier [25].
*   **Area-Effect Resolution:**
    *   *Thumper:* Applies 3 groupings to the target hex, and 1 grouping to all adjacent hexes [2, 25].
    *   *Sniper:* Applies 4 groupings to the target hex, and 2 groupings to adjacent hexes [2, 25].
    *   *Long Tom:* Applies 5 groupings to the target hex, 3 groupings to adjacent hexes, and 1 grouping to hexes at Radius-2 [2, 25].
    *   *Adjacent Damage Direction:* Damage applied to adjacent and Radius-2 hexes resolves its hit direction as originating from the central target hex [26]. If a targeted building contains 'Mechs, the full damage is applied simultaneously to both the building and the units [26].
*   **Scattering on Miss:** Fails scatter one hex per point of Margin of Failure (MoF) [27]. Roll 1D6 and compare to the Scatter Diagram to determine the direction of scatter [27]. Any units, buildings, or terrain in the final hex are automatically hit [27].
*   **Variant Munitions:**
    *   *Copperhead:* Functions as an Arrow IV homing missile [28]. It automatically hits any target successfully designated by a friendly Target Acquisition Gear (TAG) system [28, 29]. Damage groupings are reduced to: Thumper (1), Sniper (2), Long Tom (3) [28].
    *   *Illumination:* Purchased at half BSP cost (rounding down) [7]. Instead of damage, it negates all light-based targeting modifiers in its area of effect for the remainder of the battle [7].
    *   *Smoke:* Purchased at half BSP cost (rounding down) [7]. Instead of damage, it fills the target and adjacent hexes with heavy smoke rising 2 levels high [30]. The smoke remains in play for the remainder of the battle [30].

##### Minefield Support [AE]
*   **Deployment:** Written down secretly before the game begins [30]. A player can designate any full hex on the map (excluding water) [30]. A maximum of 6 Damage groupings (30 damage) can be placed in a single hex by a single player [31]. Both players can have active minefields in the same hex [31].
*   **Triggering:** Minefields detonate immediately when a unit enters the hex along the ground (including skidding, displacement, or landing a combat drop) [32, 33]. Jumping units only trigger minefields if they end their movement phase in the mined hex [32].
*   **Resolution:** Upon triggering, the controlling player rolls 2D6 against the minefield's density TN [31]. If successful, damage is resolved using the front column of the Kick Location Table [32].
*   **Depletion and Persistence:** Each time a minefield hits a target, its strength is permanently reduced by 1 grouping [32]. If its groupings are reduced to 0, the minefield is removed [32]. If the minefield attack misses, it does not deplete and remains active in the hex [34].
*   **Detection:** A unit mounting an active probe that passes within the probe's radius of an active enemy minefield forces a detection roll [34]. On a result of 10+, the minefield is permanently revealed [34].
*   **Clearing:** A player can intentionally attempt to clear a minefield hex using direct fire from an LRM-20, Rocket Launcher 20, MRM-20/30/40, ATM 9/12, or Artillery [35]. On a 2D6 roll of 5+, the minefield's strength is reduced to 0 [35]. If standard artillery attacks hit a mined hex (even if not declared as a clearing attempt), roll 2D6; on a 10+, the minefield is cleared [35]. Cleared minefields do not detonate [35].


---

## Hiring Halls

Stationing your mercenary unit at an active hiring hall provides several vital financial, operational, and logistic benefits.

### Support Ratings and Repair Discounts
Every hiring hall has a **Support Rating** from **A (highest)** to **F (none)**. This rating directly determines the level of local technical expertise and grants a discount on the Support Points (SP) required to perform unit repairs or healing:
*   **Rating A–B**: Grants a **50% repair cost modifier** (a 50% discount on SP costs).
*   **Rating C**: Grants a **75% repair cost modifier** (a 25% discount on SP costs).
*   **Rating D–F**: Grants a **100% repair cost modifier** (no discount).

### Slashed Maintenance Costs
While your mercenary command is resting at a hiring hall and not actively under contract, your monthly overhead is heavily reduced. Instead of paying the standard monthly maintenance of 150 Warchest/Support Points (WP/SP), you pay a flat **50 WP** (or Support Points) per month.
*   **Time Limit**: You can take advantage of this reduced rate for up to **six consecutive months**.
*   **Reset**: The six-month timer resets once your unit spends at least three consecutive months paying standard maintenance.

### Generating Random Contracts
Mercenaries looking for work can utilize the hiring hall to generate random contracts. You can check the local boards once per month for a new random contract offer. If you decline the generated offer, you must wait until the following month to roll for a different one.

---

## 2. Rules for Reaching the Hiring Halls (Travel Costs)

Interstellar transit across the Hinterlands is costly and time-consuming. Mercenary commands must pay for their own transit to a hiring hall unless travel is explicitly covered by an active employer contract.

### Standard Transit Costs
If your campaign uses simplified transit rules, traveling to any destination (including a hiring hall) costs a flat rate of **300 SP multiplied by your force Scale**:
*   **Scale 1**: 300 SP
*   **Scale 2**: 600 SP
*   **Scale 3**: 900 SP

### Detailed Jump Tracking (Optional)
If you are playing with an active star map and tracking individual jumps, the cost of transit is **50 SP + 50 SP per jump**, multiplied by your force Scale:
*   **Formula**: `(50 SP + (50 SP × Jumps)) × Scale`

### Maintenance Overtime during Transit
Space travel takes time, and every **four jumps (rounded up)** requires **one month of transit time**. Your MechWarriors and administrative staff must still be paid while in transit. For every month spent traveling without an active contract to cover expenses, you must pay your standard monthly maintenance fee (**500 SP multiplied by your force Scale**).

---

## 3. Hinterlands Hiring Hall Profiles

Each active hiring hall has a unique support rating, local market purchase options, and a **Mercenary Board** populated by dispossessed veterans and colorful local pilots.

---

### Almotacen (Support Rating: C)
Once a pirate smuggling base, Almotacen was brought "to the law" by the Peregrine Lancers and turned into a thriving, rough-and-tumble frontier hiring hub.

#### Local 'Mech Purchases (Sea Fox Presence)
Clan Sea Fox merchants maintain a regular presence in Almotacen's "Pulse Precinct." Once per month, you may roll **2D6** to see what Clan technology they have brought to market:
*   **2+**: One 'Mech is available.
*   **7+**: Two 'Mechs are available.
*   **10+**: Three 'Mechs are available.
*   *Note*: Available models are determined by rolling on the **Clan Sea Fox Random Allocation Table (RAT)**.

#### Almotacen Mercenary Board (Temporary Hires)
You may roll **2D6** once per month on the Almotacen Mercenary Board to recruit pilots. Regular and Veteran pilots can be hired permanently, while unique named characters can be taken as temporary hires for individual tracks:

*   **Regular Dispossessed (2–7 on 2D6)**: Gunnery 4, Piloting 5 (Alpha Strike: Skill 4). Available to hire permanently for the standard cost (100 SP).
*   **Veteran Dispossessed (8 on 2D6)**: Gunnery 3, Piloting 4 (Alpha Strike: Skill 3). Available to hire permanently for 100 SP.
*   **Veteran with 'Mech (Special Roll)**: Available as a temporary hire for **50 SP per track**, plus a **50 SP bonus** if the track is successfully completed.
*   **Olivia (9–10 on 2D6)**: A former Clan Jade Falcon solahma warrior seeking revenge against pirates. She is currently dispossessed, possesses Gunnery 2, Piloting 4 (Alpha Strike: Skill 3), and has the **Lucky (2)** Special Pilot Ability.
    *   *Terms*: Demands **50 SP per track**. She will permanently leave your force unless you take a pirate-hunting garrison track, a garrison contract on Summit, or a Lone Wolf Retainer contract on Alyina by her third contract of employment. Successfully completing one of these tasks allows you to hire her permanently. She will immediately leave if your command commits any acts of piracy.
*   **Harrington (11 on 2D6)**: A spaceport barfly piloting a **Marauder C** (Gunnery 3, Piloting 4; Alpha Strike: Skill 3). Demands you "pay his tab" of **100 SP per track**.
*   **Long John O'Sullivan (12 on 2D6)**: A quiet mercenary who spends most of his time in a hammock but pilots a **Starslayer STC-4C** (Gunnery 3, Piloting 3; Alpha Strike: Skill 3) with ease. He possesses the **Jumping Jack** Special Pilot Ability (reduces the attacker movement modifier for jumping by 2). Demands **150 SP per track**.

---

### Kandersteg (Support Rating: B)
A Lyran border world within easy jump range of the Hinterlands, Kandersteg became the leading regional hub after the destruction of Arc-Royal in 3146. It lacks dedicated military infrastructure, so its hiring facilities are informally set up in leased warehouses and office parks.

#### Local 'Mech Purchases (Lyran Manufacturers)
Representatives from Lyran industrial firms sell equipment directly to mercenaries. Roll **2D6** once per month:
*   **2+**: One 'Mech is available.
*   **6+**: Two 'Mechs are available.
*   **9+**: Three 'Mechs are available.
*   *Note*: Available models are determined by rolling on the **Lyran Commonwealth RAT**.

#### Kandersteg Mercenary Board (Temporary Hires)
Roll **2D6** once per month to recruit from Kandersteg's local talent pool:

*   **Regular Dispossessed (2–6 on 2D6)**: Piloting 5, Gunnery 4 (Alpha Strike: Skill 4). Standard purchase terms.
*   **Veteran with 'Mech (7–9 on 2D6)**: Piloting 4, Gunnery 3 (Alpha Strike: Skill 3). Demands **50 SP per track** (+50 SP success bonus).
*   **Thomas Sane (10 on 2D6)**: The disinherited third child of a Lyran Landgrave who dropped out of the military academy. Pilots a **Wolverine WVR-9R** (Gunnery 4, Piloting 5; Alpha Strike: Skill 4). Demands **75 SP per track**.
*   **Red Royal (11 on 2D6)**: John Sanders prefers his theatrical stage name, "Red Royal." He pilots a bright red **King Crab KGC-0000** (Gunnery 4, Piloting 4; Alpha Strike: Skill 4). Demands **100 SP per track**.
    *   *Terms*: He is willing to permanently join your command if he participates in at least one successful track and you pay **500 SP** to upgrade his "Red King" to a Star League-spec **KGC-000**.
*   **Erin Searcy (12 on 2D6)**: An eager pilot looking to prove her skills. Pilots a **Blackjack BJ-5** (Gunnery 3, Piloting 4; Alpha Strike: Skill 3) and possesses the **Sniper** Special Pilot Ability (changes long-range modifiers to +2 and medium-range modifiers to +1). Demands **150 SP per track**.

---

### Galatea (Support Rating: A)
The legendary **"Mercenary's Star"** remains the largest, most prestigious, and highest-rated hiring hall in the Inner Sphere. It saw a massive economic boom following the collapse of the Republic of the Sphere, as thousands of dispossessed ex-Republic soldiers flooded the market. It hosts the headquarters of the **Mercenary Review and Bonding Commission (MRBC)** and the **Mercenaries' Guild**, offering unparalleled training facilities, proving grounds, and elite technical services.

---

### Arc-Royal (Rebuilding)
Historically the premier hub for hiring mercenary forces to combat the Clans, its legendary facilities were entirely leveled during the Jade Falcon occupation. Under the leadership of Grand Duchess Callandre Kell, the Arc-Royal Liberty Coalition is actively re-establishing the hiring hall in Old Connaught. Until those facilities are complete, mercenary operations are handled out of New Hannover under the shadow of the Kell Refit Facility. It welcomes any mercenaries willing to take contracts against Clan Hell's Horses or Clan Jade Falcon.

---

### Tamar Pact and Vesper Marches Recruiting Posts
While not fully independent hiring halls, both the upstart Tamar Pact and the seceded Vesper Marches maintain dedicated recruiting posts on Kandersteg, Arc-Royal, and Galatea to snap up small-to-medium mercenary units to defend their rapidly expanding borders.
