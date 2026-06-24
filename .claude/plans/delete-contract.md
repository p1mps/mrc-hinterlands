# Delete Contract Plan

**Goal:** Add a delete button to the My Contracts list that removes the contract and handles its linked opposing contract.

**Affected files:**
- Modify: `src/Controller/ContractController.php` — add `delete` action
- Modify: `templates/contract/index.html.twig` — add delete button per row

**Linked contract logic:**
- If the opposing contract is unclaimed (`company === null`), delete it too.
- If it has been claimed by another player, just unlink both sides (set `linkedContract` to null on both) before removing the primary.

---

## Task Checklist

- [ ] **1. Add `delete` action to `ContractController`**

```php
#[Route('/{id}/delete', name: 'app_contracts_delete', methods: ['POST'])]
public function delete(Contract $contract): Response {
    if ($contract->getCompany() !== $this->getUser()->getCompany()) {
        throw $this->createAccessDeniedException();
    }
    $linked = $contract->getLinkedContract();
    if ($linked !== null) {
        $linked->setLinkedContract(null);
        $contract->setLinkedContract(null);
        if ($linked->getCompany() === null) {
            $this->em->remove($linked);
        }
    }
    $this->em->remove($contract);
    $this->em->flush();
    $this->addFlash('success', 'Contract deleted.');
    return $this->redirectToRoute('app_contracts');
}
```

- [ ] **2. Add delete button to My Contracts table in `templates/contract/index.html.twig`**

```twig
<td>
    <a href="{{ path('app_contracts_show', {id: c.id}) }}" class="btn btn-sm btn-outline-primary">View</a>
    <form method="post" action="{{ path('app_contracts_delete', {id: c.id}) }}" class="d-inline"
          onsubmit="return confirm('Delete this contract?')">
        <button class="btn btn-sm btn-danger">Del</button>
    </form>
</td>
```
