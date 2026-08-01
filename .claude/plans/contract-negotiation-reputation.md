# Plan: Contract Negotiation & Reputation System

## Context

The `MercenaryCompany` entity already has a `reputation` field (default `1`) but no logic uses it. The `ContractStepsTable` already maps steps 1–13 to all contract negotiation parameters (base pay %, command rights, salvage rights, support terms, transport terms). The `ContractGeneratorService` generates contracts via dice rolls + modifiers, but ignores reputation entirely.

Per `hinterlands.md` (pp. 17, 6680–6800):
- Starting reputation is **1**.
- Each point of reputation lets you increase **one payment or support type by one step** (max = 2 × Scale).
- You can **swap**: sacrifice 2 steps in one category to gain 1 step in another (max 2 swaps).
- Successful track → **+1 reputation**; Unsuccessful → **−1**; Tie → no change; Breaching → **−3**.
- Reputation maps to Scale/Rank: 0–2 → Scale 1, 3–10 → Scale 2, 11–20 → Scale 3.

## Files to Modify

| File | Change |
|------|--------|
| `src/Entity/MercenaryCompany.php` | Add reputation management methods |
| `src/Service/ContractService.php` | Add reputation adjustment on track outcomes |
| `src/Service/ContractGeneratorService.php` | Add `generateWithNegotiation()` method |
| `src/Service/ContractStepsTable.php` | Add helper methods for negotiation |
| `src/Controller/ContractController.php` | Add negotiation endpoint |
| `templates/contract/generate.html.twig` | Add negotiation UI |
| `templates/contract/index.html.twig` | Show company reputation |

## Detailed Design

### 1. `MercenaryCompany` — Reputation Management

Add methods to `src/Entity/MercenaryCompany.php`:

```php
// Already exists: getReputation(), setReputation()
// ADD:
public function adjustReputation(int $delta): static {
    $this->reputation = max(0, $this->reputation + $delta);
    return $this;
}

public function getScaleFromReputation(): int {
    if ($this->reputation <= 2) return 1;
    if ($this->reputation <= 10) return 2;
    return 3;  // up to 20 (max from table)
}

public function getMaxNegotiationSteps(): int {
    return 2 * $this->getScaleFromReputation();
}
```

### 2. `ContractStepsTable` — Negotiation Helpers

Add to `src/DataTables/ContractStepsTable.php`:

```php
// Get all 5 category values for a given step (used by negotiation)
public static function getStepValues(int $step): array {
    return self::STEPS[$step];
}

// Get the step number for a specific category value
public static function getStepForCategory(int $step, string $category): int {
    // Returns the step index for the specified category (basePayPercent, commandRights, salvageRights, supportTerms, transportTerms)
}

// Check if a step is achievable from current step with given reputation budget
public static function isStepReachable(int $currentStep, int $targetStep): bool {
    return $targetStep >= $currentStep && $targetStep - $currentStep <= 13;
}
```

### 3. `ContractGeneratorService` — Negotiation-Aware Generation

Add method `generateWithNegotiation(int $scale, int $reputation): array`:

This wraps the existing `generate()` logic but applies reputation-based shifts **after** the dice rolls:

1. Call existing `generate($scale)` to get base rolls
2. For each of the 5 categories (pay, support, salvage, transport, command), calculate the current step
3. Apply reputation: each point of reputation allows shifting one category up by 1 step (max 2 × scale total shifts)
4. Allow "swaps": sacrifice 2 steps from one category to gain 1 step in another (max 2 swaps)
5. Return the negotiated contract data

The key change: instead of the generated terms being final, the player can redistribute their reputation budget across categories. The UI will let them choose which categories to boost.

### 4. `ContractService` — Reputation Adjustment on Track Outcomes

Modify `handlePostTrack()` in `src/Service/ContractService.php` to adjust reputation after recording combat pay:

