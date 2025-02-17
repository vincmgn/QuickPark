<?php

namespace App\Entity;

use App\Repository\ParkingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParkingRepository::class)]
class Parking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'point')]
    private $location;

    #[ORM\Column]
    private ?bool $isEnabled = null;

    /**
     * @var Collection<int, Price>
     */
    #[ORM\OneToMany(targetEntity: Price::class, mappedBy: 'parking', orphanRemoval: true)]
    private Collection $prices;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'parking')]
    private Collection $bookings;

    public function __construct()
    {
        $this->prices = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation($location): static
    {
        $this->location = $location;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * @return Collection<int, Price>
     */
    public function getPrices(): Collection
    {
        return $this->prices;
    }

    public function addPrice(Price $price): static
    {
        if (!$this->prices->contains($price)) {
            $this->prices->add($price);
            $price->setParking($this);
        }

        return $this;
    }

    public function removePrice(Price $price): static
    {
        if ($this->prices->removeElement($price)) {
            // set the owning side to null (unless already changed)
            if ($price->getParking() === $this) {
                $price->setParking(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setParking($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getParking() === $this) {
                $booking->setParking(null);
            }
        }

        return $this;
    }
}
