<?php

namespace Sample\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170901222834 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bedrooms (id INT AUTO_INCREMENT NOT NULL, student_id INT DEFAULT NULL, dormatory_id INT NOT NULL, floor INT NOT NULL, number INT NOT NULL, UNIQUE INDEX UNIQ_784BB2ACB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dormatories (id INT AUTO_INCREMENT NOT NULL, number INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE students (id INT AUTO_INCREMENT NOT NULL, dormatory_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, gender VARCHAR(255) NOT NULL, student_id VARCHAR(255) NOT NULL, date_of_birth DATE NOT NULL, phone VARCHAR(255) NOT NULL, INDEX IDX_A4698DB23DDC69B9 (dormatory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bedrooms ADD CONSTRAINT FK_784BB2ACB944F1A FOREIGN KEY (student_id) REFERENCES students (id)');
        $this->addSql('ALTER TABLE students ADD CONSTRAINT FK_A4698DB23DDC69B9 FOREIGN KEY (dormatory_id) REFERENCES dormatories (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE students DROP FOREIGN KEY FK_A4698DB23DDC69B9');
        $this->addSql('ALTER TABLE bedrooms DROP FOREIGN KEY FK_784BB2ACB944F1A');
        $this->addSql('DROP TABLE bedrooms');
        $this->addSql('DROP TABLE dormatories');
        $this->addSql('DROP TABLE students');
    }
}
