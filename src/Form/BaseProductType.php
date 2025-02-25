<?php
// src/Form/BaseProductType.php

namespace App\Form;

use App\Entity\BaseProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;

class BaseProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Common fields for all products
        $builder
            ->add('title', TextType::class)
            ->add('description', TextType::class)
            ->add('price', IntegerType::class, [
                'attr' => ['min' => 0]
            ])

            ->add('imageFiles', FileType::class, [
                'label' => 'Upload Images',
                'mapped' => false,
                'multiple' => true,
                'required' => false,
                'attr' => ['accept' => 'image/*'],
                'constraints' => [
                    new Count([
                        'max' => 6,
                        'maxMessage' => 'You can upload a maximum of 6 images.',
                    ]),
                    new All([
                        new File([
                            'maxSize' => '1M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                            'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF).',
                        ]),
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BaseProduct::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'product_form',
        ]);
    }
}
