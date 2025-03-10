<?php

namespace App\Entity;

use App\Entity\Paiement;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CreditCardRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
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
    private ?string $number = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $expirationDate = null;
    public static function validateExpirationDate(CreditCard $creditCard, ExecutionContextInterface $context): void
    {
        if ($creditCard->getExpirationDate() < new \DateTime()) {
            $context->buildViolation('The expiration date must be in the future')
                ->atPath('expirationDate')
                ->addViolation();
        }
    }

    /**
     * @var Collection<int, Paiement>
     */
    #[ORM\OneToMany(targetEntity: Paiement::class, mappedBy: 'creditCard')]
    private Collection $paiements;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
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

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setCreditCard($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getCreditCard() === $this) {
                $paiement->setCreditCard(null);
            }
        }

        return $this;
    }
}
