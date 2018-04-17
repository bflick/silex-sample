<?php declare(strict_types = 1);

namespace Sample\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180417052818 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE process_event (id INT AUTO_INCREMENT NOT NULL, accessed DATETIME NOT NULL, pid INT NOT NULL, securePointer VARCHAR(50) NOT NULL, discr VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE check_event (id INT NOT NULL, exited TINYINT(1) NOT NULL, exitCode INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collect_event (id INT NOT NULL, output LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE input_event (id INT NOT NULL, input VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE start_event (id INT NOT NULL, started DATETIME NOT NULL, success TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE check_event ADD CONSTRAINT FK_A1F43965BF396750 FOREIGN KEY (id) REFERENCES process_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collect_event ADD CONSTRAINT FK_277D3379BF396750 FOREIGN KEY (id) REFERENCES process_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE input_event ADD CONSTRAINT FK_8350C213BF396750 FOREIGN KEY (id) REFERENCES process_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE start_event ADD CONSTRAINT FK_4EFB1056BF396750 FOREIGN KEY (id) REFERENCES process_event (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE check_event DROP FOREIGN KEY FK_A1F43965BF396750');
        $this->addSql('ALTER TABLE collect_event DROP FOREIGN KEY FK_277D3379BF396750');
        $this->addSql('ALTER TABLE input_event DROP FOREIGN KEY FK_8350C213BF396750');
        $this->addSql('ALTER TABLE start_event DROP FOREIGN KEY FK_4EFB1056BF396750');
        $this->addSql('DROP TABLE process_event');
        $this->addSql('DROP TABLE check_event');
        $this->addSql('DROP TABLE collect_event');
        $this->addSql('DROP TABLE input_event');
        $this->addSql('DROP TABLE start_event');
    }
}
