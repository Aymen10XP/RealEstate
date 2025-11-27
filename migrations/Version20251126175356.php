<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126175356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lease (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, monthly_rent NUMERIC(10, 2) NOT NULL, security_deposit NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, property_id INT NOT NULL, tenant_id INT NOT NULL, INDEX IDX_E6C77495549213EC (property_id), INDEX IDX_E6C774959033212A (tenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE maintenance_request (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, priority VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, property_id INT NOT NULL, tenant_id INT NOT NULL, assigned_manager_id INT DEFAULT NULL, INDEX IDX_4261CA0D549213EC (property_id), INDEX IDX_4261CA0D9033212A (tenant_id), INDEX IDX_4261CA0D409F5712 (assigned_manager_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE manager (id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE owner (id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE property (id INT AUTO_INCREMENT NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, state VARCHAR(100) NOT NULL, zip_code VARCHAR(20) NOT NULL, monthly_rent NUMERIC(10, 2) NOT NULL, bedrooms INT NOT NULL, bathrooms INT NOT NULL, square_feet INT NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, owner_id INT NOT NULL, manager_id INT DEFAULT NULL, INDEX IDX_8BF21CDE7E3C61F9 (owner_id), INDEX IDX_8BF21CDE783E3463 (manager_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rent_payment (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, due_date DATE NOT NULL, paid_date DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, payment_method VARCHAR(50) DEFAULT NULL, lease_id INT NOT NULL, tenant_id INT NOT NULL, INDEX IDX_8C04FF7DD3CA542C (lease_id), INDEX IDX_8C04FF7D9033212A (tenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tenant (id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone VARCHAR(20) NOT NULL, type VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C774959033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE maintenance_request ADD CONSTRAINT FK_4261CA0D549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE maintenance_request ADD CONSTRAINT FK_4261CA0D9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE maintenance_request ADD CONSTRAINT FK_4261CA0D409F5712 FOREIGN KEY (assigned_manager_id) REFERENCES manager (id)');
        $this->addSql('ALTER TABLE manager ADD CONSTRAINT FK_FA2425B9BF396750 FOREIGN KEY (id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE owner ADD CONSTRAINT FK_CF60E67CBF396750 FOREIGN KEY (id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE7E3C61F9 FOREIGN KEY (owner_id) REFERENCES owner (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE783E3463 FOREIGN KEY (manager_id) REFERENCES manager (id)');
        $this->addSql('ALTER TABLE rent_payment ADD CONSTRAINT FK_8C04FF7DD3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE rent_payment ADD CONSTRAINT FK_8C04FF7D9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462BF396750 FOREIGN KEY (id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495549213EC');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C774959033212A');
        $this->addSql('ALTER TABLE maintenance_request DROP FOREIGN KEY FK_4261CA0D549213EC');
        $this->addSql('ALTER TABLE maintenance_request DROP FOREIGN KEY FK_4261CA0D9033212A');
        $this->addSql('ALTER TABLE maintenance_request DROP FOREIGN KEY FK_4261CA0D409F5712');
        $this->addSql('ALTER TABLE manager DROP FOREIGN KEY FK_FA2425B9BF396750');
        $this->addSql('ALTER TABLE owner DROP FOREIGN KEY FK_CF60E67CBF396750');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE7E3C61F9');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE783E3463');
        $this->addSql('ALTER TABLE rent_payment DROP FOREIGN KEY FK_8C04FF7DD3CA542C');
        $this->addSql('ALTER TABLE rent_payment DROP FOREIGN KEY FK_8C04FF7D9033212A');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462BF396750');
        $this->addSql('DROP TABLE lease');
        $this->addSql('DROP TABLE maintenance_request');
        $this->addSql('DROP TABLE manager');
        $this->addSql('DROP TABLE owner');
        $this->addSql('DROP TABLE property');
        $this->addSql('DROP TABLE rent_payment');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
