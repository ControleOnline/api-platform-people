<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714200000 extends AbstractMigration
{
    private const CATEGORY_CONTEXT_EMPLOYMENT_TYPE = 'employment-type';

    public function getDescription(): string
    {
        return 'Add employment_type_id to employee_profile and backfill category relations';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('employee_profile')) {
            return;
        }

        $this->ensureColumn('employee_profile', 'employment_type_id', 'INT DEFAULT NULL');
        $this->ensureIndex('employee_profile', 'employee_profile_employment_type_idx', 'employment_type_id');

        if ($this->tableExists('category') && $this->tableExists('people_link')) {
            $this->backfillEmploymentTypeRelation();
            $this->ensureForeignKey(
                'employee_profile',
                'FK_EMPLOYEE_PROFILE_EMPLOYMENT_TYPE',
                'employment_type_id',
                'category',
                'id'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('employee_profile')) {
            return;
        }

        $this->dropForeignKeyIfExists('employee_profile', 'FK_EMPLOYEE_PROFILE_EMPLOYMENT_TYPE');
        $this->dropIndexIfExists('employee_profile', 'employee_profile_employment_type_idx');
        $this->dropColumnIfExists('employee_profile', 'employment_type_id');
    }

    private function backfillEmploymentTypeRelation(): void
    {
        $nameExpression = 'LEFT(TRIM(ep.employment_type), 100)';

        $this->addSql(sprintf(
            "INSERT INTO category (name, context, company_id)
             SELECT DISTINCT %s, ?, pl.company_id
             FROM employee_profile ep
             INNER JOIN people_link pl ON pl.id = ep.people_link_id
             WHERE ep.employment_type IS NOT NULL
             AND TRIM(ep.employment_type) <> ''
             AND NOT EXISTS (
                SELECT 1 FROM category c
                WHERE c.context = ?
                AND c.company_id = pl.company_id
                AND c.name = %s
             )",
            $nameExpression,
            $nameExpression
        ), [self::CATEGORY_CONTEXT_EMPLOYMENT_TYPE, self::CATEGORY_CONTEXT_EMPLOYMENT_TYPE]);

        $this->addSql(sprintf(
            "UPDATE employee_profile ep
             INNER JOIN people_link pl ON pl.id = ep.people_link_id
             INNER JOIN category c ON c.context = ?
             AND c.company_id = pl.company_id
             AND c.name = %s
             SET ep.employment_type_id = c.id
             WHERE ep.employment_type_id IS NULL
             AND ep.employment_type IS NOT NULL
             AND TRIM(ep.employment_type) <> ''",
            $nameExpression
        ), [self::CATEGORY_CONTEXT_EMPLOYMENT_TYPE]);
    }

    private function ensureColumn(string $tableName, string $columnName, string $definition): void
    {
        if ($this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s ADD %s %s', $tableName, $columnName, $definition));
    }

    private function ensureIndex(string $tableName, string $indexName, string $columnName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s ADD INDEX %s (%s)', $tableName, $indexName, $columnName));
    }

    private function ensureForeignKey(
        string $tableName,
        string $constraintName,
        string $columnName,
        string $referencedTable,
        string $referencedColumn
    ): void {
        if ($this->foreignKeyExists($tableName, $constraintName)) {
            return;
        }

        $this->addSql(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE SET NULL',
            $tableName,
            $constraintName,
            $columnName,
            $referencedTable,
            $referencedColumn
        ));
    }

    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!$this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN %s', $tableName, $columnName));
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP INDEX %s', $tableName, $indexName));
    }

    private function dropForeignKeyIfExists(string $tableName, string $constraintName): void
    {
        if (!$this->foreignKeyExists($tableName, $constraintName)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $tableName, $constraintName));
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $indexName]
        ) > 0;
    }

    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$tableName, $constraintName]
        ) > 0;
    }
}
