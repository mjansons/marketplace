<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield EmailField::new('email');

        yield ChoiceField::new('roles')
            ->setChoices([
                'User' => 'ROLE_USER',
                'Admin' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderExpanded();

        if (in_array($pageName, ['new', 'edit'])) {
            yield TextField::new('plainPassword', 'Password')
                ->setFormTypeOption('mapped', false)
                ->onlyOnForms()
                ->setRequired($pageName === 'new');

            yield TextField::new('repeatPassword', 'Repeat Password')
                ->setFormTypeOption('mapped', false)
                ->onlyOnForms()
                ->setRequired($pageName === 'new');
        }
    }

    public function createEntity(string $entityFqcn): User
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        return $user;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $this->addPasswordEventListener($builder);

        return $builder;
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $this->addPasswordEventListener($builder);

        return $builder;
    }

    private function addPasswordEventListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var PasswordAuthenticatedUserInterface $user */
            $user = $event->getData();
            $form = $event->getForm();

            $plainPassword = $form->get('plainPassword')->getData();
            $repeatPassword = $form->get('repeatPassword')->getData();

            if ($plainPassword || $repeatPassword) {
                if ($plainPassword !== $repeatPassword) {
                    $form->get('repeatPassword')->addError(new FormError('Passwords do not match.'));
                } else {
                    $encodedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($encodedPassword);
                }
            }
        });
    }
}
