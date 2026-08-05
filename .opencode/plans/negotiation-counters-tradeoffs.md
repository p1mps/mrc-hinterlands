# Negotiation View: Counters & Trade-Off Rules

## Summary
Add 3 counters (Scale, Reputation Spent, Trade-Offs Used) to the negotiation view and implement the trade-off mechanic (sacrifice 2 steps from one category to boost another by 1, max 2 trade-offs).

## Files to Modify

### 1. `templates/contract/negotiate.html.twig` (lines 1-364)

**Counters UI** — Add after the `#negotiation-toast` div (line 21), before the negotiation table:
```html
<div id="negotiation-counters" class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <strong>Contract Scale:</strong> <span id="counter-scale">[SCALE]</span>
            </div>
            <div class="col-md-4">
                <strong>Reputation Spent / Max Allowed:</strong>
                <span id="counter-rep-spent">0</span> / <span id="counter-rep-max">[2*SCALE]</span>
            </div>
            <div class="col-md-4">
                <strong>Trade-Offs Used / Max Allowed:</strong>
                <span id="counter-tradeoffs">0</span> / 2
            </div>
        </div>
    </div>
</div>
```

**Trade-Off UI** — Add a "Trade Off" button next to the "Accept" button (line 108):
```html
<button id="trade-off-btn" class="btn btn-warning" disabled>Trade Off (0/2)</button>
```

**JavaScript changes** (inline script, lines 113-362):
- Add `let reputationSpent = 0;` (tracks total reputation points spent across all shifts)
- Add `let tradeOffsUsed = 0;` (tracks number of trade-off operations, max 2)
- Add `updateCounters()` function that updates the 3 counter spans
- Call `updateCounters()` at end of `updateUI()`, after every successful shift, and after form submission
- Add trade-off button click handler:
  1. Opens a modal/dialog to select source category (to sacrifice 2 steps) and target category (to gain 1 step)
  2. Validates source has ≥2 steps to sacrifice, target has room to gain 1 step
  3. Sends to backend via `/contract/{id}/negotiate/tradeoff`
  4. On success: decreases source by 2 steps, increases target by 1 step, increments `tradeOffsUsed`
  5. Updates counters, disables button when `tradeOffsUsed >= 2`

### 2. `src/Controller/ContractController.php` (lines 1-467)

**New endpoint** — Add `negotiateTradeOff()` method (after `negotiateShift`, ~line 327):
```php
#[Route('/contract/{id}/negotiate/tradeoff', name: 'app_contracts_negotiate_tradeoff', methods: ['POST'])]
public function negotiateTradeOff(string $sourceCategory, string $targetCategory, Contract $contract, Request $request): Response
```

The trade-off endpoint:
- Receives: `sourceCategory`, `targetCategory`, `state` (current steps), `tradeOffsUsed`
- Validates: `tradeOffsUsed < 2`
- Validates: source category has ≥2 steps to sacrifice (next valid below is at least 2 steps down)
- Validates: target category has room to gain 1 step (next valid above is at least 1 step up)
- Performs: decrease source by 2 steps (to next valid), increase target by 1 step (to next valid)
- Returns: updated row HTMLs for both categories, new state, new `tradeOffsUsed`

**Existing `negotiateShift`** — No logic changes needed. It already correctly handles:
- Empty step validation (skips to next valid step via `getNextValidStepAbove`/`getPrevValidStepBelow`)
- Reputation budget enforcement (`$cost > $availableShifts`)
- Refunds on downward shifts

**Existing `validateNegotiationState`** — No changes needed. It validates steps are valid for categories (empty steps rejected) and that `shiftsUsed <= maxShifts`.

**Existing `negotiateAccept`** — Add `tradeOffsUsed` to the form handling:
- Read `tradeOffsUsed` from the form submission
- Validate `tradeOffsUsed <= 2`

### 3. `templates/contract/_negotiation_row.html.twig` (lines 1-20)

No changes needed — the AJAX row partial already renders correctly for both shift-up and shift-down operations. The trade-off endpoint will return two row HTMLs (one for source, one for target).

## Key Negotiation Rules Enforced

| Rule | Enforcement |
|------|-------------|
| Max reputation = 2 × Scale | Backend: `validateNegotiationState` checks `shiftsUsed <= maxShifts`. Frontend: buttons disabled when cost > availableShifts. |
| 1 Reputation = 1 step | Frontend: `cost = nextValid - currentStep` reputation points spent per shift. |
| Cannot land on empty steps | Backend: `isStepValidForCategory` rejects empty steps. Frontend: `getNextValidStepAbove`/`getPrevValidStepBelow` skip empty steps. |
| Max 2 trade-offs | Backend: `negotiateTradeOff` checks `tradeOffsUsed < 2`. Frontend: button disabled when `tradeOffsUsed >= 2`. |
| Trade-off: sacrifice 2 steps → gain 1 step | Backend: source decreased by 2 (to next valid), target increased by 1 (to next valid). |

## Trade-Off UX Design

The trade-off button, when clicked, will open a simple modal with:
- Dropdown: Source category (to sacrifice 2 steps)
- Dropdown: Target category (to gain 1 step)
- "Execute Trade-Off" button

The modal will:
- Only show source categories where the current step is ≥2 steps above the next valid step below
- Only show target categories where there's a valid step ≥1 step above current
- Disable "Execute" if source == target, or if either selection is invalid

## Testing

The existing acceptance tests (`ContractAcceptanceTest.php`) test the shift-up/shift-down flow. New tests should be added for:
1. Trade-off endpoint (success case)
2. Trade-off endpoint (exceeding 2 trade-offs → 400)
3. Trade-off endpoint (insufficient steps to sacrifice → 400)
4. Counters visible on negotiate view page

## Implementation Order

1. [ ] Add counters HTML to `negotiate.html.twig`
2. [ ] Add JavaScript tracking (`reputationSpent`, `tradeOffsUsed`, `updateCounters()`)
3. [ ] Add trade-off button + modal to template
4. [ ] Add `negotiateTradeOff()` endpoint to `ContractController.php`
5. [ ] Update `negotiateAccept` to handle `tradeOffsUsed`
6. [ ] Run existing tests to ensure no regressions
