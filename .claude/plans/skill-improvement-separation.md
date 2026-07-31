# Plan: Separate GunneryXP and PilotingXP

## Problem

Currently, `Pilot` has a single `xp` field shared between Gunnery and Piloting skill improvements. The thresholds table (`XpThresholdsTable`) already uses **different XP costs** for Gunnery vs Piloting at levels 3, 4, and 5, but a single `xp` value is checked against both — meaning a pilot could trigger a Gunnery improvement alert based on XP that was "really" meant for Piloting, or vice versa.

## Goal

Split the single `xp` column into two separate columns: `gunneryXp` and `pilotingXp`. Each skill's XP is independently checked against its own thresholds.

## Affected Files

| # | File | Change |
|---|------|--------|
| 1 | `src/Entity/Pilot.php` | Replace `xp` → `gunneryXp`, `pilotingXp` + getters/setters |
| 2 | `migrations/VersionXXXXXX.php` (new) | DB migration: `xp` → `gunnery_xp`, `piloting_xp` |
| 3 | `src/DataTable/XpThresholdsTable.php` | Update `checkImprovement()` signature |
| 4 | `src/Service/PilotService.php` | Update `getXpThresholdAlerts()` call |
| 5 | `src/Form/PilotFormType.php` | Replace `xp` field with `gunneryXp`, `pilotingXp` |
| 6 | `templates/pilot/index.html.twig` | Show two XP columns + separate alerts |
| 7 | `templates/pilot/form.html.twig` | Render two XP fields |
| 8 | `tests/Unit/Service/PilotServiceTest.php` | Update all tests referencing `setXp`/`getXp` |

## Detailed Changes

### 1. `src/Entity/Pilot.php`

**Before (lines 33-34, 55-56):**
```php
#[ORM\Column]
private int $xp = 0;

public function getXp(): int { return $this->xp; }
public function setXp(int $xp): static { $this->xp = $xp; return $this; }
```

**After:**
```php
#[ORM\Column]
private int $gunneryXp = 0;

#[ORM\Column]
private int $pilotingXp = 0;

public function getGunneryXp(): int { return $this->gunneryXp; }
public function setGunneryXp(int $gunneryXp): static { $this->gunneryXp = $gunneryXp; return $this; }

public function getPilotingXp(): int { return $this->pilotingXp; }
public function setPilotingXp(int $pilotingXp): static { $this->pilotingXp = $pilotingXp; return $this; }
```

### 2. New Migration

Use `bin/console make:migration` or manually create a migration (following the naming convention `VersionYYYYMMDDHHMMSS.php`):

```php
public function up(Schema $schema): void {
    $this->addSql('ALTER TABLE pilot RENAME COLUMN xp TO gunnery_xp');
    $this->addSql('ALTER TABLE pilot ADD COLUMN piloting_xp INT NOT NULL DEFAULT 0');
}

public function down(Schema $schema): void {
    $this->addSql('ALTER TABLE pilot RENAME COLUMN gunnery_xp TO xp');
    $this->addSql('ALTER TABLE pilot DROP COLUMN piloting_xp');
}
```

### 3. `src/DataTable/XpThresholdsTable.php`

**Before (lines 15-26):**
```php
public static function checkImprovement(int $gunnery, int $piloting, int $xp): ?string {
    $messages = [];
    $nextGunnery = $gunnery - 1;
    if ($nextGunnery >= 0 && isset(self::THRESHOLDS[$nextGunnery]) && $xp >= self::THRESHOLDS[$nextGunnery][0]) {
        $messages[] = "Gunnery can improve to $nextGunnery (" . self::THRESHOLDS[$nextGunnery][0] . " XP)";
    }
    $nextPiloting = $piloting - 1;
    if ($nextPiloting >= 0 && isset(self::THRESHOLDS[$nextPiloting]) && $xp >= self::THRESHOLDS[$nextPiloting][1]) {
        $messages[] = "Piloting can improve to $nextPiloting (" . self::THRESHOLDS[$nextPiloting][1] . " XP)";
    }
    return $messages ? implode(', ', $messages) : null;
}
```

**After:**
```php
public static function checkImprovement(int $gunnery, int $piloting, int $gunneryXp, int $pilotingXp): ?string {
    $messages = [];
    $nextGunnery = $gunnery - 1;
    if ($nextGunnery >= 0 && isset(self::THRESHOLDS[$nextGunnery]) && $gunneryXp >= self::THRESHOLDS[$nextGunnery][0]) {
        $messages[] = "Gunnery can improve to $nextGunnery (" . self::THRESHOLDS[$nextGunnery][0] . " XP)";
    }
    $nextPiloting = $piloting - 1;
    if ($nextPiloting >= 0 && isset(self::THRESHOLDS[$nextPiloting]) && $pilotingXp >= self::THRESHOLDS[$nextPiloting][1]) {
        $messages[] = "Piloting can improve to $nextPiloting (" . self::THRESHOLDS[$nextPiloting][1] . " XP)";
    }
    return $messages ? implode(', ', $messages) : null;
}
```

