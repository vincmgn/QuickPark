<?php

namespace App\Controller;

use App\Entity\CreditCard;
use App\Repository\CreditCardRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/credit_card')]
#[OA\Tag(name: 'CreditCard')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class CreditCardController extends AbstractController
{
    #[Route('s', name: 'app_credit_card', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: CreditCard::class))]
    public function index(CreditCardRepository $creditCardRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $creditCard = $creditCardRepository->findAll();
        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json');

        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'credit_card_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: CreditCard::class))]
    public function get(CreditCard $creditCard, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json');

        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_OK, [], true);
    }
}
