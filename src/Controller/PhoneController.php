<?php

namespace App\Controller;

use App\Entity\Phone;
use App\Form\PhoneType;
use App\Repository\PhoneRepository;
use App\Service\ProductConstants;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/phone')]
final class PhoneController extends AbstractController
{

    #[Route('/', name: 'app_phone_index', methods: ['GET'])]
    public function index(Request $request, PhoneRepository $phoneRepository): Response
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
        $phones = $phoneRepository->searchPhones($filters, $page);

        return $this->render('phone/index.html.twig', [
            'phones'  => $phones,
            'filters' => $filters,
            'page'    => $page,
            'productConditions' => ProductConstants::getConditions(),
        ]);
    }

    #[Route('/{id}', name: 'app_phone_show', methods: ['GET'])]
    public function show(Phone $phone): Response
    {
        return $this->render('phone/show.html.twig', [
            'product' => $phone,
        ]);
    }

}
