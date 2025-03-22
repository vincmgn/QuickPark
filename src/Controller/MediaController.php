<?php

namespace App\Controller;

use App\Types\DataStatus;
use App\Entity\CustomMedia;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/media', name: 'api_media_')]
#[OA\Tag(name: 'Media')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class MediaController extends AbstractController
{
    // #[Route('', name: 'app_media')]
    // public function index(): Response
    // {
    //     return $this->render('media/index.html.twig', [
    //         'controller_name' => 'MediaController',
    //     ]);
    // }

    #[Route('', name: 'new', methods: ['POST'])]
    public function createMedia(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator): Response
    {
        $media = new CustomMedia();
        $files = $request->files->get('media');
        if ($files === null) {
            return new JsonResponse(['error' => 'No file uploaded'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $media->setMedia($files);
        $media->setRealname($files->getClientOriginalName());
        $media->setPublicPath('/public/images/medias');
        $entityManagerInterface->persist($media);
        $entityManagerInterface->flush();

        $jsonMedia = $serializer->serialize($media, 'json');
        $location = $urlGenerator->generate('api_media_new', ['id' => $media->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonMedia, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function getMedia(CustomMedia $media, UrlGeneratorInterface $urlGenerator)
    {
        $location = $urlGenerator->generate("api_media_get", ["id" => $media->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $location = $location . str_replace("/public/", "", $media->getPublicPath()) . "/" . $media->getRealPath();

        return new JsonResponse(["media" => $media, "location" => $location], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID of the media to delete', example: 1)]

    /**
     * Delete a media
     * This is a hard and definitive delete because of GDPR rules and because a media is a heavy file
     */
    #[Route('', name: 'delete', methods: ['DELETE'])]
    public function deleteMedia(CustomMedia $media, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManagerInterface->remove($media);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Media"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
