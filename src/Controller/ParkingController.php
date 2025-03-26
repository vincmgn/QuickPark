<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Parking;
use OpenApi\Attributes as OA;
use App\Repository\ParkingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/parking', name: 'api_parking_')]
#[OA\Tag(name: 'Parking')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class ParkingController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private const UNAUTHORIZED_ACTION = "You are not allowed to do this action.";
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Parking::class))]
    /**
     * Get all parkings
     */
    public function index(
        ParkingRepository $parkingRepository,
        SerializerInterface $serializerInterface,
        Request $request,
        PaginatorInterface $paginator
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = 12;

        $query = $parkingRepository->findEnabledParkingsQuery();
        $pagination = $paginator->paginate(
            $query,
            $page,
            $limit
        );

        $jsonParkings = $serializerInterface->serialize($pagination->getItems(), 'json', ['groups' => ['parking']]);
        $totalItems = $pagination->getTotalItemCount();
        $response = [
            'data' => json_decode($jsonParkings),
            'current_page' => $page,
            'total_pages' => $totalItems > 0 ? ceil($totalItems / $limit) : 1,
            'total_items' => $totalItems
        ];

        $jsonResponse = json_encode($response);
        return new JsonResponse($jsonResponse, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Parking::class))]
    /**
     * Get a specific parking by ID
     */
    public function get(Parking $parking, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonParking = $serializerInterface->serialize($parking, 'json', ['groups' => ['parking']]);

        return new JsonResponse($jsonParking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: Parking::class))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["name" => "Parking name", "isEnabled" => true, "description" => "Parking description", "owner_id" => 1, "location" => ["latitude" => 0.0, "longitude" => 0.0]]))]
    /**
     * Add a new parking
     */
    public function new(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        /** @var ?User $currentUser */

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $currentUser = $token->getUser();

        $parking = new Parking();
        $parking->setOwner($currentUser);

        $parking->setIsEnabled($data['isEnabled']);
        $parking->setName($data['name']);
        $parking->setDescription($data['description']);


        $latitude = $data['location']['latitude'];
        $longitude = $data['location']['longitude'];
        $location = new Point($latitude, $longitude);
        $parking->setLocation($location);

        $parking->setCreatedAt(new \DateTimeImmutable());
        $parking->setUpdatedAt(new \DateTime());

        $errors = $validator->validate($parking);
        if ($errors->count() > 0) {
            return new JsonResponse(['message' => 'Validation error'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $entityManagerInterface->persist($parking);
        $entityManagerInterface->flush();

        $cache->invalidateTags(['Parking']);

        $jsonParking = $serializerInterface->serialize($parking, 'json', ['groups' => ['parking']]);
        $location = $urlGenerator->generate('api_parking_get', ['id' => $parking->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonParking, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Parking::class))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["name" => "Parking name", "isEnabled" => true, "description" => "Parking description", "location" => ["latitude" => 0.0, "longitude" => 0.0]]))]
    /**
     * Edit a parking
     */
    public function edit(Parking $parking, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        /** @var ?User $currentUser */
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $parking->setIsEnabled($data['isEnabled']);
        $parking->setName($data['name']);
        $parking->setDescription($data['description']);

        $latitude = $data['location']['latitude'];
        $longitude = $data['location']['longitude'];
        $location = new Point($latitude, $longitude);
        $parking->setLocation($location);

        $parking->setUpdatedAt(new \DateTime());

        $errors = $validator->validate($parking);
        if ($errors->count() > 0) {
            return new JsonResponse(['message' => 'Validation error'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $entityManagerInterface->flush();

        $cache->invalidateTags(['Parking']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    /**
     * Delete a parking
     */
    public function delete(Parking $parking, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManagerInterface->remove($parking);
        $entityManagerInterface->flush();

        $cache->invalidateTags(['Parking']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
