<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240912095514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add coingecko_id to cryptocurrency table and populate it';
    }

    public function up(Schema $schema): void
    {
        // Add coingecko_id column
        $this->addSql('ALTER TABLE cryptocurrency ADD coingecko_id VARCHAR(40) NOT NULL, CHANGE code symbol VARCHAR(5) NOT NULL');

        // Populate coingecko_id for existing entries
        $this->addSql('UPDATE cryptocurrency SET coingecko_id = LOWER(REPLACE(name, " ", "_"))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cryptocurrency DROP coingecko_id, CHANGE symbol code VARCHAR(5) NOT NULL');
    }
}
