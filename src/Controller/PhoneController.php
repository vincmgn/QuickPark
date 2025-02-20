<?php

namespace App\Controller;

use App\Entity\Phone;
use App\Repository\PhoneRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/phone')]
#[OA\Tag(name: 'Phone')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class PhoneController extends AbstractController
{
    #[Route('s', name: 'app_phone', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Phone::class))]
    public function index(PhoneRepository $phoneRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $phone = $phoneRepository->findAll();
        $jsonPhone = $serializerInterface->serialize($phone, 'json');

        return new JsonResponse($jsonPhone, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'phone_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Phone::class))]
    public function get(Phone $phone, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPhone = $serializerInterface->serialize($phone, 'json');

        return new JsonResponse($jsonPhone, JsonResponse::HTTP_OK, [], true);
    }
}
