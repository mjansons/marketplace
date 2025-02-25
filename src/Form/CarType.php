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

class CarType extends BaseProductType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

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
                'attr' => ['min' => 0]
            ])
            ->add('run', IntegerType::class, [
                'label' => 'Run (km)',
                'attr'  => ['min' => 0],
            ])
        ;

        // to bypass the error, when models are initially empty and after are loaded with JS.
        //
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $formData = $event->getData();
            $form = $event->getForm();

            $brand = $formData['brand'] ?? null;

            if ($brand) {
                $models = CarData::getModelsByBrand($brand);

                $form->add('model', ChoiceType::class, [
                    'label'       => 'Model',
                    'choices'     => array_combine($models, $models),
                    'placeholder' => 'Choose a model',
                    'required'    => true,
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