<?php
namespace App\Form;

use App\Entity\Contract;
use App\Enum\CommandRights;
use App\Enum\ContractType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('numberOfTracks', IntegerType::class, ['label' => 'Number of Tracks'])
            ->add('planet', TextType::class, ['required' => false])
            ->add('intensity', TextType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults(['data_class' => Contract::class]);
    }
}
