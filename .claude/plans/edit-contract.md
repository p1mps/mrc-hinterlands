# Edit Contract Plan

**Goal:** Allow a player to edit the fields of a contract they own after it has been generated and accepted.

**Affected files:**
- Create: `src/Form/ContractEditFormType.php`
- Modify: `src/Controller/ContractController.php` — add `edit` action
- Create: `templates/contract/edit.html.twig`
- Modify: `templates/contract/show.html.twig` — add Edit button

**Scope:** All editable contract fields (employer, affiliation, scale, duration, base pay percent, command rights, support terms, salvage rights, transport terms, number of tracks, type). Status and system fields (tracksCompleted, isOpposing, linkedContract, company) are not editable.

**Authorization:** Only the owning company can edit; throw AccessDeniedException otherwise.

---

## Task Checklist

- [ ] **1. Create `src/Form/ContractEditFormType.php`**

```php
<?php
namespace App\Form;

use App\Enum\CommandRights;
use App\Enum\ContractType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Contract;

class ContractEditFormType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('type', EnumType::class, ['class' => ContractType::class, 'label' => 'Contract Type'])
            ->add('employer', TextType::class)
            ->add('employerAffiliation', TextType::class, ['label' => 'Employer Affiliation'])
            ->add('scale', IntegerType::class)
            ->add('durationMonths', IntegerType::class, ['label' => 'Duration (months)'])
            ->add('basePayPercent', IntegerType::class, ['label' => 'Base Pay %', 'required' => false])
            ->add('commandRights', EnumType::class, ['class' => CommandRights::class, 'label' => 'Command Rights'])
            ->add('supportTerms', TextType::class, ['label' => 'Support Terms'])
            ->add('salvageRights', TextType::class, ['label' => 'Salvage Rights'])
            ->add('transportTerms', TextType::class, ['label' => 'Transport Terms'])
            ->add('numberOfTracks', IntegerType::class, ['label' => 'Number of Tracks']);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults(['data_class' => Contract::class]);
    }
}
```

- [ ] **2. Add `edit` action to `ContractController`**

Route: `#[Route('/{id}/edit', name: 'app_contracts_edit', methods: ['GET', 'POST'])]`

```php
#[Route('/{id}/edit', name: 'app_contracts_edit', methods: ['GET', 'POST'])]
public function edit(Contract $contract, Request $request): Response {
    $company = $this->getUser()->getCompany();
    if ($contract->getCompany() !== $company) {
        throw $this->createAccessDeniedException();
    }
    $form = $this->createForm(ContractEditFormType::class, $contract);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $this->em->flush();
        $this->addFlash('success', 'Contract updated.');
        return $this->redirectToRoute('app_contracts_show', ['id' => $contract->getId()]);
    }
    return $this->render('contract/edit.html.twig', [
        'contract' => $contract,
        'form'     => $form,
    ]);
}
```

Also add `use App\Form\ContractEditFormType;` to the imports.

- [ ] **3. Create `templates/contract/edit.html.twig`**

Bootstrap form with a back link to the show page.

- [ ] **4. Add Edit button to `templates/contract/show.html.twig`**

Next to the "Add Log Entry" button (or in a dedicated actions row).
