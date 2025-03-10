<?php

namespace App\Entity;

use App\Repository\CreditCardRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
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
    #[Assert\NotBlank]
    #[Assert\NotNull]
    // #[Assert\DateTime(format: 'Y-m-d H:i:s')]
    private ?\DateTimeInterface $expirationDate = null;
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
}
