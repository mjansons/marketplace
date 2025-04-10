<?php

namespace App\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use App\Message\ExpireAdMessage;

readonly class ProductFormHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductImageHandler $imageHandler,
        private MessageBusInterface $bus
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function handleProductForm(
        FormInterface $form,
        Request $request,
        object $product,
        UserInterface $user,
        bool $isEdit = false
    ): void {
        $existingImages = $product->getImagePaths() ?? [];
        $uploadedFiles = $form->get('imageFiles')->getData();
        $existingImages = $this->imageHandler->processUploads($uploadedFiles, $existingImages);

        $removedImagesJson = $request->request->get('removedImages');
        $removedImages = $removedImagesJson ? json_decode($removedImagesJson, true) : [];
        $existingImages = $this->imageHandler->processRemovals($removedImages, $existingImages);

        $product->setImagePaths($existingImages);

        if ($isEdit && $product->getStatus() === 'published') {
            $product->setStatus('draft');
        }

        if (!$product->getUser()) {
            $product->setUser($user);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // admin related logic after flush
        if ($product->getStatus() === "published") {
            $expiryDate = $product->getExpiryDate();

            if (!$expiryDate) {
                $product->setStatus('draft');
            } elseif ($expiryDate->getTimestamp() <= time()) {
                $product->setStatus('expired');
            } else {
                if (!$product->getPublishDate()) {
                    $product->setPublishDate(new DateTime());
                }

                $delayMs = ($expiryDate->getTimestamp() - time()) * 1000;
                $this->bus->dispatch(new ExpireAdMessage($product->getId()), [
                    new DelayStamp($delayMs)
                ]);
            }

            $this->entityManager->flush();
        }
    }
}