```php
public function handlePostTrack(Contract $contract, array $formData, int $month): void {
    // ... existing logic ...

    // NEW: Adjust company reputation based on track outcome
    $company = $contract->getCompany();
    if ($company) {
        $tier = $formData['combatPayTier'];
        $company->adjustReputationForTrack($tier);
    }

    // ... rest of existing logic ...
}
```

Per the rules (hinterlands.md p. 17):
- **Successful track** (player scores more objectives than opponent): **+1 reputation** (applies to `Full` and `HalfAgain`)
- **Unsuccessful track** (opponent scores more, player gets half pay): **−1 reputation** (`Half`)
- **Tie / no combat pay**: **0** (`None`)
- **Breaching contract** (leaving before end, refusing objectives): **−3 reputation** — add a new method `breachContract(Contract)` in `ContractService`

Note: "All objectives" (1.5× combat pay) affects **pay only**, not reputation. Reputation is always +1 for any successful track.

### 5. `ContractController` — Negotiation Endpoint & Reputation Display

Add a new route for contract negotiation:

```php
#[Route('/contract/generate/negotiate', name: 'app_contracts_negotiate', methods: ['POST'])]
public function negotiate(Request $request, EntityManagerInterface $em, ContractService $contractService, ContractGeneratorService $generator): Response
```

This endpoint:
1. Generates base contract data via `generateWithNegotiation()`
2. Accepts POST data with negotiated category shifts (which categories to boost, swap targets)
3. Creates the final contract with negotiated terms

Also modify `generate()` GET route to show the company's current reputation on the contract list page.

### 6. Templates

**`templates/contract/generate.html.twig`**: Add a negotiation section showing:
- Current company reputation
- Available negotiation points (2 × scale)
- For each of the 5 categories: current step, available shifts
- Swap controls (sacrifice from one category to boost another)

**`templates/contract/index.html.twig`**: Add a "Reputation" column showing `company.reputation`.

## Implementation Order (Checklist)

- [ ] **Step 1**: `MercenaryCompany.php` — Add `adjustReputation()`, `getScaleFromReputation()`, `getMaxNegotiationSteps()`
- [ ] **Step 2**: `ContractStepsTable.php` — Add `getStepValues()`, helper methods for reading individual categories from a step
- [ ] **Step 3**: `ContractService.php` — Add `adjustReputationForTrack(Contract, CombatPayTier)` and `breachContract(Contract)`
- [ ] **Step 4**: `ContractGeneratorService.php` — Add `generateWithNegotiation(int $scale, int $reputation): array` that wraps existing generation and applies reputation shifts
- [ ] **Step 5**: `ContractController.php` — Add `negotiate()` POST endpoint; update `generate()` GET to pass reputation to template
- [ ] **Step 6**: `generate.html.twig` — Add negotiation UI (reputation display, category shift controls, swap controls)
- [ ] **Step 7**: `index.html.twig` — Add reputation column
- [ ] **Step 8**: Unit tests for `MercenaryCompany::adjustReputation()`, `ContractStepsTable` helpers, `ContractGeneratorService::generateWithNegotiation()`, `ContractService::adjustReputationForTrack()`

## Risk / Gotchas

1. **Existing contracts**: Contracts already in the DB have no reputation tied to them. New contracts created after this change will use the company's current reputation. Old contracts remain unaffected.

2. **Backwards compatibility**: The `generate()` method should remain unchanged. The new `generateWithNegotiation()` is additive.

3. **Reputation floor**: Per rules, negative reputation has no mechanical effect other than needing to climb back. We floor at 0.

4. **Scale/Rank drops**: Per rules (p. 41), if a regular military commander drops enough reputation to lose 2 scales, they lose a scale. For mercs, reputation just affects negotiation leverage — no combat penalty. We should note this in the UI.

5. **DB migration**: The `reputation` column already exists in the DB (from the entity). No migration needed.

6. **Swaps are tricky**: "Sacrifice 2 steps in one category to increase another by 1 step, max 2 swaps." This means you can give up 4 steps total to gain 2 steps in another category. The UI needs to make this clear.
