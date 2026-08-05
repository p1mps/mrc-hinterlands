# Missing Features — Hinterlands Reference Implementation

Based on *E-CAT35450_BattleTech_Hot_Spots_Hinterlands*. Each section notes what exists, what's missing, and what the source document provides.

---

## 1. Dice Roller

### What exists
- `DiceRoller::roll(int $dice, int $sides): int` — sums random integers.

### What's missing

| # | Missing mechanic | Where it's used in the book |
|---|---|---|
| 1.1 | **Drop-the-highest**: roll N dice, drop the highest M, sum the rest | Table 448: "Roll 3d6 and drop the highest number for your initiative" |
| 1.2 | **Keep-the-lowest**: roll N dice, keep the lowest M, sum the rest | Same as above (reverse) |
| 1.3 | **Per-die history**: expose individual die values, not just the sum | Needed for "roll twice and apply both" (complication result 9 appears in every complication table) — currently impossible to know if the second roll is a duplicate without re-rolling |
| 1.4 | **Deterministic/seeded mode**: replay a specific dice sequence by seed | No way to reproduce a contract's full dice history |
| 1.5 | **Drop-the-lowest**: roll N dice, drop the lowest, sum the rest | Possible interpretation of some tables |

---

## 2. Contract Generation — Core

### What exists (solid)
- 13-step Contract Steps Table (all 5 categories)
- Contract Type → Opposing Type lookup (per type)
- ContractTrackTable (6 mission types per contract type, 1d6)
- NumberOfTracksTable (per contract type, 1d6)
- TerrainTable (9 terrain types + settings)
- CommandComplicationTable (12 terrain types × 9 results, ~100 complications with source citations)
- PayRateTable, SupportTermsTable, SalvageRightsTable, TransportationTable
- EmployerTable, EmployerAffiliationTable
- Negotiation system (reputation steps, swap mechanic, max 2 swaps)
- `rollTrackSetup()` — generates mission type, terrain, and complication for a single track

### What's missing

| # | Missing feature | Source location | Notes |
|---|---|---|---|
| 2.1 | **Mapsheet selection per track** | Tables 154–185 (and many more throughout) | Each contract/terrain combo has a 1D6 mapsheet table producing identifiers like "Lakes (MP: Grasslands)", "Badlands #2 (MP: Deserts)", "Hilltops #1 (CI)", "Business District (MP: City)". `TerrainTable` returns a generic "setting" string, not a mapsheet. |
| 2.2 | **Per-contract specific contracts** | Apostica Raid (pp. 80–81), Daring Heist (pp. 76–77), Rodeo Raid (pp. 98–100), Massasauga Rattler (pp. 105–106), Chahar Garrison (pp. 88–89), Esteros Reclamation (pp. 92–93), Krievci (pp. 101–102), Mississauga (pp. 105–106) | Pre-written contracts with unique multi-track structures, named NPCs, special rules, and bonuses. The generator only produces generic contracts. |
| 2.3 | **Track-specific special rules** | Per-contract track tables (e.g., "Horse Pen" special rule on Rodeo Raid Track 1) | `numberOfTracks` is just an integer. No `Track` entity or per-track special rule generation. |
| 2.4 | **Per-contract acquisition/purchase tables** | Daring Heist (pp. 77, Tables 383–393): Ion Sparrow, Hierofalcon, Jade Phoenix, Night Gyr variants with BV/PV | Many contracts specify which 'Mechs become available upon completion. No acquisition table service. |
| 2.5 | **Per-track opposing force composition** | Rodeo Raid: Track 2 "Hell's Horses / Mercenaries", Track 3 "Hell's Horses / Mercenaries" with specific unit lists | `generateOpposing()` generates a full opposing contract from scratch. The book often specifies exact opposing units per track. |
| 2.6 | **Victory conditions** | Per-contract (e.g., Daring Heist: "Either or both players may claim victory if they successfully complete at least one of the tracks") | No `VictoryCondition` entity or service. |
| 2.7 | **Temporary Hire rules** | Daring Heist (p. 77): "If unable to field 2/3 max BV, a local mercenary offers services" | No service for this fallback mechanic. |
| 2.8 | **Lone Wolves / Retainer NPCs** | Daring Heist: Chege Muthoni (Trebuchet TBT-9N, Piloting 4, Gunnery 3, 50 SP/track), Eric the Night Owl (Baboon Howler 5, Piloting 4, Gunnery 3, 75 SP/track) | No NPC repository or per-contract NPC generation. |
| 2.9 | **Track-specific mapsheet tables** | Each contract has its own 1D6 mapsheet table (Rodeo Raid: 6 mapsheets, Apostica Raid: different table, etc.) | No per-contract mapsheet table system. |
| 2.10 | **Command complication ongoing effects** | Every complication table result 5–8 describes ongoing effects (VP penalties, reputation changes, heat tracking modifiers, unit restrictions) | Complications are strings only. No entity to store and apply their mechanical effects. |

