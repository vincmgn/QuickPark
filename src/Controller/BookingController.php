<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Booking;
use App\Entity\Paiement;
use App\Types\DataStatus;
use OpenApi\Attributes as OA;
use App\Repository\PriceRepository;
use App\Repository\StatusRepository;
use App\Repository\BookingRepository;
use App\Repository\ParkingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/booking', name: 'api_booking_')]
#[OA\Tag(name: 'Booking')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
#[OA\Response(response: 403, description: 'Forbidden')]
final class BookingController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;

    private const UNAUTHORIZED_ACTION = "You are not allowed to do this action.";

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Booking::class))]
    #[OA\Tag(name: 'Admin', description: 'These endpoints are only accessible to admin users')]
    /**
     * Get all bookings
     */
    public function index(BookingRepository $bookingRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $booking = $bookingRepository->findAll();
        $jsonBooking = $serializerInterface->serialize($booking, 'json', ["groups" => ["booking", "stats", "status", "user"]]);
        return new JsonResponse($jsonBooking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Booking::class))]
    /**
     * Get a specific booking by ID
     */
    public function get(int $id, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface): JsonResponse
    {
        $booking = $entityManagerInterface->getRepository(Booking::class)->find($id);
        if (!$booking) {
            return new JsonResponse(['message' => 'Booking not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User || ($booking->getClient() !== $currentUser && $booking->getParking()->getOwner() !== $currentUser && !$this->isGranted('ROLE_ADMIN'))) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jsonBooking = $serializerInterface->serialize($booking, 'json', ["groups" => ["booking", "stats", "status", "user"]]);
        return new JsonResponse($jsonBooking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/bookings', name: 'booking_new', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Booking created successfully',
        content: new Model(type: Booking::class, groups: ["booking"])
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            example: [
                'parking' => 1201,
                'price' => 1101,
                'startDate' => '2025-04-15T10:00:00+00:00',
                'endDate' => '2025-04-15T12:00:00+00:00',
                'paiement' => [
                    'totalPrice' => 20.5,
                    'creditCardNumber' => '4485237470142195',
                    'ownerName' => 'John Doe'
                ]
            ]
        )
    )]
    /**
     * Add a new booking.
     */
    public function new(
        Request $request,
        SerializerInterface $serializerInterface,
        EntityManagerInterface $entityManagerInterface,
        ParkingRepository $parkingRepository,
        PriceRepository $priceRepository,
        StatusRepository $statusRepository,
        UrlGeneratorInterface $urlGenerator,
        TagAwareCacheInterface $cache,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage
    ): JsonResponse {
        $token = $tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token?->getUser();
        if (null === $token || !$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $booking = $serializerInterface->deserialize($request->getContent(), Booking::class, 'json');
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid JSON format: ' . $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $booking->setClient($currentUser);
        $booking->setCreatedAt(new \DateTimeImmutable());
        $booking->setUpdatedAt(new \DateTime());

        $requestData = json_decode($request->getContent(), true);

        // Fetch Parking
        $parkingId = $requestData['parking'] ?? null;
        if ($parkingId) {
            $parking = $parkingRepository->findOneBy(['id' => $parkingId]);
            if (!$parking) {
                return new JsonResponse(['message' => 'Parking not found'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $booking->setParking($parking);
        } else {
            return new JsonResponse(['message' => 'Parking ID is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Fetch Price
        $priceId = $requestData['price'] ?? null;
        if ($priceId) {
            $price = $priceRepository->findOneBy(['id' => $priceId]);
            if (!$price) {
                return new JsonResponse(['message' => 'Price not found'], JsonResponse::HTTP_BAD_REQUEST);
            } else if ($price->getParking() !== $parking) {
                return new JsonResponse(['message' => 'Price does not belong to the selected parking'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $booking->setPrice($price);
        } else {
            return new JsonResponse(['message' => 'Price ID is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Handle nested Paiement entity creation
        $paiementData = $requestData['paiement'] ?? null;
        if ($paiementData) {
            $paiement = $serializerInterface->deserialize(json_encode($paiementData), Paiement::class, 'json');
            $paiement->setCreatedAt(new \DateTimeImmutable());
            $paiement->setUpdatedAt(new \DateTime());
            $paiement->setStatus(
                $statusRepository->findOneBy(['name' => 'Pending'])
            );
            $booking->setPaiement($paiement);
        }

        $errors = $validator->validate($booking);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $booking->setDataStatus(DataStatus::ACTIVE);
        $booking->setStatus(
            $statusRepository->findOneBy(['name' => 'Pending'])
        );
        $booking->setCreatedAt(new \DateTimeImmutable());
        $booking->setUpdatedAt(new \DateTime());
        $entityManagerInterface->persist($booking);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Booking"]);

        $jsonBooking = $serializerInterface->serialize($booking, 'json', ["groups" => ["booking", "stats", "status", "user"]]);
        $location = $urlGenerator->generate("api_booking_get", ['id' => $booking->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonBooking, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: [
        'startDate' => '2025-04-15T10:00:00+00:00',
        'endDate' => '2025-04-15T12:00:00+00:00',
    ]))]
    /**
     * Update an existing booking by ID
     *
     */
    public function update(int $id, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        $booking = $entityManagerInterface->getRepository(Booking::class)->find($id);
        if (!$booking) {
            return new JsonResponse(['message' => 'Booking not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $booking = $serializerInterface->deserialize($request->getContent(), Booking::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $booking]);
        $booking->setUpdatedAt(new \DateTime());
        $errors = $validator->validate($booking);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManagerInterface->flush();
        $cache->invalidateTags(["Booking"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    /**
     * Delete a specific booking by ID
     */
    public function delete(int $id, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $booking = $entityManagerInterface->getRepository(Booking::class)->find($id);
        if (!$booking) {
            return new JsonResponse(['message' => 'Booking not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User || ($booking->getClient() !== $currentUser && $booking->getParking()->getOwner() !== $currentUser && !$this->isGranted('ROLE_ADMIN'))) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Soft delete
        $booking->setDataStatus(DataStatus::DELETED);
        $booking->setUpdatedAt(new \DateTime());

        $entityManagerInterface->persist($booking);
        $entityManagerInterface->flush();

        $cache->invalidateTags(["Booking"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }
}
