<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250918111155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_paid (bool), created_at (datetime_immutable), trial_ends_at (datetime_immutable nullable) to user_page with safe defaults';
    }

    public function up(Schema $schema): void
    {
        // 1) Добавляем поля с дефолтами, чтобы не упасть на существующих строках
        // Важно: используем DEFAULT CURRENT_TIMESTAMP для created_at и DEFAULT NULL для trial_ends_at
        $this->addSql("ALTER TABLE user_page 
            ADD is_paid TINYINT(1) NOT NULL DEFAULT 0,
            ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ADD trial_ends_at DATETIME DEFAULT NULL
        ");

        // 2) Для уже существующих строк выставим trial_ends_at = created_at + 48 часов
        // (Если хочешь 24 часа — поменяй INTERVAL 48 HOUR на INTERVAL 24 HOUR)
        $this->addSql("UPDATE user_page SET trial_ends_at = DATE_ADD(created_at, INTERVAL 48 HOUR) WHERE trial_ends_at IS NULL");

        // 3) (Необязательно) Ужесточение: убрать дефолт created_at, если хочешь чтобы задавалось только приложением
        // Комментарий: это безопасно, т.к. все существующие строки уже имеют created_at.
        // Закомментируй следующую строку, если хочешь оставить дефолт на уровне БД.
        $this->addSql("ALTER TABLE user_page MODIFY created_at DATETIME NOT NULL");
    }

    public function down(Schema $schema): void
    {
        // Откат: просто удаляем добавленные колонки
        $this->addSql("ALTER TABLE user_page 
            DROP COLUMN trial_ends_at,
            DROP COLUMN created_at,
            DROP COLUMN is_paid
        ");
    }
}
