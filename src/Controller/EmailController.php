<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Email;
use App\Entity\Status;
use OpenApi\Attributes as OA;
use App\Repository\EmailRepository;
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

#[Route('/api/email', name: 'api_email_')]
#[OA\Tag(name: 'Email')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class EmailController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private const UNAUTHORIZED_ACTION = "You are not allowed to do this action.";

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }


    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Email::class))]
    /**
     * Get all emails
     */
    public function index(EmailRepository $emailRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $email = $emailRepository->findAll();
        $jsonEmail = $serializerInterface->serialize($email, 'json', ['groups' => ['email', 'user']]);
        return new JsonResponse($jsonEmail, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Email::class))]
    /**
     * Get a specific email by ID
     */
    public function get(int $id, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface): JsonResponse
    {
        $email = $entityManagerInterface->getRepository(Email::class)->find($id);
        if (!$email) {
            return new JsonResponse(['message' => 'Email not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User || ($email->getOwner() !== $currentUser && !$this->isGranted('ROLE_ADMIN'))) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jsonEmail = $serializerInterface->serialize($email, 'json', ['groups' => ['email', 'user']]);
        return new JsonResponse($jsonEmail, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: Email::class))]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["email" => "test@test.com"]))]
    /**
     * Create a new email
     */
    public function new(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $email = $serializerInterface->deserialize($request->getContent(), Email::class, 'json');
        $status = $entityManagerInterface->getRepository(Status::class)->findOneBy(['name' => 'Pending']);
        $email->setStatus($status);
        $email->setOwner($currentUser);
        $email->setCreatedAt(new \DateTimeImmutable());
        $email->setUpdatedAt(new \DateTime());
        $errors = $validator->validate($email);

        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json', ['groups' => ['email']]), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManagerInterface->persist($email);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Email"]);

        $jsonEmail = $serializerInterface->serialize($email, 'json', ['groups' => ['email']]);
        $response = $urlGenerator->generate("api_email_get", ["id" => $email->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonEmail, JsonResponse::HTTP_CREATED, ["Location" => $response], true);
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["email" => "test@test.com"]))]
    /**
     * Edit a email by ID
     */
    public function update(int $id, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        $email = $entityManagerInterface->getRepository(Email::class)->find($id);
        if (!$email) {
            return new JsonResponse(['message' => 'Email not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User || ($email->getOwner() !== $currentUser && !$this->isGranted('ROLE_ADMIN'))) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }
        
        $email = $serializerInterface->deserialize($request->getContent(), Email::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $email]);
        $email->setUpdatedAt(new \DateTime());
        $errors = $validator->validate($email);

        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], false);
        }
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Email"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    /**
     * Delete a email by ID
     */
    public function delete(int $id, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $email = $entityManagerInterface->getRepository(Email::class)->find($id);
        if (!$email) {
            return new JsonResponse(['message' => 'Email not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $token = $this->tokenStorage->getToken();
        /** @var ?User $currentUser */
        $currentUser = $token->getUser();
        if (null === $token || !$currentUser instanceof User || ($email->getOwner() !== $currentUser && !$this->isGranted('ROLE_ADMIN'))) {
            return new JsonResponse(['message' => self::UNAUTHORIZED_ACTION], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $entityManagerInterface->remove($email);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Email"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }
}
