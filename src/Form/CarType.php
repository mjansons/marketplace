<?php

namespace App\Form;

use App\Entity\Car;
use App\Service\CarData;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Event\PreSubmitEvent;

class CarType extends BaseProductType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 1) Base fields (title, description, price, etc.)
        parent::buildForm($builder, $options);

        // 2) Car-specific fields
        $builder
            ->add('brand', ChoiceType::class, [
                'label'       => 'Brand',
                'choices'     => CarData::getCarBrands(),
                'placeholder' => 'Choose a brand',
                'required'    => true,
                'attr'        => ['class' => 'brand-selector'],
            ])
            ->add('model', ChoiceType::class, [
                'label'       => 'Model',
                'placeholder' => 'Choose a model',
                // Initially empty. We'll fill it dynamically in PRE_SUBMIT
                'choices'     => [],
                'required'    => true,
                'attr'        => ['class' => 'model-selector'],
            ])
            ->add('year', ChoiceType::class, [
                'label'       => 'Year',
                'choices'     => CarData::getYear(),
                'placeholder' => 'Choose a year',
                'required'    => true,
            ])
            ->add('volume', ChoiceType::class, [
                'label'       => 'Volume (Liters)',
                'choices'     => CarData::getVolume(),
                'placeholder' => 'Select volume',
                'required'    => true,
            ])
            ->add('run', IntegerType::class, [
                'label' => 'Run (km)',
                'attr'  => ['min' => 0],
            ])
        ;

        // 3) Add a PRE_SUBMIT listener to dynamically set model choices
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $formData = $event->getData(); // array of all submitted fields
            $form = $event->getForm();

            // The user selected brand
            $brand = $formData['brand'] ?? null;

            if ($brand) {
                // 4) Generate the correct model choices based on the submitted brand
                $models = CarData::getModelsByBrand($brand);

                // 5) Rebuild the 'model' field with these choices
                $form->add('model', ChoiceType::class, [
                    'label'       => 'Model',
                    'choices'     => array_combine($models, $models),
                    'placeholder' => 'Choose a model',
                    'required'    => true,
                    'data'        => $formData['model'] ?? null,
                    'attr'        => ['class' => 'model-selector'],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}