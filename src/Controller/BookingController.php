<?php

namespace App\Controller;

use App\Entity\Booking;
use OpenApi\Attributes as OA;
use App\Types\DataStatus;
use App\Repository\BookingRepository;
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

#[Route('/api/booking', name: 'api_booking_')]
#[OA\Tag(name: 'Booking')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class BookingController extends AbstractController
{
    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Booking::class))]
    /**
     * Get all bookings
     */
    public function index(BookingRepository $bookingRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $booking = $bookingRepository->findAll();
        $jsonBooking = $serializerInterface->serialize($booking, 'json', ["groups" => ["booking", "stats"]]);

        return new JsonResponse($jsonBooking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Booking::class))]
    /**
     * Get a specific booking by ID
     */
    public function get(Booking $booking, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonBooking = $serializerInterface->serialize($booking, 'json', ["groups" => ["booking", "stats"]]);

        return new JsonResponse($jsonBooking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: Booking::class))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["name" => "example"]))]
    /**
     * Add a booking
     */
    public function add(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        $booking = $serializerInterface->deserialize($request->getContent(), Booking::class, 'json');
        $errors = $validator->validate($booking);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManagerInterface->persist($booking);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Booking"]);

        $jsonBooking = $serializerInterface->serialize($booking, 'json');
        $location = $urlGenerator->generate("booking_get", ['id' => $booking->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBooking, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["name" => "example"]))]
    /**
     * Update an existing booking by ID
     *
     */
    public function update(Booking $booking, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $booking = $serializerInterface->deserialize($request->getContent(), Booking::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $booking]);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Booking"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    /**
     * Delete a specific booking by ID
     */
    public function delete(Booking $booking, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        // Soft delete
        $booking->setDataStatus(DataStatus::DELETED);
        $booking->setUpdatedAt(new \DateTime());
    
        $entityManagerInterface->persist($booking);
        $entityManagerInterface->flush();
    
        $cache->invalidateTags(["Booking"]);
    
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }    
}
