<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Booking;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/user', name: 'api_user_')]
#[OA\Tag(name: 'User')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class UserController extends AbstractController
{
    private const USER_NOT_FOUND = ['error' => 'User not found'];
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/{uuid}', name: 'get', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: User::class))]
    /**
     * Get a specific user by UUID
     */
    public function get(string $uuid, SerializerInterface $serializerInterface): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $uuid]);

        if ($user === null) {
            return new JsonResponse(self::USER_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }
        $jsonUser = $serializerInterface->serialize($user, 'json', ["groups" => ["user"]]);
        return new JsonResponse($jsonUser, JsonResponse::HTTP_OK, [], true);
    }


    #[Route('/{uuid}', name: 'update', methods: ['PUT'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["username" => "example", "profilePicture" => "example", "email" => "test@test.com", "phone" => "+33612345678"]))]
    /**
     * Update a specific user by UUID
     */
    public function update(string $uuid, Request $request, SerializerInterface $serializerInterface, ValidatorInterface $validator): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $uuid]);

        if ($user === null) {
            return new JsonResponse(self::USER_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }
        $updatedUser = $serializerInterface->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
        $errors = $validator->validate($updatedUser);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $this->entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}', name: 'delete', methods: ['DELETE'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(response: 204, description: 'No content')]
    /**
     * Delete a specific user by UUID
     */
    public function delete(string $uuid, TagAwareCacheInterface $cache): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $uuid]);
        if ($user === null) {
            return new JsonResponse(self::USER_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }
        $this->entityManager->remove($user);

        // Invalidate cache
        $cache->invalidateTags(["User"]);
        $this->entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/bookings', name: 'getUserBookings', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    /**
     * Get all bookings of a specific user by UUID
     */
    public function getUserBookings(string $uuid, SerializerInterface $serializerInterface): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $uuid]);

        if ($user === null) {
            return new JsonResponse(self::USER_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }
        /** @var Booking[] $bookings */
        $bookings = $user->getBookings()->toArray();

        $jsonBookings = $serializerInterface->serialize($bookings, 'json', ["groups" => ["user_booking"]]);
        return new JsonResponse($jsonBookings, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{uuid}/parkings', name: 'getUserParkings', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    /**
     * Get all parkings of a specific user by UUID
     */
    public function getUserParkings(string $uuid, SerializerInterface $serializerInterface): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $uuid]);
        if ($user === null) {
            return new JsonResponse(self::USER_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }
        $parkings = $user->getParkings()->toArray();

        $jsonParkings = $serializerInterface->serialize($parkings, 'json', ["groups" => ["parking"]]);
        return new JsonResponse($jsonParkings, JsonResponse::HTTP_OK, [], true);
    }


    #[Route('/{uuid}/credit-cards', name: 'getUserCreditCards', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    /**
     * Get all credit cards of a specific user by UUID
     */
    public function getUserCreditCards(string $uuid, SerializerInterface $serializerInterface): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $uuid]);
        if ($user === null) {
            return new JsonResponse(self::USER_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }
        $creditCards = $user->getCreditCards()->toArray();


        $jsonCreditCards = $serializerInterface->serialize($creditCards, 'json', ["groups" => ["user"]]);
        return new JsonResponse($jsonCreditCards, JsonResponse::HTTP_OK, [], true);
    }
}
