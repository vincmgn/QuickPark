<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PriceRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: PriceRepository::class)]
#[Assert\Callback(callback: 'validateDuration')]
#[Gedmo\Loggable]
class Price
{
    use Traits\StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["booking", "parking", "user_booking", "parking_prices"])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(["booking", "parking", "user_booking", "parking_prices"])]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Type('float')]
    #[Assert\GreaterThan(0, message: "The price must be greater than 0.")]
    #[Gedmo\Versioned]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(["parking", "user_booking", "parking_prices"])]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Type('\DateInterval')]
    #[Gedmo\Versioned]
    private ?\DateInterval $duration = null;
    public static function validateDuration($object, ExecutionContextInterface $context)
    {
        // Check if the duration has any time component
        if ($object->duration && ($object->duration->h === 0 && $object->duration->i === 0 && $object->duration->d === 0)) {
            // Here you might want to check for a minimum duration in hours or minutes
            $context->buildViolation('The duration must be greater than 0 minute.')
                ->atPath('duration')
                ->addViolation();
        }
    }

    #[ORM\ManyToOne(inversedBy: 'prices', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["price_parking"])]
    private ?Parking $parking = null;

    #[ORM\Column(length: 255)]
    #[Groups(["booking", "parking", "user_booking", "parking_prices"])]
    #[Gedmo\Versioned]
    private ?string $currency = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDuration(): ?\DateInterval
    {
        return $this->duration;
    }

    public function setDuration(\DateInterval $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getParking(): ?Parking
    {
        return $this->parking;
    }

    public function setParking(?Parking $parking): static
    {
        $this->parking = $parking;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->parking ? $this->parking->getOwner() : null;
    }
}
