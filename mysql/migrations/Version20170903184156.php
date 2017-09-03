<?php

namespace Sample\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170903184156 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE students ADD bedroom_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE students ADD CONSTRAINT FK_A4698DB2BDB6797C FOREIGN KEY (bedroom_id) REFERENCES bedrooms (id)');
        $this->addSql('CREATE INDEX IDX_A4698DB2BDB6797C ON students (bedroom_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE students DROP FOREIGN KEY FK_A4698DB2BDB6797C');
        $this->addSql('DROP INDEX IDX_A4698DB2BDB6797C ON students');
        $this->addSql('ALTER TABLE students DROP bedroom_id');
    }
}
