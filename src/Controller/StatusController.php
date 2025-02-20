<?php

namespace App\Controller;

use App\Entity\Status;
use App\Repository\StatusRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/status')]
#[OA\Tag(name: 'Status')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class StatusController extends AbstractController
{
    #[Route('s', name: 'app_status', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Status::class))]
    public function index(StatusRepository $statusRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $status = $statusRepository->findAll();
        $jsonStatus = $serializerInterface->serialize($status, 'json');

        return new JsonResponse($jsonStatus, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'status_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Status::class))]
    public function get(Status $status, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonStatus = $serializerInterface->serialize($status, 'json');

        return new JsonResponse($jsonStatus, JsonResponse::HTTP_OK, [], true);
    }
}
