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

#[Route('/api/gender')]
#[OA\Tag(name: 'Gender')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class GenderController extends AbstractController
{
    #[Route('s', name: 'app_gender', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Gender::class))]
    public function index(GenderRepository $genderRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $gender = $genderRepository->findAll();
        $jsonGender = $serializerInterface->serialize($gender, 'json');

        return new JsonResponse($jsonGender, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'gender_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Gender::class))]
    public function get(Gender $gender, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonGender = $serializerInterface->serialize($gender, 'json');

        return new JsonResponse($jsonGender, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'gender_add', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: Gender::class))]
    public function add(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $gender = $serializerInterface->deserialize($request->getContent(), Gender::class, 'json');
        $entityManagerInterface->persist($gender);
        $entityManagerInterface->flush();

        $jsonGender = $serializerInterface->serialize($gender, 'json');
        $location = $urlGenerator->generate("gender_get", ['id' => $gender->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonGender, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/{id}', name: 'gender_update', methods: ['PUT'])]
    #[OA\Response(response: 204, description: 'No content')]
    public function update(Request $request, Gender $gender, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface): JsonResponse
    {
        $gender = $serializerInterface->deserialize($request->getContent(), Gender::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $gender]);
        $entityManagerInterface->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'gender_delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    public function delete(Gender $gender, EntityManagerInterface $entityManagerInterface): JsonResponse
    {
        $entityManagerInterface->remove($gender);
        $entityManagerInterface->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }
}
