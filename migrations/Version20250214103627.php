<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214103627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_page (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(200) NOT NULL, keywords LONGTEXT NOT NULL, subtitle VARCHAR(255) NOT NULL, banner_img VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, advantage_one VARCHAR(255) NOT NULL, advantage_twoo VARCHAR(255) NOT NULL, advantage_three VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, adress VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, company_name VARCHAR(255) DEFAULT NULL, map_position LONGTEXT NOT NULL, INDEX IDX_6E8BFAE9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_page ADD CONSTRAINT FK_6E8BFAE9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_page DROP FOREIGN KEY FK_6E8BFAE9A76ED395');
        $this->addSql('DROP TABLE user_page');
    }
}
