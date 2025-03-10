<?php

namespace App\Controller;

use App\Entity\Parking;
use OpenApi\Attributes as OA;
use App\Repository\ParkingRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/parking', name: 'api_parking_')]
#[OA\Tag(name: 'Parking')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class ParkingController extends AbstractController
{
    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Parking::class))]
    /**
     * Get all parkings
     */
    public function index(ParkingRepository $parkingRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $parking = $parkingRepository->findAll();
        $jsonParking = $serializerInterface->serialize($parking, 'json', ["groups" => ["parking", "stats"]]);

        return new JsonResponse($jsonParking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Parking::class))]
    /**
     * Get a specific parking by ID
     */
    public function get(Parking $parking, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonParking = $serializerInterface->serialize($parking, 'json');

        return new JsonResponse($jsonParking, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('s', name: 'parkings_get', methods: ['GET'])]
    public function getParkings(
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

        $jsonParkings = $serializerInterface->serialize($pagination->getItems(), 'json');
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
}
