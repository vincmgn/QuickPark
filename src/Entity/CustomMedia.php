<?php

namespace App\Entity;

use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CustomMediaRepository;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;


#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: CustomMediaRepository::class)]
class CustomMedia
{
    use Traits\StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["media"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["media"])]
    private ?string $realname = null;

    #[ORM\Column(length: 255)]
    #[Groups(["media"])]
    private ?string $realPath = null;

    #[ORM\Column(length: 255)]
    #[Groups(["media"])]
    private ?string $publicPath = null;

    #[Vich\UploadableField(mapping: 'medias', fileNameProperty: 'realname', originalName: 'realPath')]
    #[OA\Property(type: "string", format: "binary")]
    private ?File $media = null;

    public function getMedia(): ?File
    {
        return $this->media;
    }

    public function setMedia(?File $media): self
    {
        $this->media = $media;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRealname(): ?string
    {
        return $this->realname;
    }

    public function setRealname(string $realname): static
    {
        $this->realname = $realname;

        return $this;
    }

    public function getRealPath(): ?string
    {
        return $this->realPath;
    }

    public function setRealPath(string $realPath): static
    {
        $this->realPath = $realPath;

        return $this;
    }

    public function getPublicPath(): ?string
    {
        return $this->publicPath;
    }

    public function setPublicPath(string $publicPath): static
    {
        $this->publicPath = $publicPath;

        return $this;
    }
}
