<?php declare(strict_types=1);
namespace CrossSelling\CrossSellingProducts\Migration;

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
  `category_version_id` BINARY(16) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `product_stream_id` BINARY(16) NULL,
  `product_stream_version_id` BINARY(16) NULL,
  `product1_id` BINARY(16) NULL,
  `product1_version_id` BINARY(16) NULL,
  `product2_id` BINARY(16) NULL,
  `product2_version_id` BINARY(16) NULL,
  `product3_id` BINARY(16) NULL,
  `product3_version_id` BINARY(16) NULL,
  `position` INT(11) NOT NULL,
  `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cross_selling_group.category` FOREIGN KEY (`category_id`, `category_version_id`)
    REFERENCES `category` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cross_selling_group.product1` FOREIGN KEY (`product1_id`, `product1_version_id`)
    REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  -- (Fremdschlüssel für product2, product3 analog)
  CONSTRAINT `fk_cross_selling_group.product_stream` FOREIGN KEY (`product_stream_id`, `product_stream_version_id`)
    REFERENCES `product_stream` (`id`, `version_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );
    }
}
