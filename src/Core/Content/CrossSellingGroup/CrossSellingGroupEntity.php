<?php declare(strict_types=1);

namespace CrossSelling\Core\Content\CrossSellingGroup;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\ProductStream\ProductStreamEntity;

class CrossSellingGroupEntity extends Entity
{
    use EntityIdTrait;

    protected string $categoryId;
    protected ?CategoryEntity $category = null;

    protected string $name;

    protected ?string $productStreamId = null;
    protected ?ProductStreamEntity $productStream = null;

    protected ?array $productIds = null;

    protected int $position;

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function setCategoryId(string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getCategory(): ?CategoryEntity
    {
        return $this->category;
    }

    public function setCategory(?CategoryEntity $category): void
    {
        $this->category = $category;
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

    public function getProductStream(): ?ProductStreamEntity
    {
        return $this->productStream;
    }

    public function setProductStream(?ProductStreamEntity $productStream): void
    {
        $this->productStream = $productStream;
    }

    public function getProductIds(): ?array
    {
        return $this->productIds;
    }

    public function setProductIds(?array $productIds): void
    {
        $this->productIds = $productIds;
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
