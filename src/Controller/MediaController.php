<?php

namespace App\Controller;

use App\Types\DataStatus;
use App\Entity\CustomMedia;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: CustomMedia::class))]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["media"],
                properties: [
                    "media" => new OA\Property(
                        type: "string",
                        format: "binary",
                        description: "Media file to upload"
                    )
                ]
            )
        )
    )]
    /**
     * Create a new media
     */
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

        $media->setCreatedAt(new \DateTime());
        $media->setUpdatedAt(new \DateTime());

        $entityManagerInterface->persist($media);
        $entityManagerInterface->flush();

        $jsonMedia = $serializer->serialize($media, 'json');
        $location = $urlGenerator->generate('api_media_new', ['id' => $media->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonMedia, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: CustomMedia::class))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID of the media to get', example: 1)]
    /**
     * Get a specific media by ID
     */
    public function getMedia(CustomMedia $media, SerializerInterface $serializer): JsonResponse
    {
        $location = str_replace('/api', '', "https://localhost/api") . "/images/medias/" . $media->getRealname();

        $jsonMedia = $serializer->serialize($media, 'json', [AbstractNormalizer::GROUPS => ['media']]);

        return new JsonResponse(["media" => json_decode($jsonMedia), "location" => $location], Response::HTTP_OK);
    }


    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID of the media to delete', example: 1)]
    /**
     * Delete a media
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
