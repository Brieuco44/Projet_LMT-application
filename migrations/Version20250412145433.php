<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250412145433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE champs ADD zone_id INT DEFAULT NULL, DROP zone');
        $this->addSql('ALTER TABLE champs ADD CONSTRAINT FK_B34671BE9F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('CREATE INDEX IDX_B34671BE9F2C3FAB ON champs (zone_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE champs DROP FOREIGN KEY FK_B34671BE9F2C3FAB');
        $this->addSql('DROP INDEX IDX_B34671BE9F2C3FAB ON champs');
        $this->addSql('ALTER TABLE champs ADD zone JSON NOT NULL, DROP zone_id');
    }
}
