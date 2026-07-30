<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\SalvagedMech;
use App\Enum\DamageState;
use App\Enum\TechBase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalvagedMechType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('model', TextType::class, [
                'label' => 'Model',
                'required' => true
            ])
            ->add('tonnage', IntegerType::class, [
                'label' => 'Tonnage',
                'required' => true
            ])
            ->add('bvCost', IntegerType::class, [
                'label' => 'BV Cost',
                'required' => true
            ])
            ->add('contract', EntityType::class, [
                'class' => Contract::class,
                'label' => 'Contract',
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a contract (optional)',
            ])
            ->add('damageState', ChoiceType::class, [
                'label' => 'Damage State',
                'choices' => [
                    'None' => DamageState::None->value,
                    'Armor Only' => DamageState::ArmorOnly->value,
                    'Structural' => DamageState::Structural->value,
                    'Crippled' => DamageState::Crippled->value,
                    'Destroyed' => DamageState::Destroyed->value,
                ],
                'required' => false,
            ])
            ->add('techBase', ChoiceType::class, [
                'label' => 'Tech Base',
                'choices' => [
                    'Inner Sphere' => TechBase::IS->value,
                    'Mixed' => TechBase::Mixed->value,
                    'Clan' => TechBase::Clan->value,
                ],
                'required' => false,
            ])
            ->add('salvageRightsPercent', IntegerType::class, [
                'label' => 'Salvage Rights Percent (null = Exchange/25%, 0 = None)',
                'required' => false,
            ])
            ->add('scrapyard', CheckboxType::class, [
                'label' => 'Scrapyard (half cost, stays Crippled)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SalvagedMech::class,
        ]);
    }
}
