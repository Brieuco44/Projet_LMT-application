<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503120109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE statut (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE controle ADD statut_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE controle ADD CONSTRAINT FK_E39396EF6203804 FOREIGN KEY (statut_id) REFERENCES statut (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E39396EF6203804 ON controle (statut_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document ADD statut_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document ADD CONSTRAINT FK_D8698A76F6203804 FOREIGN KEY (statut_id) REFERENCES statut (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D8698A76F6203804 ON document (statut_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE statut
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document DROP FOREIGN KEY FK_D8698A76F6203804
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D8698A76F6203804 ON document
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE document DROP statut_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE controle DROP FOREIGN KEY FK_E39396EF6203804
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_E39396EF6203804 ON controle
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE controle DROP statut_id
        SQL);
    }
}