The thresholds table itself stays the same — it already has separate Gunnery/Piloting columns. The change is that each skill now checks against its **own** accumulated XP instead of a shared pool.

### 4. `src/Service/PilotService.php`

**Before (lines 26-29):**
```php
$alert = XpThresholdsTable::checkImprovement(
    $pilot->getGunnery(),
    $pilot->getPiloting(),
    $pilot->getXp()
);
```

**After:**
```php
$alert = XpThresholdsTable::checkImprovement(
    $pilot->getGunnery(),
    $pilot->getPiloting(),
    $pilot->getGunneryXp(),
    $pilot->getPilotingXp()
);
```

### 5. `src/Form/PilotFormType.php`

**Before (line 19):**
```php
->add('xp', IntegerType::class);
```

**After:**
```php
->add('gunneryXp', IntegerType::class)
->add('pilotingXp', IntegerType::class);
```

### 6. `templates/pilot/index.html.twig`

**Before (lines 9, 17):**
```twig
<thead><tr><th>Name</th><th>Named</th><th>Gunnery</th><th>Piloting</th><th>XP</th><th>Unit</th><th></th></tr></thead>
...
<td>{{ pilot.xp }}{% if thresholdAlerts[pilot.id] is defined %} <span class="badge bg-warning text-dark">{{ thresholdAlerts[pilot.id] }}</span>{% endif %}</td>
```

**After:**
```twig
<thead><tr><th>Name</th><th>Named</th><th>Gunnery</th><th>Piloting</th><th>Gunnery XP</th><th>Piloting XP</th><th>Unit</th><th></th></tr></thead>
...
<td>{{ pilot.gunneryXp }}{% if thresholdAlerts[pilot.id] is defined %} <span class="badge bg-warning text-dark">{{ thresholdAlerts[pilot.id] }}</span>{% endif %}</td>
<td>{{ pilot.pilotingXp }}</td>
```

The alert badge stays on the Gunnery XP column since it's the first skill column. The Piloting XP column shows the raw value.

### 7. `templates/pilot/form.html.twig`

**Before (line 9):**
```twig
{{ form_row(form.xp) }}
```

**After:**
```twig
{{ form_row(form.gunneryXp) }}
{{ form_row(form.pilotingXp) }}
```

### 8. `tests/Unit/Service/PilotServiceTest.php`

All references to `setXp()` / `getXp()` need to be replaced with `setGunneryXp()` / `getGunneryXp()` and `setPilotingXp()` / `getPilotingXp()`.

Specific test methods to update (lines where `setXp` appears):
- `testGetXpThresholdAlertsSkipsUnnamedPilots` (line 105): `$pilot->setXp(9999)` → `$pilot->setGunneryXp(9999)->setPilotingXp(9999)`
- `testGetXpThresholdAlertsReturnsAlertsForNamedPilotsNearThreshold` (line 122): `$pilot->setXp(100)` → split
- `testGetXpThresholdAlertsWithMultiplePilots` (line 147): `$pilot->setXp(9999)` → split
- `testGetXpThresholdAlertsWithCheckImprovementReturningNull` (line 189): `$pilot->setXp(500)` → split
- `testGetXpThresholdAlertsWithMixedNamedAndUnnamedPilots` (lines 206, 214, 221): `$pilot->setXp(...)` → split
- `testGetXpThresholdAlertsWithDuplicateNamedPilots` (lines 238, 246): `$pilot->setXp(0)` → split
- `testUpdatePilotWithFullPilot` (line 659): `$pilot->setXp(250)` → split

## Checklist

- [ ] Update `src/Entity/Pilot.php` — replace `xp` with `gunneryXp` + `pilotingXp`
- [ ] Create Doctrine migration — rename `xp` → `gunnery_xp`, add `piloting_xp`
- [ ] Update `src/DataTable/XpThresholdsTable.php` — new signature with 4 params
- [ ] Update `src/Service/PilotService.php` — pass new getters
- [ ] Update `src/Form/PilotFormType.php` — two XP fields
- [ ] Update `templates/pilot/index.html.twig` — two XP columns
- [ ] Update `templates/pilot/form.html.twig` — two XP form fields
- [ ] Update `tests/Unit/Service/PilotServiceTest.php` — all `setXp`/`getXp` references
- [ ] Run `bin/phpunit` to verify all tests pass
