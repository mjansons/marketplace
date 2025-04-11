<?php

namespace App\Entity;

use App\Repository\CameraRepository;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: CameraRepository::class)]
class Camera extends BaseProduct
{
    #[ORM\Column(length: 100)]
    private ?string $brand = null;

    #[ORM\Column(length: 150)]
    private ?string $model = null;

    #[ORM\Column(name: "product_condition", type: "string", length: 100)]
    private string $productCondition;

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getProductCondition(): string
    {
        return $this->productCondition;
    }

    public function setProductCondition(?string $condition): static
    {
        $this->productCondition = $condition;
        return $this;
    }

    public function getType(): string
    {
        return 'camera';
    }
}
