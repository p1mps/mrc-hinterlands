<?php

namespace App\Form;

use App\Entity\ContractLogEntry;
use App\Entity\SalvagedMech;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
                'label' => 'BV',
                'required' => true
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
