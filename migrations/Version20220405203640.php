<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220405203640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE actor ADD name VARCHAR(100) NOT NULL, DROP lastname, DROP firstname');
        $this->addSql('ALTER TABLE director ADD name VARCHAR(100) NOT NULL, DROP lastname, DROP firstname');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE actor ADD lastname VARCHAR(50) NOT NULL, ADD firstname VARCHAR(50) NOT NULL, DROP name');
        $this->addSql('ALTER TABLE director ADD lastname VARCHAR(50) NOT NULL, ADD firstname VARCHAR(50) NOT NULL, DROP name');
    }
}
