<?php

namespace App\Controller;

use App\Entity\Parking;
use App\Repository\ParkingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ParkingController extends AbstractController
{
    #[Route('/parking', name: 'app_parking', methods: ['GET'])]
    public function index(ParkingRepository $parkingRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $parking = $parkingRepository->findAll();
        $jsonParking = $serializerInterface->serialize($parking, 'json');

        return new JsonResponse($jsonParking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/parking/{id}', name: 'parking_get', methods: ['GET'])]
    public function get(Parking $email, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonParking = $serializerInterface->serialize($email, 'json');

        return new JsonResponse($jsonParking, JsonResponse::HTTP_OK, [], true);
    }
}
