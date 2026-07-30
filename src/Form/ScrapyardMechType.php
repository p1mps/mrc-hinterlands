<?php

namespace App\Form;

use App\Entity\SalvagedMech;
use App\Enum\DamageState;
use App\Enum\TechBase;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScrapyardMechType extends AbstractType
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
            ->add('damageState', EnumType::class, [
                'class' => DamageState::class,
                'label' => 'Damage State',
                'required' => false,
            ])
            ->add('techBase', EnumType::class, [
                'class' => TechBase::class,
                'label' => 'Tech Base',
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
