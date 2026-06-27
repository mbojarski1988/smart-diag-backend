<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260627000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create known_pids table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE known_pids (
            id SERIAL NOT NULL,
            model VARCHAR(120) NOT NULL,
            pid VARCHAR(40) NOT NULL,
            name VARCHAR(255) NOT NULL,
            unit VARCHAR(40) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            active BOOLEAN NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_known_pid_model_pid ON known_pids (model, pid)');
        $this->addSql('CREATE INDEX idx_known_pids_model ON known_pids (model)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE known_pids');
    }
}
