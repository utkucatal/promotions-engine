<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328142033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD sku VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD description TEXT DEFAULT NULL');
        $this->addSql("UPDATE product SET name = 'Unknown Product' WHERE name IS NULL");
        $this->addSql('ALTER TABLE product ALTER COLUMN name SET NOT NULL');
        $this->addSql('ALTER TABLE product ALTER COLUMN name DROP DEFAULT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04ADF9038C4 ON product (sku)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D34A04ADF9038C4');
        $this->addSql('ALTER TABLE product DROP name');
        $this->addSql('ALTER TABLE product DROP sku');
        $this->addSql('ALTER TABLE product DROP description');
    }
}
