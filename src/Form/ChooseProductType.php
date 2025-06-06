<?php

// src/Form/ChooseTypeForm.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ChooseProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Car' => 'car',
                    'Computer' => 'computer',
                    'Phone' => 'phone',
                    'Camera' => 'camera',
                ],
                'placeholder' => 'Choose product type',
            ]);
    }
}
