<?php
namespace App\Form;

use App\Entity\ContractLogEntry;
use App\Enum\ContractLogEntryType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractLogEntryEditFormType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('month', IntegerType::class)
            ->add('entryType', EnumType::class, ['class' => ContractLogEntryType::class, 'label' => 'Entry Type'])
            ->add('description', TextareaType::class, ['attr' => ['rows' => 4]]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults(['data_class' => ContractLogEntry::class]);
    }
}
