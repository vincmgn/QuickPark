<?php

namespace App\Entity;

use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_UUID', fields: ['id'])]
#[Gedmo\Loggable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use Traits\StatisticsPropertiesTrait;
    use Traits\DataStatusTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[OA\Property(type: "string", format: "uuid", description: "The unique identifier of the user")]
    #[Groups(["user", 'credit_card'])]
    private ?Uuid $id = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Gedmo\Versioned]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(["user", 'credit_card'])]
    #[Gedmo\Versioned]
    private ?string $username = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(["user"])]
    #[Gedmo\Versioned]
    private ?string $profilePicture = null;

    /**
     * @var Collection<int, Parking>
     */
    #[ORM\OneToMany(targetEntity: Parking::class, mappedBy: 'owner')]
    private Collection $parkings;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'client')]
    private Collection $bookings;

    /**
     * @var Collection<int, CreditCard>
     */
    #[ORM\OneToMany(targetEntity: CreditCard::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $creditCards;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[Groups(["user"])]
    #[Gedmo\Versioned]
    private ?Gender $gender = null;

    #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'])]
    #[Groups(["user"])]
    #[Gedmo\Versioned]
    private ?Phone $phone = null;

    #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'])]
    #[Groups(["user"])]
    #[Gedmo\Versioned]
    private ?Email $email = null;

    public function __construct()
    {
        $this->id = $this->uuid ?? Uuid::v4();
        $this->parkings = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->creditCards = new ArrayCollection();
    }


    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $uuid): static
    {
        $this->id = $uuid;

        return $this;
    }


    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    // Getters & Setters
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * @return Collection<int, Parking>
     */
    public function getParkings(): Collection
    {
        return $this->parkings;
    }

    public function addParking(Parking $parking): static
    {
        if (!$this->parkings->contains($parking)) {
            $this->parkings->add($parking);
            $parking->setOwner($this);
        }

        return $this;
    }

    public function removeParking(Parking $parking): static
    {
        if ($this->parkings->removeElement($parking)) {
            // set the owning side to null (unless already changed)
            if ($parking->getOwner() === $this) {
                $parking->setOwner(null);
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
            $booking->setClient($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getClient() === $this) {
                $booking->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CreditCard>
     */
    public function getCreditCards(): Collection
    {
        return $this->creditCards;
    }

    public function addCreditCard(CreditCard $creditCard): static
    {
        if (!$this->creditCards->contains($creditCard)) {
            $this->creditCards->add($creditCard);
            $creditCard->setOwner($this);
        }

        return $this;
    }

    public function removeCreditCard(CreditCard $creditCard): static
    {
        if ($this->creditCards->removeElement($creditCard)) {
            // set the owning side to null (unless already changed)
            if ($creditCard->getOwner() === $this) {
                $creditCard->setOwner(null);
            }
        }

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getPhone(): ?Phone
    {
        return $this->phone;
    }

    public function setPhone(Phone $phone): static
    {
        // set the owning side of the relation if necessary
        if ($phone->getOwner() !== $this) {
            $phone->setOwner($this);
        }

        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(Email $email): static
    {
        // set the owning side of the relation if necessary
        if ($email->getOwner() !== $this) {
            $email->setOwner($this);
        }

        $this->email = $email;

        return $this;
    }
}
