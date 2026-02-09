<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209122956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, match_date DATETIME NOT NULL, home_score INT DEFAULT NULL, away_score INT DEFAULT NULL, status VARCHAR(20) NOT NULL, group_name VARCHAR(1) DEFAULT NULL, home_team_id INT NOT NULL, away_team_id INT NOT NULL, stadium_id INT NOT NULL, phase_id INT NOT NULL, INDEX IDX_232B318C9C4C13F6 (home_team_id), INDEX IDX_232B318C45185D02 (away_team_id), INDEX IDX_232B318C7E860E36 (stadium_id), INDEX IDX_232B318C99091188 (phase_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE phase (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, code VARCHAR(20) NOT NULL, display_order INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stadium (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, city VARCHAR(100) NOT NULL, country VARCHAR(50) NOT NULL, capacity INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(3) NOT NULL, flag VARCHAR(255) DEFAULT NULL, group_name VARCHAR(1) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C9C4C13F6 FOREIGN KEY (home_team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C45185D02 FOREIGN KEY (away_team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C7E860E36 FOREIGN KEY (stadium_id) REFERENCES stadium (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C99091188 FOREIGN KEY (phase_id) REFERENCES phase (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C9C4C13F6');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C45185D02');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C7E860E36');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C99091188');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE phase');
        $this->addSql('DROP TABLE stadium');
        $this->addSql('DROP TABLE team');
    }
}
