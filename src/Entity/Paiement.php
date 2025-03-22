<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\PaiementRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\Loggable]
class Paiement
{
    use Traits\StatisticsPropertiesTrait;
    use Traits\DataStatusTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user_booking", "paiement"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[ORM\JoinColumn(name: "credit_card_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    #[Groups(["user_booking", "paiement"])]
    private ?CreditCard $creditCard = null;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    #[ORM\JoinColumn(name: "status_id", nullable: false)]
    #[Gedmo\Versioned]
    private ?Status $status = null;

    // #[ORM\Column(length: 16)]
    // #[Assert\NotBlank]
    // #[Assert\NotNull]
    // #[Assert\Length(
    //     min: 16,
    //     max: 16,
    //     exactMessage: 'The credit card number must be exactly {{ limit }} characters long'
    // )]
    // #[Assert\Regex(
    //     pattern: '/^\d+$/',
    //     message: 'The credit card number must contain only digits'
    // )]
    // #[Groups(["user_booking", "paiement"])]
    // private ?string $creditCardNumber = null;

    #[ORM\Column]
    #[Groups(["booking", "user_booking", "paiement"])]
    #[Assert\NotNull]
    #[Assert\Type('float')]
    #[Assert\GreaterThan(0, message: "The total price must be greater than 0.")]
    private ?float $totalPrice = null;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'paiement')]
    private Collection $booking;

    public function __construct()
    {
        $this->booking = new ArrayCollection();
    }

    #[Groups(["paiement"])]
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreditCard(): ?CreditCard
    {
        return $this->creditCard;
    }

    public function setCreditCard(?CreditCard $creditCard): static
    {
        $this->creditCard = $creditCard;

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

    // public function getCreditCardNumber(): ?string
    // {
    //     return $this->creditCardNumber;
    // }

    // public function setCreditCardNumber(string $creditCardNumber): static
    // {
    //     $this->creditCardNumber = $creditCardNumber;

    //     return $this;
    // }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBooking(): Collection
    {
        return $this->booking;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->booking->contains($booking)) {
            $this->booking->add($booking);
            $booking->setPaiement($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->booking->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getPaiement() === $this) {
                $booking->setPaiement(null);
            }
        }

        return $this;
    }
}
