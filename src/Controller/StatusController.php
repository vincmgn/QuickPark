<?php

namespace App\Controller;

use App\Entity\Status;
use OpenApi\Attributes as OA;
use App\Repository\StatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
            return $serializerInterface->serialize($status, 'json', [
                'groups' => ['status']
            ]);
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
        $jsonStatus = $serializerInterface->serialize($status, 'json', [
            'groups' => ['status']
        ]);
        return new JsonResponse($jsonStatus, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: Status::class))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["name" => "example"]))]
    /**
     * Add a new status
     */
    public function new(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManager, ValidatorInterface $validator, TagAwareCacheInterface $cache, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $status = $serializerInterface->deserialize($request->getContent(), Status::class, 'json');
        $errors = $validator->validate($status);
        if (count($errors) > 0) {
            return new JsonResponse($errors, JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($status);
        $entityManager->flush();

        $cache->invalidateTags(["Status"]);

        $jsonStatus = $serializerInterface->serialize($status, 'json', [
            'groups' => ['status']
        ]);
        $status = $urlGenerator->generate('api_status_get', ['id' => $status->getId()]);

        return new JsonResponse($jsonStatus, JsonResponse::HTTP_CREATED, ['Location' => $status], true);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["name" => "example"]))]
    /**
     * Update an existing status by ID
     */
    public function update(Status $status, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManager, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $status = $serializerInterface->deserialize($request->getContent(), Status::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $status]);
        $errors = $validator->validate($status);
        if (count($errors) > 0) {
            return new JsonResponse($errors, JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->flush();

        $cache->invalidateTags(["Status"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    /**
     * Delete a specific status by ID
     */
    public function delete(Status $status, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManager->remove($status);
        $entityManager->flush();

        $cache->invalidateTags(["Status"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
