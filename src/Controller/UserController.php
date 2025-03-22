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
    private const UNAUTHORIZED_DELETE = "You are not allowed to do this action.";

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, BookingRepository $bookingRepository, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->bookingRepository = $bookingRepository;
        $this->tokenStorage = $tokenStorage;
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
        $jsonUser = $serializerInterface->serialize($user, 'json', ["groups" => ["user", "stats"]]);
        return new JsonResponse($jsonUser, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{uuid}', name: 'update', methods: ['PATCH'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["username" => "example", "profilePicture" => "example", "email" => "test@test.com", "phone" => "+33612345678"]))]
    /**
     * Update a specific user by UUID
     */
    public function update(string $uuid, Request $request, SerializerInterface $serializerInterface, ValidatorInterface $validator): JsonResponse
    {
        /** @var ?User $currentUser */
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            throw new AccessDeniedException(self::UNAUTHORIZED_DELETE);
        }
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedException(self::UNAUTHORIZED_DELETE);
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

    #[Route('/{uuid}', name: 'delete', methods: ['DELETE'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 409, description: 'User cannot be deleted at the moment')]
    /**
     * Delete a specific user by UUID
     */
    public function delete(string $uuid, TagAwareCacheInterface $cache): JsonResponse
    {
        /** @var ?User $currentUser */
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            throw new AccessDeniedException(self::UNAUTHORIZED_DELETE);
        }
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedException(self::UNAUTHORIZED_DELETE);
        }

        /** @var ?User $user */
        $user = $this->userRepository->findOneBy(['id' => $uuid]);
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

    #[Route('/{uuid}/bookings', name: 'getUserBookings', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: Booking::class))
        )
    )]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    /**
     * Get all bookings of a specific user by UUID
     */
    public function getUserBookings(string $uuid, Request $request, SerializerInterface $serializerInterface): JsonResponse
    {
        /** @var ?User $currentUser */
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            throw new AccessDeniedException(self::UNAUTHORIZED_DELETE);
        }
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedException(self::UNAUTHORIZED_DELETE);
        }

        $user = $this->userRepository->findOneBy(['id' => $uuid]);

        if ($user === null) {
            return new JsonResponse(self::USER_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }
        /** @var Booking[] $bookings */
        $bookings = $user->getBookings()->toArray();

        $jsonBookings = $serializerInterface->serialize($bookings, 'json', ["groups" => ["user_booking", "stats"]]);
        return new JsonResponse($jsonBookings, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{uuid}/parkings', name: 'getUserParkings', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    #[Route('/{uuid}/parkings', name: 'getUserParkings', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: Parking::class))
        )
    )]
    #[OA\Response(response: 404, description: 'User not found')]
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

        $jsonParkings = $serializerInterface->serialize($parkings, 'json', ["groups" => ["parking", "stats"]]);
        return new JsonResponse($jsonParkings, JsonResponse::HTTP_OK, [], true);
    }


    #[Route('/{uuid}/credit-cards', name: 'getUserCreditCards', methods: ['GET'], requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(
        response: 200,
        description: 'Success',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(new Model(type: CreditCard::class))
        )
    )]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'User not found')]
    /**
     * Get all credit cards of a specific user by UUID
     */
    public function getUserCreditCards(string $uuid, SerializerInterface $serializerInterface): JsonResponse
    {
        /** @var ?User $currentUser */
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            throw new AccessDeniedException(self::UNAUTHORIZED_DELETE);
        }
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedException(self::UNAUTHORIZED_DELETE);
        }

        $user = $this->userRepository->findOneBy(['id' => $uuid]);
        if ($user === null) {
            return new JsonResponse(self::USER_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }
        $creditCards = $user->getCreditCards()->toArray();


        $jsonCreditCards = $serializerInterface->serialize($creditCards, 'json', ["groups" => ["user", "stats"]]);
        return new JsonResponse($jsonCreditCards, JsonResponse::HTTP_OK, [], true);
    }
}
