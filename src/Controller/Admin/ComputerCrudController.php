<?php

namespace App\Controller\Admin;

use App\Entity\Computer;
use App\Form\ComputerType;
use App\Service\ProductImageHandler;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Messenger\MessageBusInterface;

class ComputerCrudController extends AbstractCrudController
{
    use ProductCrudTrait;

    public function __construct(
        private readonly ProductImageHandler $imageHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly Registry $workflowRegistry,
        private readonly MessageBusInterface $bus
    ) {}

    public static function getEntityFqcn(): string
    {
        return Computer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplates([
                'crud/new'  => 'product/new.html.twig',
                'crud/edit' => 'product/edit.html.twig',
            ]);
    }

    /**
     * @throws ExceptionInterface
     */
    public function new(AdminContext $context): Response
    {
        return $this->handleNewEdit($context, new Computer(), ComputerType::class);
    }

    /**
     * @throws ExceptionInterface
     */
    public function edit(AdminContext $context): Response
    {
        return $this->handleNewEdit($context, $context->getEntity()->getInstance(), ComputerType::class);
    }
}
