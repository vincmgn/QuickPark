<?php

namespace App\Controller;

use App\Entity\CreditCard;
use OpenApi\Attributes as OA;
use App\Repository\CreditCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/credit_card')]
#[OA\Tag(name: 'CreditCard')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class CreditCardController extends AbstractController
{
    #[Route('', name: 'app_credit_card', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: CreditCard::class))]
    /**
     * Get all credit cards
     */
    public function index(CreditCardRepository $creditCardRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $creditCard = $creditCardRepository->findAll();
        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json');

        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'credit_card_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: CreditCard::class))]
    /**
     * Get a specific credit card by ID
     */
    public function get(CreditCard $creditCard, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json');

        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'credit_card_add', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: CreditCard::class))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["number" => "4485237470142195", "expirationDate" => "2021-12-31 00:00:00"]))]
    /**
     * Add a new credit card
     */
    public function add(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $creditCard = $serializerInterface->deserialize($request->getContent(), CreditCard::class, 'json');
        $errors = $validator->validate($creditCard);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManagerInterface->persist($creditCard);
        $entityManagerInterface->flush();

        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json');
        $location = $urlGenerator->generate('credit_card_get', ['id' => $creditCard->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/{id}', name: 'credit_card_update', methods: ['PUT'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["number" => "4485237470142195", "expirationDate" => "2021-12-31 00:00:00"]))]
    /**
     * Update a credit card
     */
    public function update(Request $request, CreditCard $creditCard, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ValidatorInterface $validator): JsonResponse
    {
        $creditCard = $serializerInterface->deserialize($request->getContent(), CreditCard::class, 'json');
        $errors = $validator->validate($creditCard);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManagerInterface->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'credit_card_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    /**
     * Delete a credit card
     */
    public function delete(CreditCard $creditCard, EntityManagerInterface $entityManagerInterface): JsonResponse
    {
        $entityManagerInterface->remove($creditCard);
        $entityManagerInterface->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }
}
