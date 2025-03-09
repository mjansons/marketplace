<?php

namespace App\Form;

use App\Entity\Phone;
use App\Service\ProductConstants;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PhoneType extends BaseProductType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Base fields (title, description, price)
        parent::buildForm($builder, $options);

        $builder
            ->add('brand', TextType::class)
            ->add('model', TextType::class)
            ->add('memory', IntegerType::class)
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
            'data_class' => Phone::class,
        ]);
    }
}