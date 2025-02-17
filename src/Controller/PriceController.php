<?php

namespace App\Controller;

use App\Entity\Price;
use App\Repository\PriceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class PriceController extends AbstractController
{
    #[Route('/price', name: 'app_price', methods: ['GET'])]
    public function index(PriceRepository $priceRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $price = $priceRepository->findAll();
        $jsonPrice = $serializerInterface->serialize($price, 'json');

        return new JsonResponse($jsonPrice, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/price/{id}', name: 'price_get', methods: ['GET'])]
    public function get(Price $price, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPrice = $serializerInterface->serialize($price, 'json');

        return new JsonResponse($jsonPrice, JsonResponse::HTTP_OK, [], true);
    }
}
