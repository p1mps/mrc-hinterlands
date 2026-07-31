# Salvaged Mech Scrapyard Flag & Unit Repair

## Overview
Add a `scrapyard` flag to salvaged mechs that changes acquisition cost (half BV) and forces Crippled status, plus a repair mechanism for roster units.

---

## 1. Entity: `SalvagedMech` — Add `scrapyard` field

**File:** `src/Entity/SalvagedMech.php`

Add after `salvageRightsPercent`:

```php
#[ORM\Column(type: 'boolean', options: ['default' => false])]
private bool $scrapyard = false;

public function isScrapyard(): bool
{
    return $this->scrapyard;
}

public function setScrapyard(bool $scrapyard): static
{
    $this->scrapyard = $scrapyard;
    return $this;
}
```

---

## 2. Service: `MechAcquisitionService::acquireMech()` — Scrapyard logic

**File:** `src/Service/MechAcquisitionService.php`

### Behavior when `scrapyard = true`:
- **Cost** = `floor(bvCost / 2)` (same as salvageValue, but computed directly from BV)
- **Unit damageState** = `Crippled` (instead of the default `None`)
- **Unit BV** = the half-BV cost (same as current behavior for salvaged mechs with salvageValue)
- **SalvagedMech is NOT deleted** — it stays in the DB with `acquired = true`

### Updated `acquireMech` logic:

```php
public function acquireMech(SalvagedMech $salvagedMech, MercenaryCompany $company): void
{
    // Determine cost
    if ($salvagedMech->isScrapyard()) {
        // Scrapyard: cost is half of BV
        $cost = $this->calculationService->calculateSalvageValue($salvagedMech->getBvCost());
    } else {
        // Normal: use salvageValue if set, otherwise fall back to bvCost
        $cost = $salvagedMech->getSalvageValue() ?? $salvagedMech->getBvCost();
    }
    
    if ($cost === null || $cost <= 0) {
        throw new \InvalidArgumentException('Salvaged Mech must have a valid BV cost or salvage value.');
    }

    // Deduct Support Points from Company
    $company->deductSupportPoints($cost, "Acquisition of {$salvagedMech->getModel()}" . ($salvagedMech->isScrapyard() ? ' (Scrapyard)' : ''));

    // Create New Roster Unit
    $newUnit = new Unit();
    $newUnit->setName($salvagedMech->getModel() ?? '');
    $newUnit->setChassis($salvagedMech->getModel() ?? 'Unknown Chassis');
    $newUnit->setTonnage($salvagedMech->getTonnage() ?? 0);
    $newUnit->setBv($cost);

    try {
        $newUnit->setUnitType(UnitType::Mech);
    } catch (\ValueError $e) {
        throw new \InvalidArgumentException('Could not determine UnitType for Mech.');
    }

    $newUnit->setCompany($company);

    // Scrapyard: force Crippled status, normal: None (default)
    if ($salvagedMech->isScrapyard()) {
        try {
            $newUnit->setDamageState(DamageState::Crippled);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException('Could not set Crippled damage state.');
        }
    }

    // Mark Salvaged Mech as Acquired
    $salvagedMech->setAcquired(true);

    // Persist Changes
    $this->em->persist($newUnit);
    
    // Only remove from DB if NOT scrapyard (scrapyard mechs stay in DB)
    if (!$salvagedMech->isScrapyard()) {
        $this->em->remove($salvagedMech);
    }
    
    $this->em->flush();
}
```

The `MechAcquisitionService` constructor needs to accept `SalvageCalculationService` as a dependency.

---

## 3. Service: `RosterService` — Add `repairUnit` method

**File:** `src/Service/RosterService.php`

Add a new method:

```php
/**
 * Repairs a unit from its current damage state to None (fully repaired).
 * Deducts SP from company. Returns null on success, error string on failure.
 */
public function repairUnit(Unit $unit, MercenaryCompany $company): ?string
{
    $repairCost = $this->salvageCalc->calculateRepairCost(
        $unit->getTonnage(),
        $unit->getDamageState(),
        null  // techBase not tracked on Unit, default to IS
    );

    if ($repairCost === null) {
        return 'Could not calculate repair cost.';
    }

    if ($repairCost === 0) {
        return 'Unit is already fully repaired.';
    }

    // Attempt to deduct SP
    try {
        $company->deductSupportPoints($repairCost, "Repair of {$unit->getName()} ({$unit->getChassis()})");
    } catch (\Exception $e) {
        return $e->getMessage();
    }

    // Set damage state to None
    try {
        $unit->setDamageState(DamageState::None);
    } catch (\ValueError $e) {
        return 'Could not repair unit.';
    }

    $this->em->flush();
    return null;
}
```

The `RosterService` constructor needs to accept `SalvageCalculationService`.

---

## 4. Controller: `RosterController` — Add repair endpoint

**File:** `src/Controller/RosterController.php`

Add a new route:

```php
#[Route('/{id}/repair', name: 'app_roster_repair', methods: ['POST'])]
public function repair(Unit $unit, Request $request, RosterService $rosterService): Response
{
    $company = $this->getUser()->getCompany();
    $error = $rosterService->repairUnit($unit, $company);
    if ($error) {
        $this->addFlash('danger', $error);
    } else {
        $this->addFlash('success', 'Unit repaired successfully.');
    }

    return $this->redirectToRoute('app_roster');
}
```

