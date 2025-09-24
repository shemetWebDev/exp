<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250920120157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Safe changes for articles/documents only (no user_page touches).';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        /* ---------- articles ---------- */
        if ($sm->tablesExist(['articles'])) {
            $articles = $sm->introspectTable('articles');

            // updated_at
            if (!$articles->hasColumn('updated_at')) {
                $this->addSql("ALTER TABLE articles ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            }

            // index on create_at
            $hasIdx = false;
            foreach ($articles->getIndexes() as $idx) {
                if (strcasecmp($idx->getName(), 'idx_articles_created') === 0) {
                    $hasIdx = true;
                    break;
                }
            }
            if (!$hasIdx) {
                $this->addSql('CREATE INDEX idx_articles_created ON articles (create_at)');
            }
        }

        /* ---------- documents ---------- */
        if ($sm->tablesExist(['documents'])) {
            $documents = $sm->introspectTable('documents');

            if (!$documents->hasColumn('original_name')) {
                $this->addSql('ALTER TABLE documents ADD original_name VARCHAR(255) DEFAULT NULL');
            }
            if (!$documents->hasColumn('size')) {
                $this->addSql('ALTER TABLE documents ADD size INT DEFAULT NULL');
            }
            if (!$documents->hasColumn('created_at')) {
                // добавляем как NULL+DEFAULT -> заполняем -> делаем NOT NULL
                $this->addSql("ALTER TABLE documents ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)'");
                $this->addSql("UPDATE documents SET created_at = NOW() WHERE created_at IS NULL");
                $this->addSql("ALTER TABLE documents MODIFY created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
            }

            // article_id NOT NULL (если вдруг ещё NULLable)
            if ($documents->hasColumn('article_id') && $documents->getColumn('article_id')->getNotnull() === false) {
                $this->addSql('ALTER TABLE documents MODIFY article_id INT NOT NULL');
            }

            // type DEFAULT NULL (если вдруг NOT NULL)
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

        /* НИЧЕГО НЕ ДЕЛАЕМ с `user_page` */
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        /* ---------- articles ---------- */
        if ($sm->tablesExist(['articles'])) {
            $articles = $sm->introspectTable('articles');
            if ($articles->hasColumn('updated_at')) {
                $this->addSql('ALTER TABLE articles DROP COLUMN updated_at');
            }
            foreach ($articles->getIndexes() as $idx) {
                if (strcasecmp($idx->getName(), 'idx_articles_created') === 0) {
                    $this->addSql('DROP INDEX idx_articles_created ON articles');
                    break;
                }
            }
        }

        /* ---------- documents ---------- */
        if ($sm->tablesExist(['documents'])) {
            $documents = $sm->introspectTable('documents');

            foreach ($documents->getForeignKeys() as $fk) {
                if (strcasecmp($fk->getName(), 'FK_A2B072887294869C') === 0) {
                    $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B072887294869C');
                    break;
                }
            }
            $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B072887294869C FOREIGN KEY (article_id) REFERENCES articles (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

            if ($documents->hasColumn('original_name')) {
                $this->addSql('ALTER TABLE documents DROP COLUMN original_name');
            }
            if ($documents->hasColumn('size')) {
                $this->addSql('ALTER TABLE documents DROP COLUMN size');
            }
            if ($documents->hasColumn('created_at')) {
                $this->addSql('ALTER TABLE documents DROP COLUMN created_at');
            }

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