---

## 3. Unit Roster Data

### What exists
- `Unit` entity with basic fields
- `UnitType` enum (Mech, Vehicle, BattleArmor, AerospaceFighter, IndustrialMech)
- `SalvageCalculationService` (BV-based salvage math)

### What's missing

| # | Missing data | Source location | Notes |
|---|---|---|---|
| 3.1 | **Full unit roster (BV/PV)** | Tables 23–55 (Hinterlands), Tables 344–393 (Daring Heist), Tables 477 (Massasauga) | 60+ units with BV and PV: Vulcan VT-7T (1499/31), Bushwacker BSW-X4 (1751/37), Rawhide RWD-R1 (1939/36), Thunderbolt TDR-7S (1582/37), Goliath GOL-4S (1912/41), Atlas AS8-S (2789/56), Commando COM-5S (557/17), Quickdraw QKD-5M (1237/31), Assassin ASN-30 (925/23), Chameleon CLN-8V (1426/32), Trebuchet TBT-7M (1348/31), Caesar CES-3R (1578/34), Wolverine WVR-9R (1481/39), Flea FLE-21 (826/29), Ion Sparrow Prime (885/30), Hierofalcon Prime (1878/46), Jade Phoenix Prime (3277/53), Night Gyr Prime (2830/47), etc. |
| 3.2 | **Aerospace fighter data** | Tables 220–222 (Epona, Eurus, Enyo Strike Tank, LRM Carrier, Demolisher) | No `AerospaceFighter` entity. |
| 3.3 | **Battle Armor data** | Table 227: Elemental III Battle Armor [MicroPL] (Sqd5), 434 BSP / 20 PV | No `BattleArmor` entity. |
| 3.4 | **BSP (BattleSpace Profile) data** | Tables 220–226: Epona A (46/54 BSP, 1976 BV, 42 PV, 1000 SP), Eurus A (49/57, 1986, 46, 1200 SP), Enyo Strike Tank (39/45, 1130, 34, 1100 SP), LRM Carrier C (36/41, 1266, 27, 1200 SP), Demolisher Clan (42/48, 1563, 33, 1600 SP) | No `BattleSpaceProfile` entity. |
| 3.5 | **Mech conversion paths** | Table 279–284 (Big Leagues, pp. 54–55): Victor → Victor C (650 SP), Warhammer → Warhammer C (600 SP), Thunderbolt → Thunderbolt C (550 SP), Shadow Hawk → Shadow Hawk C (500 SP), Locust → Locust C (150 SP) | No `MechConversion` entity or service. |
| 3.6 | **Mech purchase costs** | Table 269–274: BattleMaster C (1700 SP), Hunchback IIC 5 (1000 SP), SRM Carrier C (1200 SP), Condor Heavy Hover Tank Ultra (1000 SP) | No `MechPurchaseCost` entity. |
| 3.7 | **Unit chassis lookup by name** | Throughout — every table references units by name (e.g., "Commando COM-7S", "Spider SDR-8M", "Stinger STG-7S") | No unit name-to-entity lookup service. |

---

## 4. Campaign & Reputation System

### What exists
- `MercenaryCompany` with support points
- `TrackRecord` entity
- `DiceRoller` (basic)

### What's missing

| # | Missing feature | Source location | Notes |
|---|---|---|---|
| 4.1 | **Reputation scale** | Table 197–198: Support rating C → 75% modifier | No `Reputation` entity. No service to convert reputation to contract modifiers. |
| 4.2 | **Reputation tiers** | Table 210–211: 3–10 rep → Lieutenant/Star Commander, scales by force size (2, 3) | No reputation tier system. |
| 4.3 | **Reputation tracking across contracts** | Complications frequently reference ±1 reputation (VTOL destruction, civilian destruction, etc.) | No `Campaign` entity to track reputation over time. |
| 4.4 | **VP (Victory Points) tracking** | Complications reference VP penalties (e.g., "−50 VP per building hex failed to destroy, up to −150") | No `VP` or score tracking entity. |
| 4.5 | **Monthly contract progression** | Contracts span 3–6 months; some reference "check each month" (Daring Heist: "check each month to see if a Sea Fox merchant is in system") | No month-by-month contract state machine. |
| 4.6 | **Support rating → modifier conversion** | Table 197–198: C → 75% | No service for this mapping. |

