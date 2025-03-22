<?php

namespace App\Controller;

use App\Entity\Price;
use App\Entity\Parking;
use OpenApi\Attributes as OA;
use App\Repository\PriceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/price', name: 'api_price_')]
#[OA\Tag(name: 'Price')]
#[OA\Response(response: 400, description: 'Bad request')]
#[OA\Response(response: 401, description: 'Unauthorized')]
final class PriceController extends AbstractController
{
    #[Route('', name: 'getAll', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Price::class))]
    /**
     * Get all prices
     */
    public function index(PriceRepository $priceRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $price = $priceRepository->findAll();
        $jsonPrice = $serializerInterface->serialize($price, 'json', ['groups' => ['booking', 'parking',  'user_booking']]);

        return new JsonResponse($jsonPrice, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Success', content: new Model(type: Price::class))]
    /**
     * Get a specific price by ID
     */
    public function get(Price $price, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonPrice = $serializerInterface->serialize($price, 'json', ['groups' => ['booking', 'parking',  'user_booking']]);

        return new JsonResponse($jsonPrice, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('', name: 'new', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Created', content: new Model(type: Price::class))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["price" => 10.5, "duration" => "P1D", "currency" => "EUR", "parking" => 1]))]
    /**
     * Add a new price
     */
    public function new(Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UrlGeneratorInterface $urlGenerator, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        // Deserialize the Price entity
        $price = $serializerInterface->deserialize($request->getContent(), Price::class, 'json');

        // Get the parking ID from the request (if provided)
        $parkingId = $price->getParking()?->getId();

        // Fetch the associated Parking entity from the database by ID
        if ($parkingId) {
            $parking = $entityManagerInterface->getRepository(Parking::class)->find($parkingId);

            // Ensure the parking exists and has an owner
            if (!$parking || !$parking->getOwner()) {
                return new JsonResponse(['error' => 'Parking must have an owner'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Set the parking on the Price entity (with the owner set)
            $price->setParking($parking);
        } else {
            return new JsonResponse(['error' => 'Parking ID is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Validate the Price entity
        $errors = $validator->validate($price);
        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Persist the Price entity
        $entityManagerInterface->persist($price);
        $entityManagerInterface->flush();

        // Invalidate cache
        $cache->invalidateTags(["Price"]);

        // Serialize the Price to return in the response
        $jsonPrice = $serializerInterface->serialize($price, 'json', ['groups' => ['booking', 'parking', 'user_booking']]);

        // Generate the location for the newly created Price entity
        $location = $urlGenerator->generate('price_get', ['id' => $price->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonPrice, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }


    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[OA\Response(response: 204, description: 'No content')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(example: ["price" => 10.5, "duration" => "P1D", "currency" => "EUR", "parking" => 1]))]
    /**
     * Update a price by ID
     */
    public function update(Price $price, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache, ValidatorInterface $validator): JsonResponse
    {
        $serializerInterface->deserialize(
            $request->getContent(),
            Price::class,
            'json',
            [
                'object_to_populate' => $price,
                'disable_type_enforcement' => true,
            ]
        );
        $errors = $validator->validate($price);
        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json', ['groups' => ['booking', 'parking',  'user_booking']]), JsonResponse::HTTP_BAD_REQUEST, [], false);
        }
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Price"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'No content')]
    /**
     * Delete a price by ID
     */
    public function delete(Price $price, EntityManagerInterface $entityManagerInterface, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManagerInterface->remove($price);
        $entityManagerInterface->flush();
        $cache->invalidateTags(["Price"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], false);
    }
}
