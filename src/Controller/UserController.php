<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Email;
use App\Entity\Phone;
use App\Entity\Booking;
use App\Entity\Parking;
use App\Types\DataStatus;
use App\Entity\CreditCard;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use App\Repository\BookingRepository;
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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/user', name: 'api_user_')]
#[OA\Tag(name: 'User')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class UserController extends AbstractController
{
    private const USER_NOT_FOUND = ['error' => 'User not found'];
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private BookingRepository $bookingRepository;
    private TokenStorageInterface $tokenStorage;
    private const UNAUTHORIZED_ACTION = "You are not allowed to do this action.";

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, BookingRepository $bookingRepository, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->bookingRepository = $bookingRepository;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
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
        $jsonUser = $serializerInterface->serialize($user, 'json', ["groups" => ["user", "stats"]]);
        return new JsonResponse($jsonUser, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/me', name: 'getMe', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: User::class))]
    /**
     * Get a specific user by UUID
     */
    public function getMe(string $uuid, SerializerInterface $serializerInterface): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jsonUser = $serializerInterface->serialize($currentUser, 'json', ["groups" => ["user", "stats"]]);
        return new JsonResponse($jsonUser, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["username" => "example", "profilePicture" => "example", "email" => "test@test.com", "phone" => "+33612345678"]))]
    /**
     * Update a specific user by UUID
     */
    public function update(string $uuid, Request $request, SerializerInterface $serializerInterface, ValidatorInterface $validator): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

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

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 409, description: 'User cannot be deleted at the moment')]
    /**
     * Delete a specific user by UUID
     */
    public function delete(string $id, TagAwareCacheInterface $cache): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        /** @var ?User $user */
        $user = $this->userRepository->findOneBy(['id' => $id]);
        if ($user === null) {
            return new JsonResponse(self::USER_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }

        // Check for active bookings for the user
        $activeUserBookings = $this->bookingRepository->count([
            'client' => $user,
            'status' => 'active',
        ]);

        if ($activeUserBookings > 0) {
            return new JsonResponse(['message' => "You have active bookings. You cannot delete your account."], JsonResponse::HTTP_CONFLICT);
        }

        // Check for active bookings for the user's parkings
        $activeParkingBookings = $this->bookingRepository->findActiveBookingsForUserParkings($user);
        if (!empty($activeParkingBookings)) {
            return new JsonResponse(['message' => "You have active bookings for your parkings. You cannot delete your account."], JsonResponse::HTTP_CONFLICT);
        }

        // Soft delete
        $user->setDataStatus(DataStatus::DELETED);
        $user->setUpdatedAt(new \DateTime());
        $user->setEmail(new Email('anonymous@anonymous.com'));
        $user->setPhone(new Phone('0000000000'));
        $user->setProfilePicture('https://placehold.co/400');
        $user->setUsername('anonymous');


        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Invalidate cache
        $cache->invalidateTags(["User"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/me/bookings', name: 'getUserBookings', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: Booking::class))
        )
    )]
    #[OA\Response(response: 403, description: 'Forbidden')]
    /**
     * Get all bookings of a specific user by UUID
     */
    public function getUserBookings(string $uuid, Request $request, SerializerInterface $serializerInterface): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $bookings = $currentUser->getBookings()->toArray();
        $jsonBookings = $serializerInterface->serialize($bookings, 'json', ["groups" => ["user_booking", "stats"]]);
        return new JsonResponse($jsonBookings, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/me/parkings', name: 'getUserParkings', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: Parking::class))
        )
    )]
    /**
     * Get all parkings of a me
     */
    public function getUserParkings(string $uuid, SerializerInterface $serializerInterface): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $parkings = $currentUser->getParkings()->toArray();
        $jsonParkings = $serializerInterface->serialize($parkings, 'json', ["groups" => ["parking", "stats"]]);
        return new JsonResponse($jsonParkings, JsonResponse::HTTP_OK, [], true);
    }


    #[Route('/me/credit-cards', name: 'getUserCreditCards', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: CreditCard::class))
        )
    )]
    #[OA\Response(response: 403, description: 'Forbidden')]
    /**
     * Get all credit cards of a specific user by UUID
     */
    public function getUserCreditCards(string $uuid, SerializerInterface $serializerInterface): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $creditCards = $currentUser->getCreditCards()->toArray();
        $jsonCreditCards = $serializerInterface->serialize($creditCards, 'json', ["groups" => ["user", "stats"]]);
        return new JsonResponse($jsonCreditCards, JsonResponse::HTTP_OK, [], true);
    }
}