---

## 5. Planet & Hiring Hall Data

### What exists
- `PlanetTable` — random planet name from 70 Hinterlands planet names
- `Planet` field on `Contract` entity (string)

### What's missing

| # | Missing feature | Source location | Notes |
|---|---|---|---|
| 5.1 | **Planet profiles** | Hiring Hall Profile: Almotacen (pp. 56–63) | Detailed planet data: star type, system position, gravity, atmosphere, temperature, water %, HPG class, population, socio-industrial levels, capital city. |
| 5.2 | **Facility data** | Almotacen: Malacca Lounge, Malthus Mansion (casino + civic admin + Apollinaire's offices), Dailey's, Eight Arms Boutique, Clan Consulate, Doc Kold's Clinic | No `Facility` entity. No facility services/prices. |
| 5.3 | **NPC characters** | Almotacen: Merchant Roulin, Apollinaire (top floor penthouse), Doc Kold, Vincenzo Architeuthis (Eight Arms) | No `NPC` entity. |
| 5.4 | **Full planet roster** | Table 231–232: 70+ planets across multiple regions (Blackstone, Sigurd, Last Chance, Crellacor, Gotterdammerung, Oberon VI, Gustrell, Premana, Ayacucho, Valloire, Acrux, Buena, Czarvowo, Rosice, Sierpc, Medzev, Radostov, Drosendo, Herzberg, Aristotle, Eidsfoss, Seginus, Kochab, Skye, Skondia, Sabik, etc.) | `PlanetTable` has names but no structured planet data. |
| 5.5 | **Planetary maps** | Tables 396–400, 434–436: System maps showing jump routes, light years, spectral class, district capitals | No `PlanetaryMap` entity. |

---

## 6. Track & Battle System

### What exists
- `Contract` with `numberOfTracks` and `tracksCompleted`
- `ContractTrackTable` (mission types per contract type)
- `CommandComplicationTable` (12 terrain types × 9 results)
- `rollTrackSetup()` — generates mission type, terrain, complication

### What's missing

| # | Missing feature | Source location | Notes |
|---|---|---|---|
| 6.1 | **Track entity** | Every multi-track contract (Apostica: 2 tracks, Daring Heist: 2–3 tracks, Rodeo Raid: 2–3 tracks) | No `Track` entity with `trackNumber`, `attacker/defender`, `mapsheet`, `specialRules`, `complications`. |
| 6.2 | **Per-track mission type** | Every track table (e.g., Rodeo Raid: Track 1 "Objective Raid", Track 2 "Pursuit", Track 3 "Defend") | Currently `rollTrackSetup()` generates one track. No per-track type generation. |
| 6.3 | **Per-track mapsheet selection** | Every contract has its own 1D6 mapsheet table (Rodeo Raid: 6 mapsheets, Apostica: different table, etc.) | No per-contract mapsheet table system. |
| 6.4 | **Track-specific unit lists** | Rodeo Raid Track 1: specific unit list (Spider SDR-8M, Stinger STG-7S, Crusader CRD-8R, Firestarter FS9-N, Victor VTR-12D, Commando COM-8S, Locust LCT-7V, Starslayer STY-4C, Shadow Hawk SHD-7H, Chameleon CLN-9V, Warhammer C3, Thunderbolt TDR-11S, Vulture Mk IV Prime, Longbow LGB-14C2, Supernova 5, Atlas AS7-S4) | No per-track unit pool. |
| 6.5 | **Force scale system** | Table 123–124: Scale 1 = Mixed Lance (3000 BV / 32 BSP / 100 PV / 5 BSP), Scale 3 = Mixed Company (9000 BV / 96 BSP / 300 PV / 15 BSP) | No `ForceScale` entity. |
| 6.6 | **Terrain → mapsheet mapping** | Tables 135–149: 2D6 terrain roll → mapsheet selection (Wetlands → 5, Hills → 2, Grasslands → 1, Urban → 10), 1D6 mapsheet per terrain (Grassland #2, Wide River, Open Terrain #1, etc.) | No terrain-to-mapsheet service. |
| 6.7 | **Battle phase tracking** | Heat tracking, movement phases, weapon attacks, critical hits — all described in complication effects | No `BattlePhase` or `BattleState` entity. |
| 6.8 | **Heat tracking system** | Complications reference heat (e.g., "+2 heat every turn", "heat dissipation reduced by 1") | No `HeatTracker` service. |
| 6.9 | **Critical hit system** | Complications reference crit tables (e.g., "mark off that actuator as damaged") | No `CriticalHit` system. |
| 6.10 | **Hidden Unit deployment** | Table 466: "add a RCL-1 Digging MiningMech as a Hidden Unit" | No hidden unit mechanic. |
| 6.11 | **Booby trap rules** | Table 450: "eight building hexes that are booby trapped... roll 2D6 on a 9+, building explodes" | No booby trap system. |
| 6.12 | **Ammo cache rules** | Table 405 (Urban complication): "Pick three building hexes... attacker must destroy or suffer −50 VP" | No ammo cache mechanic. |
| 6.13 | **Civilians / noncombatant rules** | Table 405 (Urban): "Add 4 unarmed infantry... −1 reputation per unit destroyed" | No civilian mechanic. |
| 6.14 | **Weather effects** | Snow storms (Table 494), magnetic interference (Table 490), sandstorms (CommandComplicationTable), lightning storms (Table 469) | Complications are strings only. No weather entity or service. |
| 6.15 | **Terrain effects** | Swamp hex movement penalties (Table 468), mountain vehicle MP reduction (Table 452), fire danger (Table 499) | No terrain effect service. |

---

## 7. Pilot & Roster Generation

### What exists
- `Pilot` entity with `gunnery`, `piloting`, `gunneryXp`, `pilotingXp`
- `XpThresholdsTable` — skill improvement checks
- `PilotService`
- `RosterService`
- `DiceRoller` (basic)

### What's missing

| # | Missing feature | Source location | Notes |
|---|---|---|---|
| 7.1 | **Random pilot generation tables** | Tables 338–340: 2D6 rolls for pilot skills (e.g., "Veteran (Gunnery 3, Piloting 4; AS: Skill 3) Dispossessed", "Harrington") | No `RandomPilotGenerator` service. |
| 7.2 | **Random 'Mech assignment** | Tables 346–363: 3D6 rolls for chassis/model with BV/PV (Spider SDR-8M, Stinger STG-7S, Crusader CRD-8R, etc.) | No `RandomMechAssignment` service. |
| 7.3 | **AS (Alpha Strike) skill** | Tables 338–340 reference "AS: Skill 3" alongside Gunnery/Piloting | No Alpha Strike skill field on `Pilot`. |
| 7.4 | **Named pilot roster generation** | Per-contract named pilots (Chege Muthoni: Trebuchet TBT-9N, Piloting 4, Gunnery 3; Eric the Night Owl: Baboon Howler 5, Piloting 4, Gunnery 3) | No named pilot database. |

---

## 8. Support & Logistics

### What exists
- `MercenaryCompany::deductSupportPoints()` — SP deduction
- `SupportPointEntry` entity
- `SupportPointService`
- `SupportTermsTable` — support terms per contract step

### What's missing

| # | Missing feature | Source location | Notes |
|---|---|---|---|
| 8.1 | **Support rating modifiers** | Table 197–198: Support rating C → 75% modifier | No service to convert support rating to SP modifiers. |
| 8.2 | **Transportation cost calculation** | Per-contract transportation terms (Straight/20%, Battle/100%, etc.) with percentage costs | `Contract::parseTransportPercent()` exists but no service for calculating actual transportation SP cost. |
| 8.3 | **Shipyard / maintenance costs** | "maintaining their vessels will likely require access to the shipyards at Arc-Royal, Butler, or Sudeten" (p. 98) | No shipyard service. |
| 8.4 | **Mercenary logistics firms** | "Steve's Stevedores" (p. 98) — commercial transport providers | No logistics firm service. |
| 8.5 | **Free Guild transportation** | Free Guilds, Malthus Confederation, Lyran Free Trader Association — different service rates | No transportation provider service. |

---

## 9. Enums Missing

### What exists
- `ContractType` (Raid, Expedition, Garrison, Invasion, Retainer)
- `ContractStatus` (Available, Accepted, InProgress, Completed, Failed)
- `DamageState`
- `UnitType` (Mech, Vehicle, BattleArmor, AerospaceFighter, IndustrialMech)
- `TechBase` (IS, Clan)
- `ContractLogEntryType`
- `CombatPayTier`
- `TrackStatus`
- `CommandRights` (Integrated, House, Liaison, Independent)

### What's missing

| # | Missing enum | Source location | Notes |
|---|---|---|---|
| 9.1 | `BattleSpaceProfile` | Tables 220–226 | BSP types (Epona, Eurus, Enyo Strike Tank, LRM Carrier, Demolisher) |
| 9.2 | `TerrainType` | TerrainTable returns strings only | Should be an enum: Desert, Wetlands, LightIndustrial, Hills, Wooded, Grasslands, Savannahs, Urban, Mountains, Alien |
| 9.3 | `ForceScale` | Table 123–124 | Scale 1 (Mixed Lance), Scale 3 (Mixed Company) |
| 9.4 | `SupportRating` | Table 197–198 | Rating C → 75% |
| 9.5 | `ReputationTier` | Table 210–211 | Lieutenant/Star Commander, etc. |
| 9.6 | `ChassisType` | Throughout unit tables | Mech chassis types (Commando, Jenner, Atlas, etc.) |
| 9.7 | `FacilityType` | Almotacen profile | Malacca Lounge, Malthus Mansion, Dailey's, Eight Arms Boutique, Clan Consulate, Doc Kold's Clinic |
| 9.8 | `ContractBriefType` | Specific contracts | Apostica Raid, Daring Heist, Rodeo Raid, Massasauga Rattler, Chahar Garrison, Esteros Reclamation, Krievci |

---

## 10. Entities Missing

### Summary of all entities that should exist but don't

| # | Entity | Purpose |
|---|---|---|
| 10.1 | `Track` | Per-track data: trackNumber, missionType, mapsheet, specialRules, complications, attacker/defender type |
| 10.2 | `Mapsheets` | Mapsheet identifiers and terrain type mappings |
| 10.3 | `AerospaceFighter` | Aerospace fighter stats (BV, PV, BSP) |
| 10.4 | `BattleArmor` | Battle Armor stats (BV, PV, BSP) |
| 10.5 | `BattleSpaceProfile` | BSP stats (BV, PV, BSP cost, SP cost) |
| 10.6 | `UnitRoster` | Full unit database with chassis, model, BV, PV |
| 10.7 | `MechConversion` | Conversion paths and SP costs |
| 10.8 | `MechPurchaseCost` | Unit purchase costs in SP |
| 10.9 | `Reputation` | Company reputation tracking |
| 10.10 | `Campaign` | Campaign state: months, reputation, completed contracts |
| 10.11 | `VP` (Victory Points) | Track record scoring |
| 10.12 | `PlanetProfile` | Full planet data (star type, gravity, atmosphere, population, etc.) |
| 10.13 | `Facility` | Hiring Hall facilities with services and prices |
| 10.14 | `NPC` | Named characters (Doc Kold, Apollinaire, etc.) |
| 10.15 | `PlanetaryMap` | System maps with jump routes |
| 10.16 | `BattleState` | Ongoing battle: heat, movement, weapon state, complications |
| 10.17 | `ComplicationEffect` | Stored complication with mechanical effects |
| 10.18 | `WeatherEffect` | Weather conditions and their modifiers |

---

## Priority Matrix

### P0 — Core gameplay loop (can't play without these)
- 2.1 Mapsheet selection per track
- 6.1 Track entity
- 6.3 Per-track mapsheet selection
- 3.1 Full unit roster (BV/PV)
- 1.1 Drop-the-highest / 1.3 Per-die history

### P1 — Contract depth (makes contracts feel real)
- 2.2 Pre-written contract briefs
- 2.3 Per-track special rules
- 2.4 Acquisition tables
- 6.4 Per-track unit lists
- 6.14 Weather effects
- 6.15 Terrain effects

### P2 — Campaign play (longer-term engagement)
- 4.1 Reputation scale
- 4.2 Reputation tiers
- 4.3 Reputation tracking
- 4.5 Monthly progression
- 4.6 Support rating → modifier

### P3 — Reference / flavor (nice to have)
- 5.1–5.5 Planet profiles, facilities, NPCs
- 7.1–7.4 Random pilot/mech generation
- 8.1–8.5 Support/logistics services
- 3.2–3.7 Aerospace, Battle Armor, BSP data
- 3.5–3.6 Mech conversions and purchase costs
