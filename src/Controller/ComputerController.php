<?php

namespace App\Controller;

use App\Entity\Computer;
use App\Repository\ComputerRepository;
use App\Service\ProductConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/computer')]
final class ComputerController extends AbstractController
{
    #[Route('/', name: 'app_computer_index', methods: ['GET'])]
    public function index(Request $request, ComputerRepository $computerRepository): Response
    {
        // Retrieve filter parameters from query string
        $filters = [
            'brand'            => $request->query->get('brand'),
            'model'            => $request->query->get('model'),
            'ram'              => $request->query->get('ram'),
            'storage'          => $request->query->get('storage'),
            'productCondition' => $request->query->get('productCondition'),
            'minPrice'         => $request->query->get('minPrice'),
            'maxPrice'         => $request->query->get('maxPrice'),
            'sort'             => $request->query->get('sort', 'id'),
            'direction'        => $request->query->get('direction', 'DESC'),
        ];

        $page = max(1, (int)$request->query->get('page', 1));
        $computers = $computerRepository->searchComputers($filters, $page);

        // Optionally, you could calculate total pages with a count query if needed

        return $this->render('computer/index.html.twig', [
            'computers'         => $computers,
            'filters'           => $filters,
            'page'              => $page,
            'productConditions' => ProductConstants::getConditions(), // For condition dropdown
        ]);
    }


    #[Route('/{id}', name: 'app_computer_show', methods: ['GET'])]
    public function show(Computer $computer): Response
    {
        return $this->render('computer/show.html.twig', [
            'product' => $computer,
        ]);
    }

}
