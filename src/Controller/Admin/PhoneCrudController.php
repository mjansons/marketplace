<?php

namespace App\Controller\Admin;

use App\Entity\Phone;
use App\Form\PhoneType;
use App\Service\ProductFormHandler;
use App\Service\ProductImageHandler;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Messenger\MessageBusInterface;

class PhoneCrudController extends AbstractCrudController
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
        return Phone::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplates([
                'crud/new'  => 'product/new.html.twig',
                'crud/edit' => 'product/edit.html.twig',
            ])
            ->setSearchFields(['title', 'description', 'user.email', 'model', 'brand']);
    }

    /**
     * @throws ExceptionInterface
     */
    public function new(AdminContext $context): Response
    {
        return $this->handleNewEdit($context, new Phone(), PhoneType::class);
    }

    /**
     * @throws ExceptionInterface
     */
    public function edit(AdminContext $context): Response
    {
        return $this->handleNewEdit($context, $context->getEntity()->getInstance(), PhoneType::class);
    }
}
