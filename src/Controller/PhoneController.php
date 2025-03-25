<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Phone;
use App\Entity\Status;
use OpenApi\Attributes as OA;
use App\Repository\PhoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/phone', name: 'api_phone_')]
#[OA\Tag(name: 'Phone')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class PhoneController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private const UNAUTHORIZED_ACTION = "You are not allowed to do this action.";

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }


    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Phone::class))]
    /**
     * Get all phones
     */
    public function index(PhoneRepository $phoneRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $phone = $phoneRepository->findAll();
        $jsonPhone = $serializerInterface->serialize($phone, 'json', ['groups' => ['phone']]);
        return new JsonResponse($jsonPhone, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Phone::class))]
    /**
     * Get a specific phone by ID
     */
    public function get(Phone $phone, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPhone = $serializerInterface->serialize($phone, 'json', ['groups' => ['phone']]);

        return new JsonResponse($jsonPhone, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: Phone::class))]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["number" => "+33612345678"]))]
    /**
     * Create a new phone
     */
    public function new(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        /** @var ?User $currentUser */
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $currentUser = $token->getUser();

        $phone = $serializerInterface->deserialize($request->getContent(), Phone::class, 'json');
        $status = $entityManagerInterface->getRepository(Status::class)->findOneBy(['name' => 'Pending']);
        $phone->setStatus($status);
        $phone->setOwner($currentUser);
        $phone->setCreatedAt(new \DateTimeImmutable());
        $phone->setUpdatedAt(new \DateTime());
        $errors = $validator->validate($phone);

        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json', ['groups' => ['phone']]), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManagerInterface->persist($phone);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Phone"]);

        $jsonPhone = $serializerInterface->serialize($phone, 'json', ['groups' => ['phone']]);
        $response = $urlGenerator->generate("api_phone_get", ["id" => $phone->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonPhone, JsonResponse::HTTP_CREATED, ["Location" => $response], true);
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["number" => "+33612345678"]))]
    /**
     * Edit a phone by ID
     */
    public function update(Phone $phone, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
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

        $phone = $serializerInterface->deserialize($request->getContent(), Phone::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $phone]);
        $phone->setUpdatedAt(new \DateTime());
        $phone->setOwner($currentUser);
        $errors = $validator->validate($phone);

        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], false);
        }
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Phone"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    /**
     * Delete a phone by ID
     */
    public function delete(Phone $phone, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
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

        $entityManagerInterface->remove($phone);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Phone"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }
}
