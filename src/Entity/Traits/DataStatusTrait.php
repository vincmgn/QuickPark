<?php

namespace App\Entity\Traits;
use App\Types\DataStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait DataStatusTrait
{
    #[ORM\Column(type: 'string', length: 255, options: ['default' => 'active'])]
    #[Groups(["stats"])]
    private ?string $dataStatus = DataStatus::ACTIVE->value;

    public function getDataStatus(): string
    {
        return $this->dataStatus;
    }

    public function setDataStatus(string|DataStatus $dataStatus): static
    {
        $this->dataStatus = $dataStatus instanceof DataStatus ? $dataStatus->value : $dataStatus;
        return $this;
    }    
}
