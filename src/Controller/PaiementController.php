<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class PaiementController extends AbstractController
{
    #[Route('/paiement', name: 'app_paiement', methods: ['GET'])]
    public function index(PaiementRepository $paiementRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $paiement = $paiementRepository->findAll();
        $jsonPaiement = $serializerInterface->serialize($paiement, 'json');

        return new JsonResponse($jsonPaiement, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/paiement/{id}', name: 'paiement_get', methods: ['GET'])]
    public function get(Paiement $paiement, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPaiement = $serializerInterface->serialize($paiement, 'json');

        return new JsonResponse($jsonPaiement, JsonResponse::HTTP_OK, [], true);
    }
}
