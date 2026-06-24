<?php
namespace App\Form;

use App\Enum\CombatPayTier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostTrackFormType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('combatPayTier', EnumType::class, ['class' => CombatPayTier::class, 'label' => 'Combat Pay Tier'])
            ->add('salvageClaimed', CheckboxType::class, ['required' => false, 'label' => 'Salvage Claimed?']);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults(['data_class' => null]);
    }
}
