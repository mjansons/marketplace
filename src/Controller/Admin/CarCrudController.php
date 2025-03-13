<?php

namespace App\Controller\Admin;

use App\Entity\Car;
use App\Form\CarType;
use App\Service\ProductFormHandler;
use App\Service\ProductImageHandler;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Messenger\MessageBusInterface;

class CarCrudController extends AbstractCrudController
{
    use ProductCrudTrait;

    public function __construct(
        private readonly ProductImageHandler $imageHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly Registry $workflowRegistry,
        private readonly MessageBusInterface $bus,
        protected ProductFormHandler $productFormHandler
    ) {}

    public static function getEntityFqcn(): string
    {
        return Car::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title')->setSortable(true),
            TextEditorField::new('description')->hideOnIndex(), // Hide in table, show in form
            MoneyField::new('price')->setCurrency('EUR'),
            ChoiceField::new('status')->setChoices([
                'Draft' => 'draft',
                'Published' => 'published',
                'Expired' => 'expired'
            ]),
            DateTimeField::new('publishDate')->setFormat('yyyy-MM-dd'),
            DateTimeField::new('expiryDate')->setFormat('yyyy-MM-dd')->setSortable(true),
            AssociationField::new('user')->setLabel('Owner'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplates([
                'crud/new'  => 'product/new.html.twig',
                'crud/edit' => 'product/edit.html.twig',
            ])
            ->setSearchFields(['title', 'description', 'user.email']);
    }

    /**
     * @throws ExceptionInterface
     */
    public function new(AdminContext $context): Response
    {
        return $this->handleNewEdit($context, new Car(), CarType::class);
    }

    /**
     * @throws ExceptionInterface
     */
    public function edit(AdminContext $context): Response
    {
        return $this->handleNewEdit($context, $context->getEntity()->getInstance(), CarType::class);
    }
}
