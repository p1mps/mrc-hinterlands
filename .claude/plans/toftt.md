# Take One for the Team Plan

**Goal:** Allow a player to mark a track as "Taking One for the Team", which halves their combat pay for that track (rounded down).

**Affected files:**
- Modify: `src/Entity/TrackRecord.php` — add `takingOneForTeam` bool field
- Create: migration for new column
- Modify: `templates/contract_log/add.html.twig` — add TOFTT checkbox to Step 3 (track setup)
- Modify: `src/Controller/ContractLogController.php` — read checkbox in `handleTrackSetup`; halve combat pay in `handlePostTrack`

**Logic:**
- TOFTT is set at track setup time via a checkbox.
- At post-track, if the pending track has `takingOneForTeam = true`, combat pay = `floor(normal / 2)`.
- The log entry description notes the halving.

---

## Task Checklist

- [ ] **1. Add `takingOneForTeam` to `TrackRecord` entity**

```php
#[ORM\Column]
private bool $takingOneForTeam = false;

public function isTakingOneForTeam(): bool { return $this->takingOneForTeam; }
public function setTakingOneForTeam(bool $v): static { $this->takingOneForTeam = $v; return $this; }
```

- [ ] **2. Generate and run migration**

```
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

- [ ] **3. Set TOFTT flag in `handleTrackSetup`**

Read a `toftt` POST param and set it on the track:

```php
private function handleTrackSetup(Contract $contract, int $month, bool $toftt): void {
    // ... existing code ...
    $track->setTakingOneForTeam($toftt);
    // ... rest unchanged ...
    $log->setDescription("Track {$track->getTrackNumber()}: {$result['missionType']} on {$result['terrain']} (MegaMek: {$result['terrainSetting']})" . ($toftt ? ' [TOFTT]' : ''));
}
```

Update the caller in `add()`:
```php
} elseif ($action === 'track_setup') {
    $toftt = (bool) $request->request->get('toftt', false);
    $this->handleTrackSetup($contract, (int) $request->request->get('month'), $toftt);
```

- [ ] **4. Halve combat pay in `handlePostTrack` when TOFTT**

Find the pending track before calculating pay, then apply the halving:

```php
$pendingTrack = null;
foreach ($contract->getTrackRecords() as $track) {
    if ($track->getStatus() === TrackStatus::Pending) {
        $pendingTrack = $track;
        break;
    }
}

$combatPay = $contract->calculateMonthlyCombatPay($tier);
if ($pendingTrack?->isTakingOneForTeam()) {
    $combatPay = (int) floor($combatPay / 2);
}
```

Update the log description to note TOFTT when active:
```php
$tofttNote = $pendingTrack?->isTakingOneForTeam() ? ' (TOFTT — half pay)' : '';
$log->setDescription("Combat pay: " . ($combatPay > 0 ? "+$combatPay SP" : "none") . " ({$tier->value})$tofttNote. $salvageNote");
```

- [ ] **5. Add TOFTT checkbox to Step 3 in `templates/contract_log/add.html.twig`**

```twig
<div class="col-md-6">
<div class="card">
<div class="card-header">Step 3 — Track Setup</div>
<div class="card-body">
<p>Rolls mission type, terrain, and command complication.</p>
<form method="post">
    <input type="hidden" name="action" value="track_setup">
    <input type="hidden" name="month" value="{{ currentMonth }}">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="toftt" value="1" id="toftt">
        <label class="form-check-label" for="toftt">Taking One for the Team</label>
    </div>
    <button class="btn btn-primary">Roll Track Setup</button>
</form>
</div>
</div>
</div>
```
