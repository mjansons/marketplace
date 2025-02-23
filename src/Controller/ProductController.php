<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Computer;
use App\Entity\Phone;
use App\Form\CarType;
use App\Form\ChooseProductType;
use App\Form\ComputerType;
use App\Form\PhoneType;
use App\Service\CarData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProductController extends AbstractController
{
    #[Route('/product/new', name: 'app_product_new')]
    #[isGranted('ROLE_USER')]
    public function chooseType(Request $request): Response
    {
        // Step 1: show user a simple form to pick product type
        $chooseForm = $this->createForm(ChooseProductType::class);
        $chooseForm->handleRequest($request);

        if ($chooseForm->isSubmitted() && $chooseForm->isValid()) {
            $type = $chooseForm->get('type')->getData();

            return $this->redirectToRoute('app_product_new_type', [
                'type' => $type,
            ]);
        }

        return $this->render('product/choose_type.html.twig', [
            'chooseForm' => $chooseForm->createView(),
        ]);
    }

    #[Route('/product/new/{type}', name: 'app_product_new_type')]
    #[isGranted('ROLE_USER')]
    public function newType(string $type, Request $request, EntityManagerInterface $em): Response
    {
        // Step 2: build the correct entity and form
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
            // Set the current user (in BaseProduct)
            $product->setUser($this->getUser());

            // Persist to DB
            $em->persist($product);
            $em->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('product/new_type.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }
    #[Route('/get-models', name: 'get_car_models', methods: ['POST'])]
    #[isGranted('ROLE_USER')]
    public function getCarModels(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $brand = $data['brand'] ?? null;

        if (!$brand || !array_key_exists($brand, CarData::getCarBrands())) {
            return new JsonResponse(['error' => 'Invalid brand: ' . $brand], 400);
        }

        return new JsonResponse(CarData::getModelsByBrand($brand));
    }

}

