<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240919150925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_frozen column to user table and set default value to false';
    }

    public function up(Schema $schema): void
    {
        // Add is_frozen column and set default value to false for all existing users
        $this->addSql('ALTER TABLE user ADD is_frozen TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('UPDATE user SET is_frozen = 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP is_frozen');
    }
}