---

## 5. Controller: `SalvagedMechController` — Add scrapyard acquisition route

**File:** `src/Controller/SalvagedMechController.php`

The existing `/acquire` route already calls `acquireMech()`. No new controller route is needed — the scrapyard logic is handled inside `acquireMech()`. However, the **show page** needs to indicate the scrapyard cost difference.

---

## 6. Form: `SalvagedMechType` — Add scrapyard checkbox

**File:** `src/Form/SalvagedMechType.php`

Add to `buildForm()`:

```php
->add('scrapyard', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
    'label' => 'Scrapyard (half cost, stays as Crippled)',
    'required' => false,
])
```

Also need to import: `use Symfony\Component\Form\Extension\Core\Type\CheckboxType;`

---

## 7. Template: `salvaged_mech/index.html.twig` — Add Scrapyard column

Add a "Scrapyard" column to the index table, between "Tech Base" and "Status":

```html
<th>Scrapyard</th>
```

And in the tbody:

```html
<td>{{ salvaged_mech.scrapyard ? 'Yes' : 'No' }}</td>
```

---

## 8. Template: `salvaged_mech/show.html.twig` — Display scrapyard info

Add a row in the detail table:

```html
<tr><th>Scrapyard</th><td>{{ salvaged_mech.isScrapyard ? 'Yes' : 'No' }}</td></tr>
```

Update the Acquire Mech button confirmation text to reflect scrapyard cost if applicable:

```html
{% set acquisitionCost = salvaged_mech.isScrapyard 
    ? salvageCalc.calculateSalvageValue(salvaged_mech.bvCost) 
    : (salvaged_mech.salvageValue ?? salvaged_mech.bvCost) %}
{% set scrapyardNote = salvaged_mech.isScrapyard ? ' (Scrapyard: half BV, stays Crippled)' : '' %}
```

Update the confirm message to show the actual cost.

---

## 9. Template: `salvaged_mech/edit.html.twig` — Add scrapyard checkbox

Add to the edit form:

```html
<div class="col-md-6">
    <div class="form-check">
        {{ form_widget(form.scrapyard, {'attr': {'class': 'form-check-input'}}) }}
        {{ form_label(form.scrapyard, null, {'label_attr': {'class': 'form-check-label'}}) }}
    </div>
</div>
```

---

## 10. Template: `roster/index.html.twig` — Add repair button

Add a "Repair" button in the actions column for units that are not `None` damage state:

```html
<td class="text-nowrap">
    <a href="{{ path('app_roster_edit', {id: unit.id}) }}" class="btn btn-sm btn-secondary">Edit</a>
    {% if unit.damageState != 'None' %}
        <form method="post" action="{{ path('app_roster_repair', {id: unit.id}) }}" class="d-inline"
              onsubmit="return confirm('Repair this unit? This will deduct SP based on tonnage and damage state.');">
            <button class="btn btn-sm btn-info">Repair</button>
        </form>
    {% endif %}
    <form method="post" action="{{ path('app_roster_delete', {id: unit.id}) }}" class="d-inline"
          onsubmit="return confirm('Delete this unit?')">
        <button class="btn btn-sm btn-danger">Del</button>
    </form>
</td>
```

---

## 11. Tests

### `MechAcquisitionServiceTest.php` — Add scrapyard tests
- `testAcquireMechScrapyardSetsCrippledDamageState()` — verifies Unit gets `Crippled` damage state
- `testAcquireMechScrapyardUsesHalfBVAsCost()` — verifies cost is `floor(bvCost/2)`
- `testAcquireMechScrapyardDoesNotRemoveSalvagedMech()` — verifies SalvagedMech stays in DB
- `testAcquireMechScrapyardWithNullBvCost()` — verifies exception is thrown

### `RosterServiceTest.php` — Add repair tests (new file)
- `testRepairUnitSucceeds()` — successful repair with correct SP deduction
- `testRepairUnitFailsInsufficientFunds()` — error when not enough SP
- `testRepairUnitFailsWhenAlreadyNone()` — error when already fully repaired
- `testRepairUnitSetsDamageStateToNone()` — verifies damage state is `None` after repair

---

## 12. Database Migration

Run `php bin/console make:migration` (or `doctrine:migrations:diff`) after adding the `scrapyard` column to generate the migration.

---

## Checklist

- [ ] 1. Add `scrapyard` field to `SalvagedMech` entity
- [ ] 2. Update `MechAcquisitionService` — inject `SalvageCalculationService`, update `acquireMech()` with scrapyard logic
- [ ] 3. Add `repairUnit()` method to `RosterService` — inject `SalvageCalculationService`
- [ ] 4. Add `repair` route to `RosterController`
- [ ] 5. Add `scrapyard` checkbox to `SalvagedMechType` form
- [ ] 6. Update `salvaged_mech/index.html.twig` — add Scrapyard column
- [ ] 7. Update `salvaged_mech/show.html.twig` — display scrapyard info, show scrapyard cost in confirm
- [ ] 8. Update `salvaged_mech/edit.html.twig` — add scrapyard checkbox
- [ ] 9. Update `roster/index.html.twig` — add Repair button for non-None units
- [ ] 10. Write/update tests for `MechAcquisitionService` (scrapyard scenarios)
- [ ] 11. Write tests for `RosterService` (repair scenarios)
- [ ] 12. Generate database migration
