<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250315214814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE champs (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, zone JSON NOT NULL, page INT NOT NULL, type_champs_id INT DEFAULT NULL, type_livrable_id INT DEFAULT NULL, INDEX IDX_B34671BEB4DCFB9B (type_champs_id), INDEX IDX_B34671BE9BA909D5 (type_livrable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE controle (id INT AUTO_INCREMENT NOT NULL, resultat TINYINT(1) NOT NULL, document_id INT DEFAULT NULL, champs_id INT DEFAULT NULL, INDEX IDX_E39396EC33F7837 (document_id), INDEX IDX_E39396E1ABA8B (champs_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, type_livrable_id INT DEFAULT NULL, INDEX IDX_D8698A769BA909D5 (type_livrable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE type_champs (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE type_livrable (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE champs ADD CONSTRAINT FK_B34671BEB4DCFB9B FOREIGN KEY (type_champs_id) REFERENCES type_champs (id)');
        $this->addSql('ALTER TABLE champs ADD CONSTRAINT FK_B34671BE9BA909D5 FOREIGN KEY (type_livrable_id) REFERENCES type_livrable (id)');
        $this->addSql('ALTER TABLE controle ADD CONSTRAINT FK_E39396EC33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE controle ADD CONSTRAINT FK_E39396E1ABA8B FOREIGN KEY (champs_id) REFERENCES champs (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A769BA909D5 FOREIGN KEY (type_livrable_id) REFERENCES type_livrable (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE champs DROP FOREIGN KEY FK_B34671BEB4DCFB9B');
        $this->addSql('ALTER TABLE champs DROP FOREIGN KEY FK_B34671BE9BA909D5');
        $this->addSql('ALTER TABLE controle DROP FOREIGN KEY FK_E39396EC33F7837');
        $this->addSql('ALTER TABLE controle DROP FOREIGN KEY FK_E39396E1ABA8B');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A769BA909D5');
        $this->addSql('DROP TABLE champs');
        $this->addSql('DROP TABLE controle');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE type_champs');
        $this->addSql('DROP TABLE type_livrable');
        $this->addSql('DROP TABLE utilisateur');
    }
}
