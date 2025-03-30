<?php

namespace App\Serializer\Denormalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DateIntervalDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): \DateInterval
    {
        if (!is_string($data)) {
            throw new \InvalidArgumentException("Invalid format for DateInterval. Expected string.");
        }

        $interval = new \DateInterval($data);

        // Check if the duration is greater than 0 minutes or a valid time span (e.g., days, weeks)
        if ($interval->h === 0 && $interval->i === 0 && $interval->s === 0 && $interval->d === 0 && $interval->m === 0 && $interval->y === 0) {
            throw new \InvalidArgumentException("The duration must be greater than 0 minute.");
        }

        return $interval;
    }



    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \DateInterval::class && is_string($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            \DateInterval::class => true,
        ];
    }
}
