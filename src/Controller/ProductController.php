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
use App\Service\CarData;
use App\Service\ProductWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{

    #[Route('/product/new', name: 'app_product_new')]
    #[IsGranted('ROLE_USER')]
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

    #[Route('/product/new/{type}', name: 'app_product_new_type')]
    #[IsGranted('ROLE_USER')]
    public function newType(
        string $type,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        switch ($type) {
            case 'car':
                $product = new Car();
                $form = $this->createForm(CarType::class, $product);
                break;
            case 'computer':
                $product = new Computer();
                $form = $this->createForm(ComputerType::class, $product);
                break;
            case 'phone':
                $product = new Phone();
                $form = $this->createForm(PhoneType::class, $product);
                break;
            default:
                throw $this->createNotFoundException('Invalid product type');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUser($this->getUser());

            $imageFiles = $form->get('imageFiles')->getData();
            $imagePaths = [];
            $errorMessages = [];
            foreach ($imageFiles as $index => $imageFile) {
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('product_images_directory'),
                            $newFilename
                        );
                        $imagePaths[] = $newFilename;
                    } catch (FileException $e) {
                        $errorMessages[] = sprintf(
                            'Failed to upload image %d (%s): %s',
                            $index + 1,
                            $imageFile->getClientOriginalName(),
                            $e->getMessage()
                        );
                    }
                }
            }

            foreach ($errorMessages as $errorMessage) {
                $this->addFlash('error', $errorMessage);
            }

            if (!empty($imagePaths)) {
                $product->setImagePaths($imagePaths);
            }

            $em->persist($product);
            $em->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }

    #[Route('/get-models', name: 'get_car_models', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function getCarModels(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $brand = $data['brand'] ?? null;

        if (!$brand || !array_key_exists($brand, CarData::getCarBrands())) {
            return new JsonResponse(['error' => 'Invalid brand: ' . $brand], 400);
        }

        return new JsonResponse(CarData::getModelsByBrand($brand));
    }

    #[Route('/product/edit/{id}', name: 'app_product_edit')]
    #[IsGranted('ROLE_USER')]
    public function editProduct(
        int $id,
        EntityManagerInterface $em,
        Request $request,
        SluggerInterface $slugger,
        ProductWorkflowService $workflowService
    ): Response {
        $product = $em->getRepository(BaseProduct::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        if ($this->getUser() !== $product->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($product instanceof Car) {
            $form = $this->createForm(CarType::class, $product);
            $type = 'car';
        } elseif ($product instanceof Computer) {
            $form = $this->createForm(ComputerType::class, $product);
            $type = 'computer';
        } elseif ($product instanceof Phone) {
            $form = $this->createForm(PhoneType::class, $product);
            $type = 'phone';
        } else {
            throw new \LogicException('Unknown product type');
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the current images from the product
            $existingImages = $product->getImagePaths() ?? [];
            $imageFiles = $form->get('imageFiles')->getData();
            $errorMessages = [];

            // Process new uploads
            foreach ($imageFiles as $index => $imageFile) {
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move($this->getParameter('product_images_directory'), $newFilename);
                        $existingImages[] = $newFilename;
                    } catch (FileException $e) {
                        $errorMessages[] = sprintf(
                            'Failed to upload image %d (%s): %s',
                            $index + 1,
                            $imageFile->getClientOriginalName(),
                            $e->getMessage()
                        );
                    }
                }
            }
            foreach ($errorMessages as $errorMessage) {
                $this->addFlash('error', $errorMessage);
            }

            // Process removed images from hidden field
            $removedImagesJson = $request->request->get('removedImages');
            $removedImages = $removedImagesJson ? json_decode($removedImagesJson, true) : [];
            foreach ($removedImages as $removedFilename) {
                $filePath = $this->getParameter('product_images_directory') . '/' . $removedFilename;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                if (($key = array_search($removedFilename, $existingImages)) !== false) {
                    unset($existingImages[$key]);
                }
            }
            $product->setImagePaths(array_values($existingImages));

            // If the product was published or expired and is being edited,
            // move it back to draft using workflow
            if ($product->getStatus() === 'published') {
                $now = new \DateTime();
                $product->setExpiryDate($now);
                $workflowService->applyTransition($product, 'draft_from_published');
            } elseif ($product->getStatus() === 'expired') {
                $workflowService->applyTransition($product, 'draft_from_expired');
            }

            $em->flush();
            return $this->redirectToRoute('app_dashboard');
        }
        return $this->render('product/edit.html.twig', [
            'form'    => $form->createView(),
            'type'    => $type,
            'product' => $product,
        ]);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/product/publish/{id}', name: 'app_product_publish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publishProduct(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ProductWorkflowService $workflowService
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

        $now = new \DateTime();
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

        $now = new \DateTime();
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
