<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ParkingRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use Symfony\Component\Serializer\Annotation\Groups;
use LongitudeOne\Spatial\PHP\Types\SpatialInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ParkingRepository::class)]
#[Assert\Callback([self::class, 'validateLocation'])]
class Parking
{
    use Traits\StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["booking", "parking", "user_booking"])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(["booking", "parking"])]
    private ?bool $isEnabled = null;

    /**
     * @var Collection<int, Price>
     */
    #[ORM\OneToMany(targetEntity: Price::class, mappedBy: 'parking', orphanRemoval: true)]
    #[Groups(["parking"])]
    private Collection $prices;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'parking')]
    private Collection $bookings;

    #[ORM\Column(length: 255)]
    #[Groups(["parking", "user_booking"])]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 3,
        max: 25,
        minMessage: 'The parking name must be at least {{ limit }} characters long',
        maxMessage: 'The parking name cannot be longer than {{ limit }} characters'
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["parking"])]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 10,
        minMessage: 'The parking description must be at least {{ limit }} characters long'
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'geography')]
    #[Assert\NotNull]
    #[Assert\Type(type: SpatialInterface::class, message: 'The location must be a valid spatial object')]
    #[Groups(["parking", "user_booking"])]
    private ?SpatialInterface $location = null;

    #[ORM\ManyToOne(inversedBy: 'parkings')]
    #[ORM\JoinColumn(name: "owner_id", referencedColumnName: "id", nullable: false)]
    private ?User $owner = null;

    public static function validateLocation(self $object, ExecutionContextInterface $context): void
    {
        if ($object->location instanceof Point) {
            $longitude = $object->location->getLongitude();
            $latitude = $object->location->getLatitude();
            if ($latitude < -90 || $latitude > 90) {
                $context->buildViolation('The latitude must be between -90 and 90.')
                    ->atPath('location')
                    ->addViolation();
            }

            if ($longitude < -180 || $longitude > 180) {
                $context->buildViolation('The longitude must be between -180 and 180.')
                    ->atPath('location')
                    ->addViolation();
            }
        }
    }

    public function __construct()
    {
        $this->prices = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLocation(): ?SpatialInterface
    {
        return $this->location;
    }

    public function setLocation(SpatialInterface $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }
}
