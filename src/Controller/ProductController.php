<?php

namespace App\Controller;

use App\Entity\BaseProduct;
use App\Entity\Car;
use App\Entity\Computer;
use App\Entity\Phone;
use App\Form\CarType;
use App\Form\ChooseProductType;
use App\Form\ComputerType;
use App\Form\PhoneType;
use App\Message\ExpireAdMessage;
use App\Service\CarData;
use App\Service\ProductImageHandler;
use App\Service\ProductWorkflowService;
use DateMalformedStringException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\Registry;

class ProductController extends AbstractController
{

    #[IsGranted('ROLE_USER')]
    #[Route('/product/new', name: 'app_product_new')]
    public function chooseType(Request $request): Response
    {
        // Step 1: show user a simple form to pick product type
        $chooseForm = $this->createForm(ChooseProductType::class);
        $chooseForm->handleRequest($request);

        if ($chooseForm->isSubmitted() && $chooseForm->isValid()) {
            $type = $chooseForm->get('type')->getData();
            return $this->redirectToRoute('app_product_new_type', ['type' => $type]);
        }

        return $this->render('product/choose_type.html.twig', [
            'chooseForm' => $chooseForm->createView(),
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/product/new/{type}', name: 'app_product_new_type')]
    public function newType(
        string $type,
        Request $request,
        EntityManagerInterface $em,
        ProductImageHandler $imageHandler,
        Registry $workflowRegistry
    ): Response {
        switch ($type) {
            case 'car':
                $product = new Car();
                $formClass = CarType::class;
                break;
            case 'computer':
                $product = new Computer();
                $formClass = ComputerType::class;
                break;
            case 'phone':
                $product = new Phone();
                $formClass = PhoneType::class;
                break;
            default:
                throw $this->createNotFoundException('Invalid product type');
        }

        $workflow = $workflowRegistry->get($product);
        $statusChoices = array_keys($workflow->getDefinition()->getPlaces());

        // Create form with the status_choices option
        $form = $this->createForm($formClass, $product, [
            'status_choices' => $statusChoices,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUser($this->getUser());

            // Process image uploads
            $uploadedFiles = $form->get('imageFiles')->getData();
            $imagePaths = $imageHandler->processUploads($uploadedFiles);
            if (!empty($imagePaths)) {
                $product->setImagePaths($imagePaths);
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created successfully.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/get-models', name: 'get_car_models', methods: ['POST'])]
    public function getCarModels(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $brand = $data['brand'] ?? null;

        if (!$brand || !array_key_exists($brand, CarData::getCarBrands())) {
            return new JsonResponse(['error' => 'Invalid brand: ' . $brand], 400);
        }

        return new JsonResponse(CarData::getModelsByBrand($brand));
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/product/edit/{id}', name: 'app_product_edit')]
    public function editProduct(
        int $id,
        EntityManagerInterface $em,
        Request $request,
        ProductImageHandler $imageHandler,
        ProductWorkflowService $workflowService,
        Registry $workflowRegistry
    ): Response {
        $product = $em->getRepository(BaseProduct::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        if ($this->getUser() !== $product->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($product instanceof Car) {
            $type = 'car';
            $formClass = CarType::class;
        } elseif ($product instanceof Computer) {
            $type = 'computer';
            $formClass = ComputerType::class;
        } elseif ($product instanceof Phone) {
            $type = 'phone';
            $formClass = PhoneType::class;
        } else {
            throw new \LogicException('Unknown product type');
        }


        $workflow = $workflowRegistry->get($product);
        $statusChoices = array_keys($workflow->getDefinition()->getPlaces());

        $form = $this->createForm($formClass, $product, [
            'status_choices' => $statusChoices,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Process image uploads/removals
            $existingImages = $product->getImagePaths() ?? [];
            $uploadedFiles = $form->get('imageFiles')->getData();
            $existingImages = $imageHandler->processUploads($uploadedFiles, $existingImages);

            $removedImagesJson = $request->request->get('removedImages');
            $removedImages = $removedImagesJson ? json_decode($removedImagesJson, true) : [];
            $existingImages = $imageHandler->processRemovals($removedImages, $existingImages);

            $product->setImagePaths($existingImages);

            if ($product->getStatus() === "published") {
                $workflowService->applyTransition($product, 'draft_from_published');
            }

            $em->flush();
            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('product/edit.html.twig', [
            'form'    => $form->createView(),
            'type'    => $type,
            'product' => $product,
        ]);
    }

    /**
     * @throws DateMalformedStringException
     * @throws ExceptionInterface
     */
    #[Route('/product/publish/{id}', name: 'app_product_publish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publishProduct(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ProductWorkflowService $workflowService,
        MessageBusInterface $bus
    ): Response {
        $product = $em->getRepository(BaseProduct::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        if ($product->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not allowed to publish this product.');
        }

        $durationWeeks = (int) $request->request->get('durationWeeks');
        if (!in_array($durationWeeks, [1, 2, 3, 4])) {
            $this->addFlash('error', 'Invalid duration selected.');
            return $this->redirectToRoute('app_dashboard');
        }

        $now = new DateTime();
        $expiryDate = (clone $now)->modify('+' . ($durationWeeks * 7) . ' days');
        $product->setPublishDate($now);
        $product->setExpiryDate($expiryDate);

        if($product->getStatus() === 'draft') {
            $workflowService->applyTransition($product, 'publish');
        }elseif($product->getStatus() === 'expired') {
            $workflowService->applyTransition($product, 're_publish');
        } else {
            $this->addFlash('error', 'This product is already published.');
            return $this->redirectToRoute('app_dashboard');
        }

        $delayInSeconds = $expiryDate->getTimestamp() - time();
        $delayMs = $delayInSeconds * 1000;
        $bus->dispatch(new ExpireAdMessage($product->getId()), [
            new DelayStamp($delayMs)
        ]);


        $em->flush();
        $this->addFlash('success', 'Product published successfully.');
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/product/unpublish/{id}', name: 'app_product_unpublish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unpublishProduct(
        int $id,
        EntityManagerInterface $em,
        ProductWorkflowService $workflowService
    ): Response {
        $product = $em->getRepository(BaseProduct::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        if ($product->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not allowed to unpublish this product.');
        }

        $now = new DateTime();
        $product->setExpiryDate($now);

        $workflowService->applyTransition($product, 'draft_from_published');

        $em->flush();
        $this->addFlash('success', 'Product unpublished successfully.');
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/product/delete/{id}', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, BaseProduct $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $product->getUser()) {
            throw $this->createAccessDeniedException('You are not allowed to delete this product.');
        }

        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            // Remove files from the filesystem
            $imagePaths = $product->getImagePaths() ?? [];
            foreach ($imagePaths as $filename) {
                $filePath = $this->getParameter('product_images_directory') . '/' . $filename;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
    }



}
