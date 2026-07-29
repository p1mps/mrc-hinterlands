<?php
namespace App\Form;

use App\Entity\Contract;
use App\Entity\MercenaryCompany;
use App\Enum\CommandRights;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractEditFormType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('company', EntityType::class, [
                'class'        => MercenaryCompany::class,
                'choice_label' => 'name',
                'label'        => 'Claiming Player',
                'required'     => false,
                'placeholder'  => '— Unassigned —',
            ])
            ->add('status', EnumType::class, ['class' => ContractStatus::class, 'label' => 'Contract Status'])
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
            ->add('numberOfTracks', IntegerType::class, ['label' => 'Number of Tracks'])
            ->add('name', TextType::class, ['label' => 'Contract Name', 'required' => false])
            ->add('planet', TextType::class, ['required' => false])
            ->add('intensity', TextType::class, ['required' => false])
            ->add('description', TextareaType::class, ['required' => false, 'attr' => ['rows' => 10]]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults(['data_class' => Contract::class]);
    }
}
