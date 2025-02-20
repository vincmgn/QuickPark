<?php

namespace App\Controller;

use App\Entity\Parking;
use App\Repository\ParkingRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/parking')]
#[OA\Tag(name: 'Parking')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class ParkingController extends AbstractController
{
    #[Route('s', name: 'app_parking', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Parking::class))]
    public function index(ParkingRepository $parkingRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $parking = $parkingRepository->findAll();
        $jsonParking = $serializerInterface->serialize($parking, 'json');

        return new JsonResponse($jsonParking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'parking_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Parking::class))]
    public function get(Parking $parking, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonParking = $serializerInterface->serialize($parking, 'json');

        return new JsonResponse($jsonParking, JsonResponse::HTTP_OK, [], true);
    }
}
