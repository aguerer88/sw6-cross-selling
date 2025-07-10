<?php declare(strict_types=1);
namespace CrossSelling\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1623456789CreateCrossSellingGroupTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1623456789;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
CREATE TABLE IF NOT EXISTS `cross_selling_product_group` (
    `id` BINARY(16) NOT NULL,
    `category_id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `product_stream_id` BINARY(16) NULL,
    `position` INT(11) NOT NULL DEFAULT 0,
    `product_ids` JSON NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,

    PRIMARY KEY (`id`),

    CONSTRAINT `fk_cross_selling_group.product_stream`
        FOREIGN KEY (`product_stream_id`)
        REFERENCES `product_stream` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,

    CHECK (JSON_VALID(`product_ids`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }
}
