# Dropship Feature Plan

## Summary

Add a `Dropship` entity to the mercenary company management system with:
- One-to-one relationship with `MercenaryCompany` (each company gets exactly 1 dropship)
- Configurable `maxCapacity` (max salvaged mechs the dropship can hold)
- Full CRUD (create, edit, delete)
- Capacity enforcement at mech assignment time
- Proper handling of owned entities on delete

## Architecture Decisions

### Dropship-SalvagedMech Relationship

A `SalvagedMech` is "on a dropship" when its `dropship` field is non-null. This is independent of the `acquired` status:
- A mech can be on a dropship but not yet acquired (waiting to be added to roster)
- A mech can be on a dropship and already acquired (edge case, but handled)
- When a mech is acquired, it is **unassigned from the dropship** (sets `dropship` to null) — this is the only way mechs leave a dropship

### Capacity Enforcement Strategy

- **At creation**: `maxCapacity` can be any positive integer. No validation against current mech count (there are none).
- **At mech assignment**: Before setting a SalvagedMech's `dropship`, count existing mechs on that dropship. If count >= maxCapacity, reject with a clear error.
- **At capacity shrink (edit)**: Allow shrinking `maxCapacity` even below current count. This doesn't remove existing mechs. New assignments will be rejected until the count drops below the new limit. This is the simplest and most predictable behavior — no silent data mutation.
- **At delete**: Unassign all mechs (set their `dropship` to null) rather than blocking deletion. This follows the `onDelete: 'SET NULL'` convention already used in the codebase (e.g., `SalvagedMech.contract` uses `onDelete: 'SET NULL'`).

### Authorization

Follow the existing inline pattern: `$this->getUser()->getCompany()` compared with `$dropship->getCompany()`. No centralized auth service exists.

### ID Type

All IDs in this project are `int` (auto-increment). Dropship uses `int`.

---

## Files to Create/Modify

### New Files

1. **`src/Entity/Dropship.php`** — Entity class
2. **`src/Repository/DropshipRepository.php`** — Repository (extends ServiceEntityRepository)
3. **`src/Service/DropshipService.php`** — Service with capacity enforcement logic
4. **`src/Controller/DropshipController.php`** — Controller with CRUD routes
5. **`src/Form/DropshipType.php`** — Form type for create/edit
6. **`tests/Unit/Service/DropshipServiceTest.php`** — Unit tests
7. **`tests/Unit/Service/DropshipControllerTest.php`** — Controller tests (full-stack integration with real DB)

### Modified Files

8. **`src/Entity/SalvagedMech.php`** — Add `dropship` ManyToOne relationship + getters/setters
9. **`src/Entity/MercenaryCompany.php`** — Add `dropship` OneToOne relationship + getters/setters
10. **`src/Service/MechAcquisitionService.php`** — Unassign mech from dropship during acquisition

---

## Detailed Design

### 1. Dropship Entity (`src/Entity/Dropship.php`)

```php
<?php
namespace App\Entity;

use App\Repository\DropshipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DropshipRepository::class)]
class Dropship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'dropship', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private MercenaryCompany $company;

    #[ORM\Column(type: 'integer')]
    private int $maxCapacity;

    // getters/setters for id, company, maxCapacity
}
```

- `maxCapacity` is `int` (no nullable — must be set at creation)
- `OneToOne` to `MercenaryCompany` with `unique: true` on join column enforces "one dropship per company" at the DB level
- `cascade: ['persist', 'remove']` so dropship is saved/deleted with its company

### 2. SalvagedMech Modification (`src/Entity/SalvagedMech.php`)

Add to existing entity:

```php
#[ORM\ManyToOne(inversedBy: 'salvagedMechs')]
#[ORM\JoinColumn(onDelete: 'SET NULL')]
private ?Dropship $dropship = null;
```

Plus getters/setters:

```php
public function getDropship(): ?Dropship { return $this->dropship; }
public function setDropship(?Dropship $dropship): static { $this->dropship = $dropship; return $this; }
```

### 3. MercenaryCompany Modification (`src/Entity/MercenaryCompany.php`)

Add to existing entity:

```php
#[ORM\OneToOne(mappedBy: 'company', cascade: ['persist', 'remove'])]
private ?Dropship $dropship = null;
```

Plus getter:

```php
public function getDropship(): ?Dropship { return $this->dropship; }
```

### 4. DropshipRepository (`src/Repository/DropshipRepository.php`)

