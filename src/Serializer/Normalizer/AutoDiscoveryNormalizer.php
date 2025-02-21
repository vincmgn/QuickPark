<?php

namespace App\Serializer\Normalizer;

use App\Entity\Gender;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AutoDiscoveryNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);
        $className = (new \ReflectionClass($object))->getShortName();
        $className = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        $data["_links"] = [
            "getAll" => [
                "method" => "GET",
                "path" => $this->urlGenerator->generate("api_" . $className . "_getAll")

            ],
            "self" => [
                "method" => "GET",
                "path" => $this->urlGenerator->generate("api_" . $className . "_get", ["id" => $data["id"]])
            ],
            "new" => [
                "method" => "POST",
                "path" => $this->urlGenerator->generate("api_" . $className . "_new")
            ],
            "edit" => [
                "method" => "PUT",
                "path" => $this->urlGenerator->generate("api_" . $className . "_edit", ["id" => $data["id"]])
            ],
            "delete" => [
                "method" => "DELETE",
                "path" => $this->urlGenerator->generate("api_" . $className . "_delete", ["id" => $data["id"]])
            ]
        ];

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Gender && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Gender::class => true,
        ];
    }
}
