<?php

namespace App\Controller;

use App\Entity\Status;
use OpenApi\Attributes as OA;
use App\Repository\StatusRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/status', name: 'api_status_')]
#[OA\Tag(name: 'Status')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class StatusController extends AbstractController
{
    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Status::class))]
    /**
     * Get all statuses
     */
    public function index(StatusRepository $statusRepository, SerializerInterface $serializerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllStatus";
        $jsonStatus = $cache->get($idCache, function (ItemInterface $item) use ($statusRepository, $serializerInterface) {
            $item->tag("Status");
            $status = $statusRepository->findAll();
            return $serializerInterface->serialize($status, 'json');
        });
        return new JsonResponse($jsonStatus, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Status::class))]
    /**
     * Get a specific status by ID
     */
    public function get(Status $status, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonStatus = $serializerInterface->serialize($status, 'json');
        return new JsonResponse($jsonStatus, JsonResponse::HTTP_OK, [], true);
    }
}
