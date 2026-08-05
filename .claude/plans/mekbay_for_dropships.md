# Mekbay Plan for Dropships

## Problem

Currently, dropships track capacity purely by **tonnage**. There is no concept of "mekbays" — physical hangar bays that hold mechs. In the Battletech universe, dropships have a limited number of mekbays, and each mech takes one mekbay regardless of tonnage. The plan is to add mekbay tracking alongside the existing tonnage tracking.

## Requirements

- Every dropship has a `mekbayCapacity` (number of mekbays)
- **Non-salvaged mechs** (roster `Unit` entities on the dropship) consume 1 mekbay each
- **Salvaged mechs** (`SalvagedMech` entities on the dropship) do NOT consume mekbays, only tonnage
- If no mekbays are available, a non-salvaged mech cannot be onboarded
- Tonnage tracking continues as-is for both types

## Key Design Decisions

1. **Mekbay tracking is dynamic, not stored.** We count `Unit` entities assigned to the dropship where `dropship IS NOT NULL`. No new stored field for "used mekbays" — computed on the fly.

2. **SalvagedMechs never use mekbays.** They only consume tonnage. This matches the requirement: "for salvaged mechs no mekbays, only tonnage."

3. **`mekbayCapacity` is a required integer on Dropship**, defaulting to `0` (no mekbays). This mirrors `maxCapacity` which also has no default but is required.

4. **Shrinking `mekbayCapacity` below current usage is allowed** (same as `maxCapacity` behavior). We don't break existing assignments when editing.

## Files to Modify

### 1. `src/Entity/Dropship.php` — Add `mekbayCapacity` field

```php
#[ORM\Column(type: 'integer')]
private int $mekbayCapacity = 0;

public function getMekbayCapacity(): int
{
    return $this->mekbayCapacity;
}

public function setMekbayCapacity(int $mekbayCapacity): static
{
    $this->mekbayCapacity = $mekbayCapacity;
    return $this;
}
```

### 2. `src/Service/DropshipService.php` — Add mekbay enforcement

Add a helper method:
```php
public function getUsedMekbays(Dropship $dropship): int
{
    return (int) $this->em->createQueryBuilder()
        ->select('COUNT(u.id)')
        ->from(Unit::class, 'u')
        ->where('u.dropship = :id')
        ->setParameter('id', $dropship->getId())
        ->getQuery()
        ->getSingleScalarResult();
}
```

Update `assignUnitToDropship` to check mekbays:
```php
public function assignUnitToDropship(Unit $unit, Dropship $dropship): void
{
    // Existing tonnage check...
    $currentTonnage = $this->getTonnageOnDropship($dropship);
    if ($currentTonnage + $unit->getTonnage() > $dropship->getMaxCapacity()) {
        throw new \LogicException(...);
    }

    // NEW: Mekbay check
    $usedMekbays = $this->getUsedMekbays($dropship);
    if ($usedMekbays >= $dropship->getMekbayCapacity()) {
        throw new \LogicException(
            "No mekbays available. Current mekbays: {$usedMekbays}, max: {$dropship->getMekbayCapacity()}."
        );
    }

    $unit->setDropship($dropship);
    $this->em->flush();
}
```

`assignMechToDropship` stays unchanged (salvaged mechs don't use mekbays).

### 3. `src/Form/DropshipType.php` — Add `mekbayCapacity` field

```php
$builder->add('mekbayCapacity', IntegerType::class, [
    'label' => 'Maximum Mekbay Count',
    'required' => true,
    'attr' => ['min' => 0],
]);
```

Update `maxCapacity` label to "Maximum Tonnage Capacity" for clarity.

### 4. `src/Controller/DropshipController.php` — Pass `mekbayCapacity` to service methods

Update `createDropship` and `updateDropship` calls to include `mekbayCapacity` parameter.

### 5. `templates/dropship/show.html.twig` — Display mekbay info

- Add a row in the info table: "Mekbays" showing `used / capacity`
- In the "Available to Board" section, only show roster Units when mekbays are available
- Update the "Board" button for roster units to mention mekbay cost

### 6. `templates/dropship/new.html.twig` + `edit.html.twig` — Render `mekbayCapacity`

Add `{{ form_row(form.mekbayCapacity) }}` to both templates.

### 7. `tests/Unit/Service/DropshipServiceTest.php` — Add mekbay tests

- `testAssignUnitToDropshipFailsWhenNoMekbaysAvailable`
- `testAssignMechToDropshipIgnoresMekbays`
- `testGetUsedMekbaysReturnsCorrectCount`

### 8. `tests/Controller/DropshipIntegrationTest.php` — Add mekbay integration tests

- `testDropshipRejectsUnitWhenMekbaysExhausted`
- `testSalvagedMechAssignmentIgnoresMekbayLimit`

### 9. `dump.sql` — Add `mekbay_capacity` column (if updating schema)

Note: Per the codebase convention, entity definitions take priority over `dump.sql`. The schema migration will be handled by Doctrine migrations (if configured) or manual schema update.

## Checklist

- [ ] Add `mekbayCapacity` field to `src/Entity/Dropship.php`
- [ ] Add `getUsedMekbays()` method to `src/Service/DropshipService.php`
- [ ] Update `assignUnitToDropship()` in `DropshipService` to enforce mekbay limit
- [ ] Leave `assignMechToDropship()` unchanged (salvaged mechs don't use mekbays)
- [ ] Add `mekbayCapacity` field to `src/Form/DropshipType.php`
- [ ] Update `src/Controller/DropshipController.php` to pass `mekbayCapacity`
- [ ] Update `templates/dropship/show.html.twig` to display mekbay info
- [ ] Update `templates/dropship/new.html.twig` to render `mekbayCapacity`
- [ ] Update `templates/dropship/edit.html.twig` to render `mekbayCapacity`
- [ ] Add mekbay unit tests to `tests/Unit/Service/DropshipServiceTest.php`
- [ ] Add mekbay integration tests to `tests/Controller/DropshipIntegrationTest.php`
- [ ] Run `bin/phpunit` to verify all tests pass
