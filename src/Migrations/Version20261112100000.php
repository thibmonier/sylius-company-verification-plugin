<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Additive: one table. Schema API (not raw SQL) so that it runs on MySQL, MariaDB and PostgreSQL.
 */
final class Version20261112100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the verification history of the companies (tmonier/sylius-company-verification-plugin).';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('tmonier_company_verification');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('company_id', Types::INTEGER);
        $table->addColumn('registration_number', Types::STRING, ['length' => 64, 'notnull' => false]);
        $table->addColumn('vat_number', Types::STRING, ['length' => 32, 'notnull' => false]);
        $table->addColumn('register_status', Types::STRING, ['length' => 16]);
        $table->addColumn('register_name', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('register_address', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('register_closed_at', Types::DATE_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('vat_status', Types::STRING, ['length' => 16]);
        $table->addColumn('vat_name', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('vat_address', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('verified_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['company_id', 'id'], 'idx_tmonier_company_verification_company');
        $table->addForeignKeyConstraint('tmonier_company', ['company_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_tmonier_company_verification_company');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('tmonier_company_verification');
    }
}
