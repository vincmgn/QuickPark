<?php

namespace App\Entity;

use App\Entity\Paiement;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CreditCardRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CreditCardRepository::class)]
#[Assert\Callback([self::class, 'validateExpirationDate'])]
class CreditCard
{
    use Traits\StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user_booking", "user", "credit_card"])]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 16,
        max: 16,
        exactMessage: 'The credit card number must be exactly {{ limit }} characters long'
    )]
    #[Assert\Regex(
        pattern: '/^\d+$/',
        message: 'The credit card number must contain only digits'
    )]
    #[Groups(["user_booking", "user", "credit_card"])]
    private ?string $number = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    // #[Assert\DateTime(format: 'Y-m-d H:i:s')]
    #[Groups(["user_booking", "user", "credit_card"])]
    private ?\DateTimeInterface $expirationDate = null;

    #[ORM\ManyToOne(inversedBy: 'creditCards')]
    #[ORM\JoinColumn(name: "owner_id", referencedColumnName: "id", nullable: false)]
    #[Groups(["credit_card"])]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    #[Groups(["credit_card"])]
    private ?string $ownerName = null;
    public static function validateExpirationDate(CreditCard $creditCard, ExecutionContextInterface $context): void
    {
        if ($creditCard->getExpirationDate() < new \DateTime()) {
            $context->buildViolation('The expiration date must be in the future')
                ->atPath('expirationDate')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?\DateTimeInterface $expirationDate): static
    {
        $this->expirationDate = $expirationDate;

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

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function setOwnerName(string $ownerName): static
    {
        $this->ownerName = $ownerName;

        return $this;
    }
}
