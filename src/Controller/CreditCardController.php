<?php

namespace App\Controller;

use App\Entity\CreditCard;
use App\Repository\CreditCardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class CreditCardController extends AbstractController
{
    #[Route('/credit_card', name: 'app_credit_card', methods: ['GET'])]
    public function index(CreditCardRepository $creditCardRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $creditCard = $creditCardRepository->findAll();
        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json');

        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/credit_card/{id}', name: 'credit_card_get', methods: ['GET'])]
    public function get(CreditCard $creditCard, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json');

        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_OK, [], true);
    }
}
