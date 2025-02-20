<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class BookingController extends AbstractController
{
    #[Route('/booking', name: 'app_booking', methods: ['GET'])]
    public function index(BookingRepository $bookingRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $booking = $bookingRepository->findAll();
        $jsonBooking = $serializerInterface->serialize($booking, 'json');

        return new JsonResponse($jsonBooking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/booking/{id}', name: 'booking_get', methods: ['GET'])]
    public function get(Booking $booking, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonBooking = $serializerInterface->serialize($booking, 'json');

        return new JsonResponse($jsonBooking, JsonResponse::HTTP_OK, [], true);
    }
}
