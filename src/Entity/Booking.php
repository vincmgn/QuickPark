<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookingRepository;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[Assert\Callback([self::class, 'validateDates'])]
class Booking
{
    use Traits\StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["booking", "parking"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["booking" ])]
    private ?Parking $parking = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["booking"])]
    private ?Price $price = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["booking", "parking"])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["booking", "parking"])]
    private ?\DateTimeInterface $endDate = null;

    public static function validateDates(Booking $booking, ExecutionContextInterface $context): void
    {
        if ($booking->getStartDate() >= $booking->getEndDate()) {
            $context->buildViolation('The start date must be before the end date')
                ->atPath('startDate')
                ->addViolation();
        }
    }

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["booking"])]
    private ?Status $status = null;

    #[ORM\ManyToOne(inversedBy: 'booking')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["booking", "parking"])]
    private ?Paiement $paiement = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): static
    {
        $this->paiement = $paiement;

        return $this;
    }
}
