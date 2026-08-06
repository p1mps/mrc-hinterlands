<?php
namespace App\Form;

use App\Entity\Unit;
use App\Enum\DamageState;
use App\Enum\UnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UnitFormType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('name', TextType::class)
            ->add('chassis', TextType::class)
            ->add('tonnage', IntegerType::class)
            ->add('bv', IntegerType::class, ['label' => 'Base BV'])
            ->add('unitType', EnumType::class, ['class' => UnitType::class])
            ->add('damageState', EnumType::class, ['class' => DamageState::class]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults(['data_class' => Unit::class]);
    }
}
