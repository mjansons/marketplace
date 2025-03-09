<?php

namespace App\Form;

use App\Entity\BaseProduct;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
        $builder
            ->add('title', TextType::class)
            ->add('description', TextType::class)
            ->add('price', IntegerType::class, [
                'attr' => ['min' => 0]
            ]);


        if ($options['is_admin'] === true) {
            $builder->add('status', ChoiceType::class, [
                'choices'     => array_combine($options['status_choices'], $options['status_choices']),
                'placeholder' => 'Select status',
                'required'    => true,
            ])->add('expiryDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Expiry Date',
            ])->add('user', EntityType::class, [
                    'class' => User::class,
                    'choice_label' => 'email',
                    'placeholder' => 'Select a user',
                    'required' => true,
                    'data' => $options['user'] ?? null
                ]);
        }

        // Common image upload field for all users
        $builder->add('imageFiles', FileType::class, [
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
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG).',
                    ]),
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'     => BaseProduct::class,
            'csrf_protection'=> true,
            'csrf_field_name'=> '_token',
            'csrf_token_id'  => 'product_form',
            'status_choices' => [],
            'is_admin' => false,
            'user' => null,
        ]);
        $resolver->setAllowedTypes('status_choices', ['array']);
        $resolver->setAllowedTypes('is_admin', ['bool']);
        $resolver->setAllowedTypes('user', ['null', User::class]);
    }
}