```php
<?php
namespace App\Repository;

use App\Entity\Dropship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DropshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dropship::class);
    }

    public function countMechsOnDropship(int $dropshipId): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(s.id)')
            ->join(SalvagedMech::class, 's', 'WITH', 's.dropship = d')
            ->where('d.id = :id')
            ->setParameter('id', $dropshipId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCompanyId(int $companyId): ?Dropship
    {
        return $this->findOneBy(['company' => $companyId]);
    }
}
```

### 5. DropshipService (`src/Service/DropshipService.php`)

```php
<?php
namespace App\Service;

use App\Entity\Dropship;
use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use Doctrine\ORM\EntityManagerInterface;

class DropshipService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function getDropship(int $id): ?Dropship
    {
        return $this->em->getRepository(Dropship::class)->find($id);
    }

    public function getDropshipByCompany(MercenaryCompany $company): ?Dropship
    {
        return $this->em->getRepository(Dropship::class)->findByCompanyId($company->getId());
    }

    public function createDropship(MercenaryCompany $company, int $maxCapacity): Dropship
    {
        // Check if company already has a dropship
        $existing = $this->getDropshipByCompany($company);
        if ($existing) {
            throw new \LogicException('This company already has a dropship. Each company may only have one dropship.');
        }

        if ($maxCapacity <= 0) {
            throw new \InvalidArgumentException('Dropship maxCapacity must be a positive integer.');
        }

        $dropship = new Dropship();
        $dropship->setCompany($company);
        $dropship->setMaxCapacity($maxCapacity);

        $this->em->persist($dropship);
        $this->em->flush();

        return $dropship;
    }

    public function updateDropship(Dropship $dropship, int $newMaxCapacity): void
    {
        // Validate capacity is positive
        if ($newMaxCapacity <= 0) {
            throw new \InvalidArgumentException('Dropship maxCapacity must be a positive integer.');
        }

        // Note: We allow shrinking below current mech count.
        // Existing mechs stay assigned; new assignments will be rejected until count drops.

        $dropship->setMaxCapacity($newMaxCapacity);
        $this->em->flush();
    }

    public function deleteDropship(Dropship $dropship): void
    {
        // Unassign all mechs before deleting (cascade handles the rest)
        $repo = $this->em->getRepository(SalvagedMech::class);
        foreach ($repo->findBy(['dropship' => $dropship]) as $mechan) {
            $mechan->setDropship(null);
        }
        $this->em->remove($dropship);
        $this->em->flush();
    }

    public function assignMechToDropship(SalvagedMech $mechan, Dropship $dropship): void
    {
        $company = $dropship->getCompany();

        // Authorization check
        $currentCompany = $this->em->getConnection()->fetchOne(
            'SELECT company_id FROM dropship WHERE id = ?', ['dropship_id' => $dropship->getId()]
        );
        // Actually, we check via the service layer:
        // The caller should verify $this->getUser()->getCompany() === $dropship->getCompany()
        // in the controller. The service just enforces capacity.

        // Capacity check
        $mechanCount = $this->em->getRepository(Dropship::class)
            ->countMechsOnDropship($dropship->getId());

        if ($mechanCount >= $dropship->getMaxCapacity()) {
            throw new \LogicException(
                "Dropship '{$dropship->getCompany()->getName()}' is at full capacity ({$mechanCount}/{$dropship->getMaxCapacity()} mechs). Cannot assign additional mechs."
            );
        }

        $mechan->setDropship($dropship);
        $this->em->flush();
    }

    public function countMechsOnDropship(Dropship $dropship): int
    {
        return $this->em->getRepository(Dropship::class)->countMechsOnDropship($dropship->getId());
    }
}
```

Wait, the repository method uses `SalvagedMech` — I need to import it. Let me refine:

```php
use App\Entity\SalvagedMech;
```

### 6. DropshipController (`src/Controller/DropshipController.php`)

