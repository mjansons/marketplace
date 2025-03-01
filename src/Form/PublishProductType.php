<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublishProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('durationWeeks', ChoiceType::class, [
            'label' => 'Ad Duration (in weeks)',
            'choices' => [
                '1 Week' => 1,
                '2 Weeks' => 2,
                '3 Weeks' => 3,
                '4 Weeks' => 4,
            ],
            'placeholder' => 'Select duration',
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // No data_class here since it's just a simple form
        $resolver->setDefaults([]);
    }
}
