<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PhoneRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PhoneRepository::class)]
class Phone
{
    use Traits\StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['phone'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 10,
        max: 25,
        minMessage: 'The phone number must be at least {{ limit }} characters long',
        maxMessage: 'The phone number cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^\+?[0-9\s\-]{10,25}$/',
        message: 'The phone number is not valid'
    )]
    private ?string $number = null;

    #[ORM\ManyToOne(inversedBy: 'phones')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['phone'])]
    private ?Status $status = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['phone'])]
    #[Assert\Type("\DateTimeImmutable", message: "La date de vérification doit être une date valide")]
    private ?\DateTimeImmutable $verifiedAt = null;

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

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }
}
