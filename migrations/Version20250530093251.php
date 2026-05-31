<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530093251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE book ADD isbn VARCHAR(13) DEFAULT NULL, ADD publication_date DATE DEFAULT NULL, ADD publisher VARCHAR(255) DEFAULT NULL, ADD pages INT DEFAULT NULL, DROP stock, CHANGE description description LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_CBE5A331989D9B62 ON book (slug)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_CBE5A331989D9B62 ON book
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE book ADD stock INT NOT NULL, DROP isbn, DROP publication_date, DROP publisher, DROP pages, CHANGE description description LONGTEXT NOT NULL
        SQL);
    }
}
