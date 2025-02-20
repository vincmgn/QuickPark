<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/booking')]
#[OA\Tag(name: 'Booking')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class BookingController extends AbstractController
{
    #[Route('s', name: 'app_booking', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Booking::class))]
    public function index(BookingRepository $bookingRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $booking = $bookingRepository->findAll();
        $jsonBooking = $serializerInterface->serialize($booking, 'json');

        return new JsonResponse($jsonBooking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'booking_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Booking::class))]
    public function get(Booking $booking, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonBooking = $serializerInterface->serialize($booking, 'json');

        return new JsonResponse($jsonBooking, JsonResponse::HTTP_OK, [], true);
    }
}
