<?php

namespace App\Form;

use App\Entity\Computer;
use App\Service\ProductConstants;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComputerType extends BaseProductType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Base fields (title, description, price)
        parent::buildForm($builder, $options);

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
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => Computer::class,
        ]);
    }
}
