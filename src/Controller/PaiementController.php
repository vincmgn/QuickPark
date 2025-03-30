<?php

namespace App\Controller;

use App\Entity\Status;
use App\Entity\Paiement;
use App\Types\DataStatus;
use OpenApi\Attributes as OA;
use App\Repository\PaiementRepository;
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

#[Route('/api/paiement', name: 'api_paiement_')]
#[OA\Tag(name: 'Paiement')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class PaiementController extends AbstractController
{
    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Paiement::class))]
    #[OA\Tag(name: 'Admin', description: 'These endpoints are only accessible to admin users')]
    /**
     * Get all paiements
     */
    public function index(PaiementRepository $paiementRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $paiement = $paiementRepository->findAll();
        $jsonPaiement = $serializerInterface->serialize($paiement, 'json', [
            'groups' => ['paiement']
        ]);

        return new JsonResponse($jsonPaiement, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Paiement::class))]
    /**
     * Get a specific paiement by ID
     */
    public function get(int $id, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface): JsonResponse
    {
        $paiement = $entityManagerInterface->getRepository(Paiement::class)->find($id);
        if (!$paiement) {
            return new JsonResponse(['message' => 'Paiement not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $jsonPaiement = $serializerInterface->serialize($paiement, 'json', [
            'groups' => ['paiement', 'status', 'credit_card']
        ]);
        return new JsonResponse($jsonPaiement, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Success', content: new Model(type: Paiement::class))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["creditCard" => 1, "totalPrice" => 100]))]
    /**
     * Create a new paiement
     */
    public function new(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $paiement = new Paiement();
        $status = $entityManagerInterface->getRepository(Status::class)->findOneBy(['name' => 'Pending']);
        $paiement->setStatus($status);
        $paiement->setCreditCard($data['creditCard']);
        $paiement->setTotalPrice($data['totalPrice']);

        $errors = $validator->validate($paiement);

        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManagerInterface->persist($paiement);
        $entityManagerInterface->flush();

        $cache->invalidateTags(['paiement']);

        $jsonPaiement = $serializerInterface->serialize($paiement, 'json', ['groups' => ['paiement', 'status', 'credit_card']]);
        $location = $urlGenerator->generate('api_paiement_get', ['id' => $paiement->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonPaiement, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Paiement::class))]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 404, description: 'Paiement not found')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: [
        "creditCardNumber" => "4485237470142195",
        "totalPrice" => 100
    ]))]
    /**
     * Update a specific paiement by ID
     */
    public function update(int $id, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $paiement = $entityManagerInterface->getRepository(Paiement::class)->find($id);
        if (!$paiement) {
            return new JsonResponse(['message' => 'Paiement not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $paiement = $serializerInterface->deserialize($request->getContent(), Paiement::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $paiement]);
        $errors = $validator->validate($paiement);
        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $paiement->setUpdatedAt(new \DateTime());
        $entityManagerInterface->flush();
        $cache->invalidateTags(['paiement']);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Response(response: 404, description: 'Paiement not found')]
    /**
     * Delete a specific paiement by ID
     */
    public function delete(int $id, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $paiement = $entityManagerInterface->getRepository(Paiement::class)->find($id);
        if (!$paiement) {
            return new JsonResponse(['message' => 'Paiement not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Soft delete
        $paiement->setDataStatus(DataStatus::DELETED);
        $paiement->setUpdatedAt(new \DateTimeImmutable());
        $entityManagerInterface->flush();
        $cache->invalidateTags(['paiement']);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
