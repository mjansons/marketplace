<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use ReflectionClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

trait ProductCrudTrait
{
    /**
     * @throws ExceptionInterface
     */
    protected function handleNewEdit(AdminContext $context, object $product, string $formType): Response
    {
        $workflow = $this->workflowRegistry->get($product);
        $statusChoices = array_keys($workflow->getDefinition()->getPlaces());

        $form = $this->createForm($formType, $product, [
            'status_choices' => $statusChoices,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->productFormHandler->handleProductForm(
                $form,
                $context->getRequest(),
                $product,
                $this->getUser()
            );

            $this->addFlash('success', sprintf(
                '%s saved successfully.',
                (new ReflectionClass($product))->getShortName()
            ));

            return new RedirectResponse($this->generateUrl('admin'));
        }

        $template = $context->getCrud()->getCurrentAction() === 'edit'
            ? 'product/edit.html.twig'
            : 'product/new.html.twig';

        return $this->render($template, [
            'form' => $form->createView(),
            'product' => $product,
            'type' => strtolower((new ReflectionClass($product))->getShortName()),
        ]);
    }
}