```php
<?php
namespace App\Controller;

use App\Entity\Dropship;
use App\Form\DropshipType;
use App\Service\DropshipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dropship')]
class DropshipController extends AbstractController
{
    #[Route('/', name: 'app_dropship_show', methods: ['GET'])]
    public function show(DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();
        $dropship = $dropshipService->getDropshipByCompany($company);

        return $this->render('dropship/show.html.twig', [
            'dropship' => $dropship,
        ]);
    }

    #[Route('/new', name: 'app_dropship_new', methods: ['GET', 'POST'])]
    public function new(Request $request, DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();

        $existing = $dropshipService->getDropshipByCompany($company);
        if ($existing) {
            $this->addFlash('error', 'This company already has a dropship.');
            return $this->redirectToRoute('app_dropship_show');
        }

        $dropship = new Dropship();
        $form = $this->createForm(DropshipType::class, $dropship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dropshipService->createDropship($company, $dropship->getMaxCapacity());
                $this->addFlash('success', 'Dropship created successfully.');
                return $this->redirectToRoute('app_dropship_show');
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('dropship/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dropship_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dropship $dropship, DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();

        // Authorization: only the owning company can edit
        if ($dropship->getCompany() !== $company) {
            $this->addFlash('error', 'You do not have permission to edit this dropship.');
            return $this->redirectToRoute('app_dropship_show');
        }

        $form = $this->createForm(DropshipType::class, $dropship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dropshipService->updateDropship($dropship, $dropship->getMaxCapacity());
                $this->addFlash('success', 'Dropship updated successfully.');
                return $this->redirectToRoute('app_dropship_show');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('dropship/edit.html.twig', [
            'dropship' => $dropship,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_dropship_delete', methods: ['POST'])]
    public function delete(Request $request, Dropship $dropship, DropshipService $dropshipService): Response
    {
        $company = $this->getUser()->getCompany();

        // Authorization
        if ($dropship->getCompany() !== $company) {
            $this->addFlash('error', 'You do not have permission to delete this dropship.');
            return $this->redirectToRoute('app_dropship_show');
        }

        if ($this->isCsrfTokenValid('delete' . $dropship->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $dropshipService->deleteDropship($dropship);
                $this->addFlash('success', 'Dropship deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to delete dropship: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_dropship_show');
    }
}
```

### 7. DropshipType Form (`src/Form/DropshipType.php`)

```php
<?php
namespace App\Form;

use App\Entity\Dropship;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DropshipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('maxCapacity', IntegerType::class, [
            'label' => 'Maximum Salvaged Mech Capacity',
            'attr' => ['min' => 1],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Dropship::class]);
    }
}
```

### 8. SalvagedMech Modification

Add `dropship` field + getters/setters.

### 9. MercenaryCompany Modification

Add `dropship` getter.

### 10. MechAcquisitionService Modification

In `acquireMech()`, after marking the SalvagedMech as acquired, unassign it from its dropship:

```php
// After $salvagedMech->setAcquired(true);
if ($salvagedMech->getDropship() !== null) {
    $salvagedMech->setDropship(null);
}
```

This frees up capacity on the dropship when a mech is acquired into the roster.

---

## Tests (`tests/Unit/Service/DropshipServiceTest.php`)

### Service Tests

1. `testCreateDropshipCreatesEntityWithValidCapacity` — happy path
2. `testCreateDropshipRejectsZeroCapacity` — validation
3. `testCreateDropshipRejectsNegativeCapacity` — validation
4. `testCreateDropshipRejectsSecondDropshipForCompany` — LogicException with clear message
5. `testUpdateDropshipAllowsShrinkingBelowCurrentCount` — no error, existing mechs stay
6. `testUpdateDropshipRejectsZeroCapacity` — validation
7. `testAssignMechToDropshipSucceedsWhenUnderCapacity` — happy path
8. `testAssignMechToDropshipRejectsWhenAtCapacity` — LogicException with clear message
9. `testDeleteDropshipUnassignsAllMechs` — mechs get dropship=null
10. `testDeleteDropshipWithoutMechsSucceeds` — no mechs, just delete

### Controller Tests (Full-Stack Integration)

1. `testNewDropshipCreatesDropship` — POST creates dropship
2. `testNewDropshipRejectsWhenCompanyAlreadyHasDropship` — 2nd dropship rejected
3. `testEditDropshipUpdatesCapacity` — PUT updates
4. `testEditDropshipRejectsUnauthorizedCompany` — ownership check
5. `testDeleteDropshipWithMechsUnassignsMechs` — cascade behavior
6. `testDeleteDropshipWithoutMechsSucceeds` — clean delete

---

## Expected Behavior Summary

| Scenario | Behavior |
|---|---|
| Create first dropship with valid capacity | Success |
| Create second dropship for same company | `LogicException`: "This company already has a dropship" |
| Create dropship with maxCapacity=0 or negative | `InvalidArgumentException` |
| Assign mech when under capacity | Success |
| Assign mech when at capacity | `LogicException`: "Dropship is at full capacity" |
| Shrink capacity below current mech count | Allowed (no error, existing mechs stay, new assignments blocked) |
| Delete dropship with mechs | All mechs unassigned (dropship=null), dropship deleted |
| Delete dropship without mechs | Dropship deleted |
| Acquire mech on dropship | Mech unassigned from dropship (capacity freed) |
| Unauthorized company edits dropship | Flash error, redirected |
| Unauthorized company deletes dropship | Flash error, redirected |
