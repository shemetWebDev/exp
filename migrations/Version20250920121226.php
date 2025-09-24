<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250920121226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Guard migration for documents (no user_page changes).';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['documents'])) {
            $documents = $sm->introspectTable('documents');

            // created_at NOT NULL: если столбца нет — создаём; если есть и NULLable — заполним и сделаем NOT NULL
            if (!$documents->hasColumn('created_at')) {
                $this->addSql("ALTER TABLE documents ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'");
                $this->addSql("UPDATE documents SET created_at = NOW() WHERE created_at IS NULL");
                $this->addSql("ALTER TABLE documents MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
            } else {
                if ($documents->getColumn('created_at')->getNotnull() === false) {
                    $this->addSql("UPDATE documents SET created_at = NOW() WHERE created_at IS NULL");
                    $this->addSql("ALTER TABLE documents MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
                }
            }

            // article_id NOT NULL
            if ($documents->hasColumn('article_id') && $documents->getColumn('article_id')->getNotnull() === false) {
                $this->addSql('ALTER TABLE documents MODIFY article_id INT NOT NULL');
            }

            // type DEFAULT NULL
            if ($documents->hasColumn('type') && $documents->getColumn('type')->getNotnull() === true) {
                $this->addSql('ALTER TABLE documents MODIFY type VARCHAR(255) DEFAULT NULL');
            }

            // FK ON DELETE CASCADE
            $needToAddFk = true;
            $needToRecreateForCascade = false;
            foreach ($documents->getForeignKeys() as $fk) {
                if (strcasecmp($fk->getName(), 'FK_A2B072887294869C') === 0) {
                    $needToAddFk = false;
                    $onDelete = method_exists($fk, 'onDelete') ? $fk->onDelete() : ($fk->getOption('onDelete') ?? null);
                    if (strtoupper((string)$onDelete) !== 'CASCADE') {
                        $needToRecreateForCascade = true;
                    }
                    break;
                }
            }
            if ($needToRecreateForCascade) {
                $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B072887294869C');
                $needToAddFk = true;
            }
            if ($needToAddFk) {
                $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B072887294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE');
            }
        }

        /* user_page — НЕ ТРОГАЕМ */
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if ($sm->tablesExist(['documents'])) {
            $documents = $sm->introspectTable('documents');

            // FK назад в NO ACTION (как делает авто-генератор)
            foreach ($documents->getForeignKeys() as $fk) {
                if (strcasecmp($fk->getName(), 'FK_A2B072887294869C') === 0) {
                    $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B072887294869C');
                    break;
                }
            }
            $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B072887294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

            // created_at можно дропнуть, только если есть
            if ($documents->hasColumn('created_at')) {
                $this->addSql('ALTER TABLE documents DROP COLUMN created_at');
            }

            // вернём article_id NULLable и type NOT NULL — если надо
            if ($documents->hasColumn('article_id') && $documents->getColumn('article_id')->getNotnull() === true) {
                $this->addSql('ALTER TABLE documents MODIFY article_id INT DEFAULT NULL');
            }
            if ($documents->hasColumn('type') && $documents->getColumn('type')->getNotnull() === false) {
                $this->addSql('ALTER TABLE documents MODIFY type VARCHAR(255) NOT NULL');
            }
        }

        /* user_page — без изменений */
    }
}
