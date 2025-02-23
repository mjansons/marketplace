<?php

namespace App\Entity;

use App\Repository\ComputerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComputerRepository::class)]
class Computer extends BaseProduct
{

    #[ORM\Column(length: 150)]
    private ?string $brand = null;

    #[ORM\Column(length: 150)]
    private ?string $model = null;

    #[ORM\Column]
    private ?int $ram = null;

    #[ORM\Column]
    private ?int $storage = null;

    #[ORM\Column(name: "product_condition", type: "string", length: 100)] // Store as a string
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

    public function getRam(): ?int
    {
        return $this->ram;
    }

    public function setRam(int $ram): static
    {
        $this->ram = $ram;

        return $this;
    }

    public function getStorage(): ?int
    {
        return $this->storage;
    }

    public function setStorage(int $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function getProductCondition(): ?string
    {
        return $this->productCondition;
    }
    public function setProductCondition(?string $condition): static
    {
        $this->productCondition = $condition;
        return $this;
    }
}
