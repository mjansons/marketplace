<?php

namespace App\Form;

use App\Entity\Computer;
use App\Service\ProductConstants;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComputerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand')
            ->add('model')
            ->add('ram')
            ->add('storage')
            ->add('productCondition', ChoiceType::class, [
                'label' => 'Condition',
                'choices' => ProductConstants::getConditions(),
                'placeholder' => 'Choose condition',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Computer::class,
        ]);
    }
}
