<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Parking $parking = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Price $price = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Status $status = null;

    #[ORM\OneToOne(mappedBy: 'booking', cascade: ['persist', 'remove'])]
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

    public function setPaiement(Paiement $paiement): static
    {
        // set the owning side of the relation if necessary
        if ($paiement->getBooking() !== $this) {
            $paiement->setBooking($this);
        }

        $this->paiement = $paiement;

        return $this;
    }
}
