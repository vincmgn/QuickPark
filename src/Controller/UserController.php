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
use App\Repository\StatusRepository;
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
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/user', name: 'api_user_')]
#[OA\Tag(name: 'User')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
#[OA\Response(response: 403, description: 'Forbidden')]
final class UserController extends AbstractController
{
    private const USER_NOT_FOUND = ['error' => 'User not found'];
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private TokenStorageInterface $tokenStorage;
    private const UNAUTHORIZED_ACTION = "You are not allowed to do this action.";

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
    }

    private function getCurrentUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }
        $user = $token->getUser();
        return $user instanceof User ? $user : null;
    }

    private function denyAccessUnlessGrantedOwner(User $resourceOwner, ?User $currentUser): void
    {
        if (null === $currentUser || ($resourceOwner !== $currentUser && !$this->isGranted('ROLE_ADMIN'))) {
            throw new AccessDeniedException(self::UNAUTHORIZED_ACTION);
        }
    }

    private function denyAccessUnlessLoggedIn(): ?User
    {
        $currentUser = $this->getCurrentUser();
        if (null === $currentUser) {
            throw new AccessDeniedException(self::UNAUTHORIZED_ACTION);
        }
        return $currentUser;
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: User::class))]
    /**
     * Get a specific user by UUID
     */
    public function get(string $id, SerializerInterface $serializerInterface): JsonResponse
    {
        $currentUser = $this->denyAccessUnlessLoggedIn();
        $user = $this->userRepository->findOneBy(['id' => $id]);

        if (!$user) {
            return new JsonResponse(self::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGrantedOwner($user, $currentUser);

        $jsonUser = $serializerInterface->serialize($user, 'json', ["groups" => ["user", "stats"]]);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/me', name: 'getMe', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: User::class))]
    /**
     * Get the current user details
     */
    public function getMe(SerializerInterface $serializerInterface): JsonResponse
    {
        $currentUser = $this->denyAccessUnlessLoggedIn();
        $jsonUser = $serializerInterface->serialize($currentUser, 'json', ["groups" => ["user", "stats"]]);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(
        response: 200,
        description: 'User updated successfully, returns a new JWT.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string', description: 'The new JWT for the updated user.')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["username" => "example", "email" => "test@test.com", "phone" => "+33612345678"]))]
    /**
     * Update a specific user by UUID
     */
    public function update(string $id, Request $request, SerializerInterface $serializerInterface, ValidatorInterface $validator, StatusRepository $statusRepository, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $currentUser = $this->denyAccessUnlessLoggedIn();
        $user = $this->userRepository->findOneBy(['id' => $id]);

        if (!$user) {
            return new JsonResponse(self::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGrantedOwner($user, $currentUser);

        $data = json_decode($request->getContent(), true);
        $updatedUser = $serializerInterface->deserialize($request->getContent(), User::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $user,
        ]);

        if (isset($data['phone']) && $data['phone'] !== null) {
            $newPhone = (new Phone())
                ->setNumber($data['phone'])
                ->setStatus($statusRepository->findOneBy(['name' => 'Pending']))
                ->setOwner($updatedUser)
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());
            $updatedUser->setPhone($newPhone);
        }

        if (isset($data['email']) && $data['email'] !== null) {
            $newEmail = (new Email())
                ->setEmail($data['email'])
                ->setStatus($statusRepository->findOneBy(['name' => 'Pending']))
                ->setOwner($updatedUser)
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());
            $updatedUser->setEmail($newEmail);
        }

        $errors = $validator->validate($updatedUser);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $user->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        $jsonUser = $serializerInterface->serialize($user, 'json', ["groups" => ["user", "stats"]]);
        $response = new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        $response->headers->set('Authorization', 'Bearer ' . $jwtManager->create($user));
        return $response;
    }

    #[Route('/me', name: 'updateMe', methods: ['PATCH'])]
    #[OA\Response(
        response: 200,
        description: 'User updated successfully, returns a new JWT.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', type: 'string', description: 'The new JWT for the updated user.')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["username" => "example", "email" => "test@test.com", "phone" => "+33612345678"]))]
    /**
     * Update the current user
     */
    public function updateMe(Request $request, SerializerInterface $serializerInterface, ValidatorInterface $validator, StatusRepository $statusRepository, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $currentUser = $this->denyAccessUnlessLoggedIn();
        $data = json_decode($request->getContent(), true);
        $updatedUser = $serializerInterface->deserialize($request->getContent(), User::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser,
        ]);

        if (isset($data['phone']) && $data['phone'] !== null) {
            $newPhone = (new Phone())
                ->setNumber($data['phone'])
                ->setStatus($statusRepository->findOneBy(['name' => 'Pending']))
                ->setOwner($updatedUser)
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());
            $updatedUser->setPhone($newPhone);
        }

        if (isset($data['email']) && $data['email'] !== null) {
            $newEmail = (new Email())
                ->setEmail($data['email'])
                ->setStatus($statusRepository->findOneBy(['name' => 'Pending']))
                ->setOwner($updatedUser)
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());
            $updatedUser->setEmail($newEmail);
        }

        $errors = $validator->validate($updatedUser);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $currentUser->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        $jsonUser = $serializerInterface->serialize($currentUser, 'json', ["groups" => ["user", "stats"]]);
        $response = new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        $response->headers->set('Authorization', 'Bearer ' . $jwtManager->create($currentUser));
        return $response;
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 409, description: 'User cannot be deleted at the moment')]
    /**
     * Delete a specific user by UUID
     */
    public function delete(string $id, TagAwareCacheInterface $cache, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): JsonResponse
    {
        $currentUser = $this->denyAccessUnlessLoggedIn();
        $user = $this->userRepository->findOneBy(['id' => $id]);

        if (!$user) {
            return new JsonResponse(self::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGrantedOwner($user, $currentUser);

        // Check for active bookings for the user
        $activeUserBookings = $bookingRepository->count(['client' => $user, 'dataStatus' => DataStatus::ACTIVE]);
        if (!empty($activeUserBookings)) {
            return new JsonResponse(['message' => "You have active bookings. You cannot delete your account."], Response::HTTP_CONFLICT);
        }

        // Check for active bookings for the user's parkings
        $activeParkingBookings = $bookingRepository->findActiveBookingsForUserParkings($user);
        if (!empty($activeParkingBookings)) {
            return new JsonResponse(['message' => "You have active bookings for your parkings. You cannot delete your account."], Response::HTTP_CONFLICT);
        }

        // Soft delete
        $user->setDataStatus(DataStatus::DELETED);
        $user->setUpdatedAt(new \DateTime());
        if ($email = $user->getEmail()) {
            $entityManager->remove($email);
        }
        if ($phone = $user->getPhone()) {
            $entityManager->remove($phone);
        }
        $currentUser->setUsername($currentUser->getId());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Invalidate cache
        $cache->invalidateTags(["User"]);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/me', name: 'deleteMe', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 409, description: 'User cannot be deleted at the moment')]
    /**
     * Delete the current user
     */
    public function deleteMe(TagAwareCacheInterface $cache, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): JsonResponse
    {
        $currentUser = $this->denyAccessUnlessLoggedIn();

        // Check for active bookings for the user
        $activeUserBookings = $bookingRepository->count(['client' => $currentUser, 'dataStatus' => DataStatus::ACTIVE]);
        if (!empty($activeUserBookings)) {
            return new JsonResponse(['message' => "You have active bookings. You cannot delete your account."], Response::HTTP_CONFLICT);
        }

        // Check for active bookings for the user's parkings
        $activeParkingBookings = $bookingRepository->findActiveBookingsForUserParkings($currentUser);
        if (!empty($activeParkingBookings)) {
            return new JsonResponse(['message' => "You have active bookings for your parkings. You cannot delete your account."], Response::HTTP_CONFLICT);
        }

        // Soft delete
        $currentUser->setDataStatus(DataStatus::DELETED);
        $currentUser->setUpdatedAt(new \DateTime());
        if ($email = $currentUser->getEmail()) {
            $entityManager->remove($email);
        }
        if ($phone = $currentUser->getPhone()) {
            $entityManager->remove($phone);
        }
        $currentUser->setUsername($currentUser->getId());
        $this->entityManager->persist($currentUser);
        $this->entityManager->flush();

        // Invalidate cache
        $cache->invalidateTags(["User"]);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
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
    /**
     * Get all bookings from my account
     */
    public function getUserBookings(SerializerInterface $serializerInterface): JsonResponse
    {
        $currentUser = $this->denyAccessUnlessLoggedIn();
        $bookings = $currentUser->getBookings()->toArray();
        $jsonBookings = $serializerInterface->serialize($bookings, 'json', ["groups" => ["user_booking", "stats"]]);
        return new JsonResponse($jsonBookings, Response::HTTP_OK, [], true);
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
     * Get all parkings from my account
     */
    public function getUserParkings(SerializerInterface $serializerInterface): JsonResponse
    {
        $currentUser = $this->denyAccessUnlessLoggedIn();
        $parkings = $currentUser->getParkings()->toArray();
        $jsonParkings = $serializerInterface->serialize($parkings, 'json', ["groups" => ["parking", "stats"]]);
        return new JsonResponse($jsonParkings, Response::HTTP_OK, [], true);
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
    /**
     * Get all credit cards from my account
     */
    public function getUserCreditCards(SerializerInterface $serializerInterface): JsonResponse
    {
        $currentUser = $this->denyAccessUnlessLoggedIn();
        $creditCards = $currentUser->getCreditCards()->toArray();
        $jsonCreditCards = $serializerInterface->serialize($creditCards, 'json', ["groups" => ["user", "stats"]]);
        return new JsonResponse($jsonCreditCards, Response::HTTP_OK, [], true);
    }
}
