<?php declare(strict_types=1);

namespace CrossSelling\Core\Content\CrossSellingGroup;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class CrossSellingGroupCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CrossSellingGroupEntity::class;
    }
}
