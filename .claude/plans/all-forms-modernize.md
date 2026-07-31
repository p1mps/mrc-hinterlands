# Plan: Modernize all forms — single column, centered

## Problem

Forms are inconsistent: some are flat with no grid, some use 2-column grid, one uses `form_widget()` to dump all fields. No form is centered or has a modern card-like appearance.

## Target Design

Every form page gets:
- Centered layout via `row justify-content-center`
- Single-column form wrapped in `col-md-6` (wider for large forms) or `col-md-8` (for the 15-field contract edit)
- `form_start` with Bootstrap classes
- Consistent "Save" button (primary) + "Back" link (outline-secondary)
- `form_end`
- `mb-3` spacing on buttons

No form type changes needed — only template rewrites.

## Files to Change (13 templates)

| # | Template | Fields | Width |
|---|----------|--------|-------|
| 1 | `templates/pilot/form.html.twig` | 6 | `col-md-6` |
| 2 | `templates/roster/form.html.twig` | 7 | `col-md-6` |
| 3 | `templates/salvaged_mech/_form.html.twig` | all (via widget) | `col-md-6` |
| 4 | `templates/salvaged_mech/edit.html.twig` | 7 | `col-md-6` |
| 5 | `templates/salvaged_mech/new.html.twig` | 5 | `col-md-6` |
| 6 | `templates/salvaged_mech/new_with_check.html.twig` | 5 | `col-md-6` |
| 7 | `templates/salvaged_mech/battlefield_edit.html.twig` | 5 | `col-md-6` |
| 8 | `templates/salvaged_mech/scrapyard_edit.html.twig` | 5 | `col-md-6` |
| 9 | `templates/contract/edit.html.twig` | 15 | `col-md-8` |
| 10 | `templates/contract_log/edit.html.twig` | 4+conditional | `col-md-6` |
| 11 | `templates/dropship/new.html.twig` | 2 | `col-md-6` |
| 12 | `templates/dropship/edit.html.twig` | 2 | `col-md-6` |
| 13 | `templates/security/register.html.twig` | 5 | `col-md-5` (already centered, just fix form_start) |

## Leave As-Is (functional layouts, not edit forms)

- `contract_log/add.html.twig` — multi-card dashboard with JS calculations
- `support_point/index.html.twig` — combined sidebar form + table page
- `contract/generate.html.twig` — data display page
- All read-only pages (show, index, dashboard, rules, roster index, etc.)

## Pattern (applied to all 13)

```twig
{% extends 'base.html.twig' %}
{% block body %}
<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>{{ title }}</h2>
        <a href="{{ path('app_xxx') }}" class="btn btn-outline-secondary mb-3">Back to List</a>
        {{ form_start(form, {attr: {class: 'mb-3'}}) }}
            {{ form_row(form.field1) }}
            {{ form_row(form.field2) }}
            ...
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Save</button>
            </div>
        {{ form_end(form) }}
    </div>
</div>
{% endblock %}
```

Key rules:
- `row justify-content-center` + `col-md-6` for centering
- `col-md-8` only for contract edit (15 fields)
- `form_start` gets `{attr: {class: 'mb-3'}}` (not grid class)
- Each `form_row` is a full-width field inside the centered column
- Buttons wrapped in `d-flex gap-2` for alignment
- "Back to List" link pointing to the index route

## Specific Notes Per File

### 1. `pilot/form.html.twig`
- 6 fields: name, isNamed, gunnery, piloting, gunneryXp, pilotingXp
- Back link → `app_pilots`

### 2. `roster/form.html.twig`
- 7 fields: name, chassis, tonnage, bv, unitType, damageState, isActive
- Back link → `app_roster`

### 3. `salvaged_mech/_form.html.twig`
- Uses `{{ form_widget(form) }}` — replace with explicit `form_row()` calls
- This partial is included by edit, new, battlefield_edit, scrapyard_edit templates
- Fields: model, tonnage, bvCost, contract, damageState, techBase, salvageRightsPercent, scrapyard
- Must match `SalvagedMechType` field order

### 4–8. Salvaged Mech templates
- All use the same underlying `SalvagedMechType` form class
- `edit.html.twig`, `new.html.twig`, `battlefield_edit.html.twig`, `scrapyard_edit.html.twig`
- Back link → `app_salvaged_mech_show` (edit/battlefield/scrapyard) or `app_salvaged_mech_index` (new)
- `new_with_check.html.twig` is a separate workflow — back link → `app_salvaged_mech_index`

### 9. `contract/edit.html.twig`
- 15 fields — use `col-md-8` for breathing room
- Back link → `app_contracts_show` (via contract.id)

### 10. `contract_log/edit.html.twig`
- 4 base fields + conditional block (missionType, terrain, complication when entryType == 'track_setup')
- Back link → `app_contracts_show` (via contract.id)
- Must preserve the conditional block for track_setup entries

### 11–12. Dropship templates
- 2 fields each: name, maxCapacity
- Back link → `app_dropship_show`
- `new.html.twig` also has a "no dropship" empty state — preserve that

### 13. `security/register.html.twig`
- Already centered (`col-md-5`), just needs `form_start` with class
- Back link → `app_login`

## Checklist

- [ ] `templates/pilot/form.html.twig` — centered, single column
- [ ] `templates/roster/form.html.twig` — centered, single column
- [ ] `templates/salvaged_mech/_form.html.twig` — explicit form_row calls, centered
- [ ] `templates/salvaged_mech/edit.html.twig` — centered, single column
- [ ] `templates/salvaged_mech/new.html.twig` — centered, single column
- [ ] `templates/salvaged_mech/new_with_check.html.twig` — centered, single column
- [ ] `templates/salvaged_mech/battlefield_edit.html.twig` — centered, single column
- [ ] `templates/salvaged_mech/scrapyard_edit.html.twig` — centered, single column
- [ ] `templates/contract/edit.html.twig` — centered, `col-md-8`
- [ ] `templates/contract_log/edit.html.twig` — centered, preserve conditional block
- [ ] `templates/dropship/new.html.twig` — centered, single column
- [ ] `templates/dropship/edit.html.twig` — centered, single column
- [ ] `templates/security/register.html.twig` — fix form_start
- [ ] Run `bin/phpunit` to verify
