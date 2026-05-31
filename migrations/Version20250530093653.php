<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250530093653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean up book descriptions by removing leading div tags';
    }

    public function up(Schema $schema): void
    {
        // Update descriptions to remove leading div tags
        $this->addSql('UPDATE book SET description = REGEXP_REPLACE(description, \'^<div>|</div>$\', \'\') WHERE description LIKE \'<div>%\'');
        
        // Also clean up any potential nested divs at the start
        $this->addSql('UPDATE book SET description = REGEXP_REPLACE(description, \'^(<div>)+|</div>$\', \'\') WHERE description LIKE \'<div>%\'');
    }

    public function down(Schema $schema): void
    {
        // We can't reliably restore the divs, so we do nothing in down()
        $this->addSql('-- No down migration needed for description cleanup');
    }
} 