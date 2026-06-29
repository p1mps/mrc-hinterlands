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
            ->add('name', TextType::class, [
                'label' => 'Mech Name/Designation'
            ])
            ->add('model', TextType::class, [
                'label' => 'Model (e.g., LRM-5)',
                'required' => false
            ])
            ->add('tonnage', IntegerType::class, [
                'label' => 'Tonnage',
                'required' => false
            ])
            ->add('configuration', TextType::class, [
                'label' => 'Configuration Notes',
                'required' => false,
                'attr' => ['rows' => 5]
            ])
            ->add('sourceLogEntry', EntityType::class, [
                'class' => ContractLogEntry::class,
                'choice_label' => function(ContractLogEntry $entry) {
                    return sprintf('%s - %s', $entry->getContract()->getId(), $entry->getDescription());
                },
                'label' => 'Source Log Entry',
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
