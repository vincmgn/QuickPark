<?php

namespace App\Controller;

use App\Entity\Price;
use App\Repository\PriceRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/price', name: 'api_price_')]
#[OA\Tag(name: 'Price')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class PriceController extends AbstractController
{
    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Price::class))]
    /**
     * Get all prices
     */
    public function index(PriceRepository $priceRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $price = $priceRepository->findAll();
        $jsonPrice = $serializerInterface->serialize($price, 'json');

        return new JsonResponse($jsonPrice, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Price::class))]
    /**
     * Get a specific price by ID
     */
    public function get(Price $price, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPrice = $serializerInterface->serialize($price, 'json');

        return new JsonResponse($jsonPrice, JsonResponse::HTTP_OK, [], true);
    }
}
