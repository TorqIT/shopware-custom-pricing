<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Log\Package;

#[Package('core')] // Adjust package as needed
class Migration1749234000CreateTorqCustomPriceCustomDataTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1749234000; // Replace with actual timestamp if needed for ordering
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `torq_custom_price_custom_data` (
            `id` BINARY(16) NOT NULL,
            `custom_price_id` BINARY(16) NOT NULL,
            `custom_fields` JSON NULL DEFAULT NULL,
            `price` json NOT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `json.torq_custom_price_custom_data.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
            CONSTRAINT `fk.torq_custom_price_custom_data.custom_price_id` FOREIGN KEY (`custom_price_id`)
                REFERENCES `custom_price` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}