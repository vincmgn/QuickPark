<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\CreditCard;
use OpenApi\Attributes as OA;
use App\Repository\CreditCardRepository;
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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/credit_card', name: 'api_credit_card_')]
#[OA\Tag(name: 'CreditCard')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class CreditCardController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private const UNAUTHORIZED_ACTION = "You are not allowed to do this action.";
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: CreditCard::class))]
    /**
     * Get all credit cards
     */
    public function index(CreditCardRepository $creditCardRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $creditCard = $creditCardRepository->findAll();
        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json', [
            'groups' => ['user_booking', 'user']
        ]);

        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: CreditCard::class))]
    /**
     * Get a specific credit card by ID
     */
    public function get(CreditCard $creditCard, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json', [
            'groups' => ['user_booking', 'user']
        ]);

        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: CreditCard::class))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["number" => "4485237470142195", "expirationDate" => "2025-12-31 00:00:00", "owner_name" => "John Doe"]))]
    /**
     * Add a new credit card
     */
    public function new(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        /** @var ?User $currentUser */
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $currentUser = $token->getUser();

        $creditCard = $serializerInterface->deserialize($request->getContent(), CreditCard::class, 'json');
        $creditCard->setOwner($currentUser);
        $creditCard->setOwnerName($data['owner_name']);

        $creditCard->setCreatedAt(new \DateTimeImmutable());
        $creditCard->setUpdatedAt(new \DateTime());

        $errors = $validator->validate($creditCard);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json',), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManagerInterface->persist($creditCard);
        $entityManagerInterface->flush();

        $cache->invalidateTags(['CreditCard']);

        $jsonCreditCard = $serializerInterface->serialize($creditCard, 'json', ['groups' => ['user_booking', 'user']]);
        $location = $urlGenerator->generate('api_credit_card_get', ['id' => $creditCard->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonCreditCard, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["number" => "4485237470142195", "expirationDate" => "2025-12-31 00:00:00", "owner_name" => "John Doe"]))]
    /**
     * Update a credit card
     */
    public function update(Request $request, CreditCard $creditCard, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        /** @var ?User $currentUser */
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $creditCard = $serializerInterface->deserialize($request->getContent(), CreditCard::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $creditCard]);
        $creditCard->setOwner($currentUser);
        $creditCard->setUpdatedAt(new \DateTime());

        $errors = $validator->validate($creditCard);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManagerInterface->flush();
        $cache->invalidateTags(['CreditCard']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    /**
     * Delete a credit card
     */
    public function delete(CreditCard $creditCard, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManagerInterface->remove($creditCard);
        $entityManagerInterface->flush();
        $cache->invalidateTags(['CreditCard']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }
}
