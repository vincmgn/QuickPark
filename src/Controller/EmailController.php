<?php

namespace App\Controller;

use App\Entity\Email;
use App\Repository\EmailRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class EmailController extends AbstractController
{
    #[Route('/email', name: 'app_email', methods: ['GET'])]
    public function index(EmailRepository $emailRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $email = $emailRepository->findAll();
        $jsonEmail = $serializerInterface->serialize($email, 'json');

        return new JsonResponse($jsonEmail, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/email/{id}', name: 'email_get', methods: ['GET'])]
    public function get(Email $email, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonEmail = $serializerInterface->serialize($email, 'json');

        return new JsonResponse($jsonEmail, JsonResponse::HTTP_OK, [], true);
    }
}
