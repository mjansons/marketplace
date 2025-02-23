<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250222093906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE car CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE car ADD CONSTRAINT FK_773DE69DBF396750 FOREIGN KEY (id) REFERENCES base_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE computer CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE computer ADD CONSTRAINT FK_A298A7A6BF396750 FOREIGN KEY (id) REFERENCES base_product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE phone CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE phone ADD CONSTRAINT FK_444F97DDBF396750 FOREIGN KEY (id) REFERENCES base_product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phone DROP FOREIGN KEY FK_444F97DDBF396750');
        $this->addSql('ALTER TABLE phone CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE car DROP FOREIGN KEY FK_773DE69DBF396750');
        $this->addSql('ALTER TABLE car CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE computer DROP FOREIGN KEY FK_A298A7A6BF396750');
        $this->addSql('ALTER TABLE computer CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }
}
