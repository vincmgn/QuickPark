<?php

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\DataStatus;
use App\DBAL\Types\DataStatusType;
use Symfony\Component\Serializer\Annotation\Groups;

trait StatisticsPropertiesTrait
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["stats"])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["stats"])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: DataStatusType::DATASTATUS, options: ['default' => DataStatus::ACTIVE])]
    #[Groups(["stats"])]
    private DataStatus $dataStatus = DataStatus::ACTIVE;

    public function getDataStatus(): DataStatus
    {
        return $this->dataStatus;
    }

    public function setDataStatus(DataStatus $dataStatus): static
    {
        $this->dataStatus = $dataStatus;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        if (is_null($this->getCreatedAt())) {
            $this->createdAt = $createdAt;
        }

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $this->setCreatedAt(new \DateTime());
        $this->setUpdatedAt(new \DateTime());
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->setUpdatedAt(new \DateTime());
    }
}
