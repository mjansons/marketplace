<?php

namespace App\Controller;

use App\Entity\Car;
use App\Repository\CarRepository;
use App\Service\CarData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/car')]
final class CarController extends AbstractController
{

    #[Route('/cars/select-brand', name: 'app_car_select_brand', methods: ['GET'])]
    public function selectBrand(CarRepository $carRepository): Response
    {
        $brandCounts = [];
        $counts = $carRepository->countCarsByBrand(); // Fetch brand counts

        foreach ($counts as $row) {
            $brandCounts[$row['brand']] = $row['car_count'];
        }

        return $this->render('car/select_brand.html.twig', [
            'brandCounts' => $brandCounts,
        ]);
    }


    #[Route('/filter/{brand}', name: 'app_car_filter_by_brand', methods: ['GET'])]
    public function filterByBrand(string $brand, Request $request, CarRepository $carRepository): Response
    {

        $brand = mb_strtolower($brand);
        $allBrands = array_map('mb_strtolower', array_keys(CarData::getCarBrands()));

        if (!in_array($brand, $allBrands, true)) {
            $this->addFlash('error', 'Invalid brand selected.');
            return $this->redirectToRoute('app_car_select_brand');
        }

        // Get available models, years, and volumes
        $models = CarData::getModelsByBrand(ucfirst($brand));
        $years = CarData::getYear();
        $volumes = CarData::getVolume();

        // Retrieve filters from request
        $filters = [
            'brand' => ucfirst($brand),
            'model' => $request->query->get('model'),
            'minYear' => $request->query->get('minYear'),
            'maxYear' => $request->query->get('maxYear'),
            'minVolume' => $request->query->get('minVolume'),
            'maxVolume' => $request->query->get('maxVolume'),
            'minRun' => $request->query->get('minRun'),
            'maxRun' => $request->query->get('maxRun'),
            'minPrice' => $request->query->get('minPrice'),
            'maxPrice' => $request->query->get('maxPrice'),
            'sort' => $request->query->get('sort', 'year'),
            'direction' => $request->query->get('direction', 'DESC'),
        ];

        $page = max(1, (int) $request->query->get('page', 1));
        $cars = $carRepository->searchCars($filters, $page);

        return $this->render('car/filter_by_brand.html.twig', [
            'selectedBrand' => ucfirst($brand),
            'models' => $models,
            'years' => $years,
            'volumes' => $volumes,
            'cars' => $cars,
            'filters' => $filters,
            'page' => $page,
        ]);
    }



    #[Route('/{id}', name: 'app_car_show', methods: ['GET'])]
    public function show(Car $car): Response
    {
        return $this->render('car/show.html.twig', [
            'product' => $car,
        ]);
    }

}
