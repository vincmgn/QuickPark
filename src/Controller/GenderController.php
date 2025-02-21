<?php

namespace App\Controller;

use App\Entity\Gender;
use App\Repository\GenderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/gender')]
#[OA\Tag(name: 'Gender')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class GenderController extends AbstractController
{
    #[Route('s', name: 'app_gender', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Gender::class))]
    /**
     * Get all genders
     */
    public function index(GenderRepository $genderRepository, SerializerInterface $serializerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllGender";
        $jsonGender = $cache->get($idCache, function (ItemInterface $item) use ($genderRepository, $serializerInterface) {
            $item->tag("Gender");
            $gender = $genderRepository->findAll();
            return $serializerInterface->serialize($gender, 'json');
        });
        return new JsonResponse($jsonGender, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'gender_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Gender::class))]
    /**
     * Get a specific gender by ID
     */
    public function get(Gender $gender, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonGender = $serializerInterface->serialize($gender, 'json');

        return new JsonResponse($jsonGender, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'gender_add', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: Gender::class))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["name" => "example"]))]
    /**
     * Add a new gender
     */
    public function add(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        $gender = $serializerInterface->deserialize($request->getContent(), Gender::class, 'json');
        $errors = $validator->validate($gender);
        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManagerInterface->persist($gender);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Gender"]);

        $jsonGender = $serializerInterface->serialize($gender, 'json');
        $location = $urlGenerator->generate("gender_get", ['id' => $gender->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonGender, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/{id}', name: 'gender_update', methods: ['PUT'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["name" => "example"]))]
    /**
     * Update an existing gender by ID
     *
     */
    public function update(Gender $gender, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $gender = $serializerInterface->deserialize($request->getContent(), Gender::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $gender]);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Gender"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'gender_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    /**
     * Delete a specific gender by ID
     */
    public function delete(Gender $gender, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManagerInterface->remove($gender);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Gender"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }
}
