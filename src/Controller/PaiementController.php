<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Repository\PaiementRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/paiement', name: 'api_paiement_')]
#[OA\Tag(name: 'Paiement')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class PaiementController extends AbstractController
{
    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Paiement::class))]
    /**
     * Get all paiements
     */
    public function index(PaiementRepository $paiementRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $paiement = $paiementRepository->findAll();
        $jsonPaiement = $serializerInterface->serialize($paiement, 'json');

        return new JsonResponse($jsonPaiement, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Paiement::class))]
    /**
     * Get a specific paiement by ID
     */
    public function get(Paiement $paiement, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPaiement = $serializerInterface->serialize($paiement, 'json');

        return new JsonResponse($jsonPaiement, JsonResponse::HTTP_OK, [], true);
    }
}
