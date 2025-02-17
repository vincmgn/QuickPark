<?php

namespace App\Controller;

use App\Entity\Status;
use App\Repository\StatusRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class StatusController extends AbstractController
{
    #[Route('/status', name: 'app_status', methods: ['GET'])]
    public function index(StatusRepository $statusRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $status = $statusRepository->findAll();
        $jsonStatus = $serializerInterface->serialize($status, 'json');

        return new JsonResponse($jsonStatus, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/status/{id}', name: 'status_get', methods: ['GET'])]
    public function get(Status $status, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonStatus = $serializerInterface->serialize($status, 'json');

        return new JsonResponse($jsonStatus, JsonResponse::HTTP_OK, [], true);
    }
}
