<?php

namespace App\Form;

use App\Entity\Dropship;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DropshipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Dropship Name',
            'required' => false,
        ]);
        $builder->add('maxCapacity', IntegerType::class, [
            'label' => 'Maximum Tonnage Capacity',
            'attr' => ['min' => 1],
        ]);
        $builder->add('mekbayCapacity', IntegerType::class, [
            'label' => 'Maximum Mekbay Count',
            'required' => true,
            'attr' => ['min' => 0],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Dropship::class]);
    }
}
