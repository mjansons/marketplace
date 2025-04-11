<?php

namespace App\Controller;

use App\Entity\Camera;
use App\Repository\CameraRepository;
use App\Service\ProductConstants;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/camera')]
final class CameraController extends AbstractController
{

    #[Route('/', name: 'app_camera_index', methods: ['GET'])]
    public function index(Request $request, CameraRepository $cameraRepository): Response
    {
        $filters = [
            'brand'            => $request->query->get('brand'),
            'model'            => $request->query->get('model'),
            'memory'           => $request->query->get('memory'),
            'productCondition' => $request->query->get('productCondition'),
            'minPrice'         => $request->query->get('minPrice'),
            'maxPrice'         => $request->query->get('maxPrice'),
            'sort'             => $request->query->get('sort', 'id'),
            'direction'        => $request->query->get('direction', 'DESC'),
        ];

        $page = max(1, (int)$request->query->get('page', 1));
        $cameras = $cameraRepository->searchCameras($filters, $page);

        return $this->render('camera/index.html.twig', [
            'cameras'  => $cameras,
            'filters' => $filters,
            'page'    => $page,
            'productConditions' => ProductConstants::getConditions(),
        ]);
    }

    #[Route('/{id}', name: 'app_camera_show', methods: ['GET'])]
    public function show(Camera $camera): Response
    {
        return $this->render('camera/show.html.twig', [
            'product' => $camera,
        ]);
    }

}
