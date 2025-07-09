<?php declare(strict_types=1);

namespace CrossSelling\Core\Content\CrossSellingGroup;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class CrossSellingGroupEntity extends Entity
{
    use EntityIdTrait;

    protected string $categoryId;
    protected string $name;
    protected ?string $productStreamId;
    protected ?string $product1Id;
    protected ?string $product2Id;
    protected ?string $product3Id;
    protected int $position;

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function setCategoryId(string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getProductStreamId(): ?string
    {
        return $this->productStreamId;
    }

    public function setProductStreamId(?string $productStreamId): void
    {
        $this->productStreamId = $productStreamId;
    }

    public function getProduct1Id(): ?string
    {
        return $this->product1Id;
    }

    public function setProduct1Id(?string $product1Id): void
    {
        $this->product1Id = $product1Id;
    }

    public function getProduct2Id(): ?string
    {
        return $this->product2Id;
    }

    public function setProduct2Id(?string $product2Id): void
    {
        $this->product2Id = $product2Id;
    }

    public function getProduct3Id(): ?string
    {
        return $this->product3Id;
    }

    public function setProduct3Id(?string $product3Id): void
    {
        $this->product3Id = $product3Id;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
