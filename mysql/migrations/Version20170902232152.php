<?php

namespace Sample\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170902232152 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE dormatory_audits (audit_id INT NOT NULL, dormatory_id INT NOT NULL, INDEX IDX_B313D871BD29F359 (audit_id), INDEX IDX_B313D8713DDC69B9 (dormatory_id), PRIMARY KEY(audit_id, dormatory_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audits (id INT AUTO_INCREMENT NOT NULL, number INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dormatory_audits ADD CONSTRAINT FK_B313D871BD29F359 FOREIGN KEY (audit_id) REFERENCES dormatories (id)');
        $this->addSql('ALTER TABLE dormatory_audits ADD CONSTRAINT FK_B313D8713DDC69B9 FOREIGN KEY (dormatory_id) REFERENCES audits (id)');
        $this->addSql('ALTER TABLE dormatories ADD title VARCHAR(35) NOT NULL, ADD updated DATETIME NOT NULL, ADD content LONGTEXT NOT NULL, DROP number');
        $this->addSql('ALTER TABLE students DROP FOREIGN KEY FK_A4698DB23DDC69B9');
        $this->addSql('ALTER TABLE students ADD CONSTRAINT FK_A4698DB23DDC69B9 FOREIGN KEY (dormatory_id) REFERENCES audits (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dormatory_audits DROP FOREIGN KEY FK_B313D8713DDC69B9');
        $this->addSql('ALTER TABLE students DROP FOREIGN KEY FK_A4698DB23DDC69B9');
        $this->addSql('DROP TABLE dormatory_audits');
        $this->addSql('DROP TABLE audits');
        $this->addSql('ALTER TABLE dormatories ADD number INT NOT NULL, DROP title, DROP updated, DROP content');
        $this->addSql('ALTER TABLE students DROP FOREIGN KEY FK_A4698DB23DDC69B9');
        $this->addSql('ALTER TABLE students ADD CONSTRAINT FK_A4698DB23DDC69B9 FOREIGN KEY (dormatory_id) REFERENCES dormatories (id)');
    }
}
