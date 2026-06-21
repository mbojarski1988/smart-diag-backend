<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create licenses table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE licenses (
            id SERIAL NOT NULL,
            license_key VARCHAR(80) NOT NULL,
            client_name VARCHAR(255) NOT NULL,
            client_email VARCHAR(255) NOT NULL,
            note TEXT DEFAULT NULL,
            active BOOLEAN NOT NULL,
            valid_until TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            deactivated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_license_key ON licenses (license_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE licenses');
    }
}
