<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250326204248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, coordonees JSON NOT NULL, page INT NOT NULL, type_livrable_id INT DEFAULT NULL, INDEX IDX_A0EBC0079BA909D5 (type_livrable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_A0EBC0079BA909D5 FOREIGN KEY (type_livrable_id) REFERENCES type_livrable (id)');
        $this->addSql('ALTER TABLE champs DROP FOREIGN KEY FK_B34671BE9BA909D5');
        $this->addSql('DROP INDEX IDX_B34671BE9BA909D5 ON champs');
        $this->addSql('ALTER TABLE champs ADD question VARCHAR(255) NOT NULL, DROP type_livrable_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE zone DROP FOREIGN KEY FK_A0EBC0079BA909D5');
        $this->addSql('DROP TABLE zone');
        $this->addSql('ALTER TABLE champs ADD type_livrable_id INT DEFAULT NULL, DROP question');
        $this->addSql('ALTER TABLE champs ADD CONSTRAINT FK_B34671BE9BA909D5 FOREIGN KEY (type_livrable_id) REFERENCES type_livrable (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_B34671BE9BA909D5 ON champs (type_livrable_id)');
    }
}
