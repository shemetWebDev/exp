<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826145416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ads_like (id INT AUTO_INCREMENT NOT NULL, ads_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BC48AF60FE52BF81 (ads_id), INDEX IDX_BC48AF60A76ED395 (user_id), UNIQUE INDEX uniq_ads_user (ads_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ads_like ADD CONSTRAINT FK_BC48AF60FE52BF81 FOREIGN KEY (ads_id) REFERENCES ads (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ads_like ADD CONSTRAINT FK_BC48AF60A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ads_like DROP FOREIGN KEY FK_BC48AF60FE52BF81');
        $this->addSql('ALTER TABLE ads_like DROP FOREIGN KEY FK_BC48AF60A76ED395');
        $this->addSql('DROP TABLE ads_like');
    }
}
