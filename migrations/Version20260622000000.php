<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ai_prompts table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ai_prompts (
            id SERIAL NOT NULL,
            name VARCHAR(255) NOT NULL,
            prompt TEXT NOT NULL,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ai_prompts');
    }
}
