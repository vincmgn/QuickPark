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

#[Route('/api/paiement')]
#[OA\Tag(name: 'Paiement')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class PaiementController extends AbstractController
{
    #[Route('s', name: 'app_paiement', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Paiement::class))]
    public function index(PaiementRepository $paiementRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $paiement = $paiementRepository->findAll();
        $jsonPaiement = $serializerInterface->serialize($paiement, 'json');

        return new JsonResponse($jsonPaiement, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'paiement_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Paiement::class))]
    public function get(Paiement $paiement, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPaiement = $serializerInterface->serialize($paiement, 'json');

        return new JsonResponse($jsonPaiement, JsonResponse::HTTP_OK, [], true);
    }
}
