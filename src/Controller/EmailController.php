<?php

namespace App\Controller;

use App\Entity\Email;
use App\Repository\EmailRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/email')]
#[OA\Tag(name: 'Email')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class EmailController extends AbstractController
{
    #[Route('s', name: 'app_email', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Email::class))]
    public function index(EmailRepository $emailRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $email = $emailRepository->findAll();
        $jsonEmail = $serializerInterface->serialize($email, 'json');

        return new JsonResponse($jsonEmail, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'email_get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Email::class))]
    public function get(Email $email, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonEmail = $serializerInterface->serialize($email, 'json');

        return new JsonResponse($jsonEmail, JsonResponse::HTTP_OK, [], true);
    }
}
