# Assign Pilot to Unit Plan

**Goal:** Let a player assign (or unassign) a pilot to a unit directly from the roster page.

**Affected files:**
- Modify: `src/Controller/RosterController.php` — add `assignPilot` action; pass pilots to `index`
- Modify: `templates/roster/index.html.twig` — add inline assign form per unit row

**Logic:**
- The select shows: "— none —" + every company pilot that is either unassigned OR already assigned to *this* unit (pilots assigned to another unit are excluded).
- Submitting with the empty option unassigns any existing pilot.
- The FK lives on `Unit`, so `$unit->setPilot($pilot)` is the only write needed.

---

## Task Checklist

- [ ] **1. Pass pilots to roster index**

In `RosterController::index()`, query all pilots for the company and pass them to the template:

```php
#[Route('', name: 'app_roster')]
public function index(): Response {
    $company = $this->getUser()->getCompany();
    return $this->render('roster/index.html.twig', [
        'company' => $company,
        'units'   => $company->getUnits(),
        'pilots'  => $company->getPilots(),
        'totalBv' => $company->getTotalBv(),
    ]);
}
```

- [ ] **2. Add `assignPilot` action to `RosterController`**

```php
#[Route('/{id}/assign-pilot', name: 'app_roster_assign_pilot', methods: ['POST'])]
public function assignPilot(Unit $unit, Request $request, EntityManagerInterface $em): Response {
    if ($unit->getCompany() !== $this->getUser()->getCompany()) {
        throw $this->createAccessDeniedException();
    }
    $pilotId = $request->request->get('pilot_id');
    if ($pilotId) {
        $pilot = $em->getRepository(Pilot::class)->find((int) $pilotId);
        if ($pilot && $pilot->getCompany() === $this->getUser()->getCompany()) {
            $unit->setPilot($pilot);
        }
    } else {
        $unit->setPilot(null);
    }
    $em->flush();
    return $this->redirectToRoute('app_roster');
}
```

Also add `use App\Entity\Pilot;` to the imports.

- [ ] **3. Add inline assign form to `templates/roster/index.html.twig`**

Replace the pilot cell (`<td>{{ unit.pilot ? unit.pilot.name : '—' }}</td>`) with a small inline form:

```twig
<td>
    <form method="post" action="{{ path('app_roster_assign_pilot', {id: unit.id}) }}" class="d-flex gap-1">
        <select name="pilot_id" class="form-select form-select-sm">
            <option value="">— none —</option>
            {% for pilot in pilots %}
                {% if pilot.unit is null or pilot.unit.id == unit.id %}
                    <option value="{{ pilot.id }}"
                        {{ unit.pilot and unit.pilot.id == pilot.id ? 'selected' : '' }}>
                        {{ pilot.name }} ({{ pilot.gunnery }}/{{ pilot.piloting }})
                    </option>
                {% endif %}
            {% endfor %}
        </select>
        <button class="btn btn-sm btn-outline-secondary">Set</button>
    </form>
</td>
```
