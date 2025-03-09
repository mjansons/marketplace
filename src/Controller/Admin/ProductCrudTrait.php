<?php

namespace App\Controller\Admin;

use App\Message\ExpireAdMessage;
use App\Service\ProductImageHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use ReflectionClass;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\Registry;

/**
 * @property EntityManagerInterface $entityManager
 * @property ProductImageHandler $imageHandler
 * @property Registry $workflowRegistry
 * @property MessageBusInterface $bus
 *
 * @method FormInterface createForm(string $type, $data = null, array $options = [])
 * @method bool isGranted(string $attribute, $subject = null)
 * @method UserInterface|null getUser()
 * @method void addFlash(string $type, string $message)
 * @method Response render(string $view, array $parameters = [], Response $response = null)
 * @method string generateUrl(string $route, array $parameters = [])
 */

trait ProductCrudTrait
{
    /**
     * @throws ExceptionInterface
     */
    protected function handleNewEdit(AdminContext $context, object $product, string $formType): Response
    {
        // Retrieve workflow statuses
        $workflow = $this->workflowRegistry->get($product);
        $statusChoices = array_keys($workflow->getDefinition()->getPlaces());

        // Create form
        $form = $this->createForm($formType, $product, [
            'status_choices' => $statusChoices,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $existingImages = $product->getImagePaths() ?? [];
            $uploadedFiles = $form->get('imageFiles')->getData();
            $existingImages = $this->imageHandler->processUploads($uploadedFiles, $existingImages);

            $removedImagesJson = $context->getRequest()->request->get('removedImages');
            $removedImages = $removedImagesJson ? json_decode($removedImagesJson, true) : [];
            $existingImages = $this->imageHandler->processRemovals($removedImages, $existingImages);
            $product->setImagePaths($existingImages);

            // add to messenger if status in published
            if (
                $product->getStatus() === "published" &&
                $product->getExpiryDate() &&
                ($product->getExpiryDate()->getTimestamp() > time())
            ) {
                $delayMs = ($product->getExpiryDate()->getTimestamp() - time()) * 1000;
                $this->bus->dispatch(new ExpireAdMessage($product->getId()), [
                    new DelayStamp($delayMs)
                ]);

                $product->setPublishDate(new DateTime());

            } elseif (
                $product->getExpiryDate() &&
                ($product->getExpiryDate()->getTimestamp() <= time())
            ) {
                $product->setStatus("expired");
            }

            if (method_exists($product, 'setUser') && !$product->getUser()) {
                $product->setUser($this->getUser());
            }

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('%s saved successfully.', (new ReflectionClass($product))->getShortName()));
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
