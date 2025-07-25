<?php declare(strict_types=1);

namespace CrossSelling\Core\Content\CrossSellingGroup;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\ProductStream\ProductStreamDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CrossSellingGroupDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cross_selling_product_group';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CrossSellingGroupEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('category_id', 'categoryId', CategoryDefinition::class))->addFlags(new Required()),
            (new ManyToOneAssociationField('category', 'category_id', CategoryDefinition::class, 'id', false)),

            (new StringField('name', 'name'))->addFlags(new Required()),

            (new FkField('product_stream_id', 'productStreamId', ProductStreamDefinition::class)),
            (new ManyToOneAssociationField('productStream', 'product_stream_id', ProductStreamDefinition::class, 'id', false)),

            (new FkField('product1_id', 'product1Id', ProductDefinition::class)),
            (new ManyToOneAssociationField('product1', 'product1_id', ProductDefinition::class, 'id', false)),

            (new FkField('product2_id', 'product2Id', ProductDefinition::class)),
            (new ManyToOneAssociationField('product2', 'product2_id', ProductDefinition::class, 'id', false)),

            (new FkField('product3_id', 'product3Id', ProductDefinition::class)),
            (new ManyToOneAssociationField('product3', 'product3_id', ProductDefinition::class, 'id', false)),

            (new IntField('position', 'position'))->addFlags(new Required()),

            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
