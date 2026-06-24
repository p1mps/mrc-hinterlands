# Step 0 — Transport Plan

**Goal:** Add a "Transport" step (Step 0) to the Add Log Entry page, recording an editable SP amount (default +300).

**Affected files:**
- Modify: `src/Enum/ContractLogEntryType.php` — add `Transport` case
- Modify: `src/Controller/ContractLogController.php` — handle `transport` action, add `handleTransport`
- Modify: `templates/contract_log/add.html.twig` — add Step 0 card before Step 1

---

## Task Checklist

- [ ] **1. Add `Transport` case to `ContractLogEntryType`**

```php
case Transport = 'transport';
```

- [ ] **2. Handle `transport` action in `ContractLogController::add()`**

```php
if ($action === 'transport') {
    $this->handleTransport($contract, $company, (int) $request->request->get('amount', 300));
    $this->addFlash('success', 'Transport recorded.');
}
```

- [ ] **3. Add `handleTransport` private method**

```php
private function handleTransport(Contract $contract, $company, int $amount): void {
    $sp = new SupportPointEntry();
    $sp->setCompany($company);
    $sp->setAmount($amount);
    $sp->setDescription("Transport (scale {$contract->getScale()})");
    $this->em->persist($sp);

    $log = new ContractLogEntry();
    $log->setContract($contract);
    $log->setMonth($contract->getTracksCompleted() + 1);
    $log->setEntryType(ContractLogEntryType::Transport);
    $log->setDescription("Transport: " . ($amount >= 0 ? "+$amount" : "$amount") . " SP");
    $this->em->persist($log);
    $this->em->flush();
}
```

- [ ] **4. Add Step 0 card to `templates/contract_log/add.html.twig`**

Insert before the existing Step 1 card:

```twig
<div class="col-md-6">
<div class="card">
<div class="card-header">Step 0 — Transport</div>
<div class="card-body">
<form method="post">
    <input type="hidden" name="action" value="transport">
    <div class="mb-2">
        <label class="form-label">Amount (SP)</label>
        <input type="number" name="amount" class="form-control" value="-300">
    </div>
    <button class="btn btn-info">Record Transport</button>
</form>
</div>
</div>
</div>
```
