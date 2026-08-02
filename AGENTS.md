# Mercenary Company Management System

## Tech Stack

- **PHP 8.2+** (runtime 8.5), **Symfony 7.4.x**, **Doctrine ORM 3.6**, **MariaDB 10.11** (dev), **PostgreSQL 16** (local override)
- **PHPUnit 13.2** (failOnDeprecation, failOnNotice, failOnWarning), **Twig**, **Docker Compose** (postgres in `compose.yaml`)
- Namespace: `App\` → `src/`, tests: `App\Tests\` → `tests/`
- Entry point: `src/Kernel.php`

## Commands

```bash
composer install              # install dependencies
bin/phpunit                   # run all tests (test env → SQLite in `var/test_%kernel.environment%.db`)
bin/phpunit tests/Unit/Service/SomeServiceTest.php  # single test file
bin/phpunit tests/Unit/Service/SomeServiceTest.php --filter testSomeMethod  # single method
```

Dev `.env` defaults to **MariaDB** on `127.0.0.1:3306` (`mrc_hinterlands`). Local override (`.env.local`) uses **PostgreSQL** on `127.0.0.1:5432`. Test uses **SQLite** (`.env.test`).

## .env Loading Order

`.env` → `.env.local` → `.env.$APP_ENV` → `.env.$APP_ENV.local`.
`.env.local` is gitignored — **never commit it**.

## Architecture

| Layer | Count | Notes |
|---|---|---|
| Controllers (10) | `SalvagedMech`, `Contract`, `ContractLog`, `Roster`, `Pilot`, `SupportPoint`, `Dashboard`, `Security`, `Dropship`, `Rules` |
| Entities (10) | `User`, `MercenaryCompany`, `Unit`, `Pilot`, `Contract`, `TrackRecord`, `ContractLogEntry`, `SupportPointEntry`, `SalvagedMech`, `Dropship` |
| Services (15) | `SalvageCalculationService`, `MechAcquisitionService`, `ContractService`, `DropshipService`, `RansomService`, `ContractGeneratorService`, `SalvageCheckService`, `ContractLogService`, `SalvagedMechService`, `SupportPointService`, `PilotService`, `DashboardService`, `SecurityService`, `RosterService`, `DiceRoller` |
| Repositories (10) | All extend `ServiceEntityRepository` |
| Enums (9) | `ContractType`, `ContractStatus`, `DamageState`, `UnitType`, `TechBase`, `ContractLogEntryType`, `CombatPayTier`, `TrackStatus`, `CommandRights` |
| Form Types (11) | Named `*Type.php` in `src/Form/` |
| DataTables (15) | Contract generation helpers + `XpThresholdsTable` |
| Twig Extensions (1) | `UsersExtension` |

## Pilot — Skill & XP (critical)

- `Pilot` has `gunnery` (default 4), `piloting` (default 5), `gunneryXp` (default 0), `pilotingXp` (default 0).
- **No single `xp` field** — Gunnery and Piloting track independent XP.
- Skill levels 0–5 (lower = better, "roll under"). `XpThresholdsTable::checkImprovement(gunnery, piloting, gunneryXp, pilotingXp)` returns improvement alerts.
- Only **named pilots** (`isNamed() == true`) get XP improvement checks.
- Max **4 named pilots** per company.
- When editing a pilot entity in tests, use `setGunneryXp()` / `setPilotingXp()` — `setXp()` no longer exists.

## Critical Patterns

### Authorization — inline, no service
Controllers call `$this->getUser()->getCompany()`, then compare `$entity->getCompany() !== $company`. No `#[IsGranted]` annotations. Unauthorized = flash error + redirect.

### Support Points (SP) are the core currency
Every action (acquisition, repair, transport, maintenance, base pay) deducts/credits SP. `MercenaryCompany::deductSupportPoints()` throws `Exception` on insufficient funds — this is the primary failure mechanism.

### Salvage math
- Salvage value = `floor(bvCost / 2)`
- Repair cost = `tonnage * multiplier` (IS: 0.5/2/3/5, Mixed/Clan: 1.5×)
- Acquisition cost = `salvageValue × (1 - salvageRightsPercent/100)`
- SP payout = `salvageValue × (salvageRightsPercent / 100)`, or 25% for "Exchange" (null percent)
- Acquisition disallowed when `salvageRightsPercent` is null (Exchange) or 0 (None)
- Salvage check: 2d6, thresholds by unit type (Mech: 4+, Vehicle: 6+, Battle Armor: 7+)

### Doctrine relationships
- `onDelete: 'SET NULL'` for optional relationships (e.g., `SalvagedMech.contract`) — child survives parent deletion
- `cascade: ['persist', 'remove']` on `OneToOne`/`OneToMany` where child should be deleted with parent
- `orphanRemoval: true` on `MercenaryCompany.supportPointEntries`
- All IDs are `int` (auto-increment). No UUIDs.
- `MercenaryCompany` has `cascade: ['persist', 'remove']` on `units`, `pilots`, `supportPointEntries` (with `orphanRemoval`)

### DB dump caveat
`dump.sql` is **outdated** vs. entity definitions. `SalvagedMech` entity has fields (`damageState`, `techBase`, `salvageValue`, `salvageRightsPercent`, `scrapyard`, `isTrulyDestroyed`, `spTaken`) not in the dump. Trust entity annotations over `dump.sql`.

### Contracts: `salvageRights` string parsing
Entity stores `?int $salvageRightsPercent`, but user-facing value is a string like `"3"`, `"Exchange"`, `"Exchange/50%"`. Parse accordingly.

## Testing

- Unit tests: mock `EntityManagerInterface`, use `createMock`/`createStub`
- Integration: `WebTestCase` + `SchemaTool` for schema recreation
- Test naming: `test<MethodName><Scenario>()`
- No `#[CoversClass]` attributes
- `FullStackIntegrationTest` exercises the full stack: create entities via repo, persist/flush, then assert via repo find.

## Gotchas

- Dropship has a **unique `company_id` constraint** — one dropship per company at DB level
- `APP_SHARE_DIR` env var (set in `.env`) controls the shared directory path for file operations
- `MercenaryCompany::getRoles()` returns `['ROLE_USER']` + `ROLE_ALLOWED_TO_SWITCH` if username is 'Andrea'
- Flash messages for all user feedback (success/error)
- Form types: `IntegerType::class` for numbers, `EnumType::class` for enums, `TextType::class` for strings, `CheckboxType::class` for booleans
- PHPUnit is configured with `failOnDeprecation="true"`, `failOnNotice="true"`, `failOnWarning="true"` — silence deprecations or fix them
