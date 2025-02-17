<?php

namespace App\Controller;

use App\Entity\Phone;
use App\Repository\PhoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class PhoneController extends AbstractController
{
    #[Route('/phone', name: 'app_phone', methods: ['GET'])]
    public function index(PhoneRepository $phoneRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $phone = $phoneRepository->findAll();
        $jsonPhone = $serializerInterface->serialize($phone, 'json');

        return new JsonResponse($jsonPhone, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/phone/{id}', name: 'phone_get', methods: ['GET'])]
    public function get(Phone $phone, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPhone = $serializerInterface->serialize($phone, 'json');

        return new JsonResponse($jsonPhone, JsonResponse::HTTP_OK, [], true);
    }
}
