# Contract: Planet & Intensity Fields Plan

**Goal:** Add nullable `planet` (string) and `intensity` (string) fields to `Contract`.

**Affected files:**
- Modify: `src/Entity/Contract.php`
- Create: migration
- Modify: `src/Form/ContractEditFormType.php` — add both fields
- Modify: `templates/contract/show.html.twig` — display both fields

**Both fields are nullable** — they are not generated, so existing contracts and the accept flow leave them blank until the player fills them in via the edit form.

---

## Task Checklist

- [ ] **1. Add fields to `Contract` entity**

```php
#[ORM\Column(length: 255, nullable: true)]
private ?string $planet = null;

#[ORM\Column(length: 255, nullable: true)]
private ?string $intensity = null;

public function getPlanet(): ?string { return $this->planet; }
public function setPlanet(?string $planet): static { $this->planet = $planet; return $this; }

public function getIntensity(): ?string { return $this->intensity; }
public function setIntensity(?string $intensity): static { $this->intensity = $intensity; return $this; }
```

- [ ] **2. Generate and run migration**

- [ ] **3. Add fields to `ContractEditFormType`**

```php
->add('planet', TextType::class, ['required' => false])
->add('intensity', TextType::class, ['required' => false])
```

- [ ] **4. Add rows to contract show table**

```twig
<tr><th>Planet</th><td>{{ contract.planet ?? '—' }}</td></tr>
<tr><th>Intensity</th><td>{{ contract.intensity ?? '—' }}</td></tr>
```
