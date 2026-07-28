<?php

declare(strict_types=1);

namespace DoctrineMigrations\People;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Baseline schema for people module from s.controleonline.com";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('CREATE TABLE IF NOT EXISTS `company_document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `people_id` (`people_id`),
  KEY `documentType_id` (`document_type_id`),
  CONSTRAINT `company_document_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `company_document_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document` bigint(20) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc` (`document`,`document_type_id`),
  UNIQUE KEY `document` (`document_type_id`,`people_id`) USING BTREE,
  KEY `type_2` (`document_type_id`),
  KEY `image_id` (`file_id`),
  KEY `type` (`people_id`,`document_type_id`) USING BTREE,
  CONSTRAINT `document_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `document_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `document_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(50) CHARACTER SET utf8 NOT NULL,
  `people_type` enum(\'F\',\'J\') CHARACTER SET utf8 NOT NULL COMMENT \' Individual or juridical person\',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `email` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(50) CHARACTER SET utf8 NOT NULL,
  `types` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `confirmed` tinyint(1) NOT NULL DEFAULT \'0\',
  `people_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `IDX_E7927C743147C936` (`people_id`),
  CONSTRAINT `FK_E7927C743147C936` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=316723 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `employee_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_link_id` int(11) NOT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_function` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `termination_date` date DEFAULT NULL,
  `workload_hours` int(11) DEFAULT NULL,
  `linkedin_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_headline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_summary` longtext COLLATE utf8mb4_unicode_ci,
  `linkedin_snapshot` json DEFAULT NULL,
  `notes` longtext COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT \'1\',
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alter_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `job_title_id` int(11) DEFAULT NULL,
  `job_function_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_profile_people_link_unique` (`people_link_id`),
  KEY `employee_profile_people_link_idx` (`people_link_id`),
  KEY `employee_profile_job_title_idx` (`job_title_id`),
  KEY `employee_profile_job_function_idx` (`job_function_id`),
  KEY `employee_profile_department_idx` (`department_id`),
  CONSTRAINT `FK_EMPLOYEE_PROFILE_DEPARTMENT` FOREIGN KEY (`department_id`) REFERENCES `category` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_EMPLOYEE_PROFILE_JOB_FUNCTION` FOREIGN KEY (`job_function_id`) REFERENCES `category` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_EMPLOYEE_PROFILE_JOB_TITLE` FOREIGN KEY (`job_title_id`) REFERENCES `category` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_EMPLOYEE_PROFILE_PEOPLE_LINK` FOREIGN KEY (`people_link_id`) REFERENCES `people_link` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `media_types` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `people_type` set(\'F\',\'J\') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_type_unique` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `package_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `users` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `package_id` (`package_id`,`module_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `package_modules_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `package_modules_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `alias` varchar(64) CHARACTER SET utf8 NOT NULL,
  `register_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `enable` tinyint(1) NOT NULL,
  `people_type` enum(\'F\',\'J\') CHARACTER SET utf8 NOT NULL COMMENT \' Individual or juridical person\',
  `language_id` int(11) NOT NULL,
  `foundation_date` datetime DEFAULT NULL,
  `other_informations` longtext CHARACTER SET utf8,
  `source` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `subsector_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `language_id` (`language_id`),
  KEY `sector_id` (`sector_id`),
  KEY `subsector_id` (`subsector_id`),
  CONSTRAINT `people_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `people_ibfk_5` FOREIGN KEY (`sector_id`) REFERENCES `category` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `people_ibfk_6` FOREIGN KEY (`subsector_id`) REFERENCES `category` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=105476 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_absence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `absence_date` date NOT NULL,
  `reason` longtext COLLATE utf8mb4_unicode_ci,
  `justification_file_id` int(11) DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT \'1\',
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alter_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `people_absence_context_idx` (`context`),
  KEY `people_absence_company_idx` (`company_id`),
  KEY `people_absence_people_idx` (`people_id`),
  KEY `people_absence_absence_date_idx` (`absence_date`),
  KEY `people_absence_active_idx` (`active`),
  KEY `FK_PEOPLE_ABSENCE_FILE` (`justification_file_id`),
  CONSTRAINT `FK_PEOPLE_ABSENCE_COMPANY` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_PEOPLE_ABSENCE_FILE` FOREIGN KEY (`justification_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_PEOPLE_ABSENCE_PEOPLE` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_access_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `direction` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'entry\',
  `event_at` datetime NOT NULL,
  `source` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'manual\',
  `payload` json DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alter_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `people_access_event_context_idx` (`context`),
  KEY `people_access_event_company_idx` (`company_id`),
  KEY `people_access_event_people_idx` (`people_id`),
  KEY `people_access_event_event_at_idx` (`event_at`),
  KEY `people_access_event_direction_idx` (`direction`),
  CONSTRAINT `FK_PEOPLE_ACCESS_EVENT_COMPANY` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_PEOPLE_ACCESS_EVENT_PEOPLE` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `domain` varchar(255) CHARACTER SET utf8 NOT NULL,
  `theme_id` int(11) DEFAULT NULL,
  `domain_type` enum(\'API\',\'APP\',\'ERP\',\'SHOP\',\'WEBSITE\') CHARACTER SET utf8 NOT NULL DEFAULT \'ERP\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`),
  KEY `people_id` (`people_id`),
  KEY `theme_id` (`theme_id`),
  CONSTRAINT `people_domain_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_domain_ibfk_2` FOREIGN KEY (`theme_id`) REFERENCES `theme` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_export_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(11) NOT NULL,
  `people_id` int(11) DEFAULT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'pending\',
  `file_id` int(11) DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `error_message` longtext COLLATE utf8mb4_unicode_ci,
  `finished_at` datetime DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alter_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `people_export_job_context_idx` (`context`),
  KEY `people_export_job_kind_idx` (`kind`),
  KEY `people_export_job_status_idx` (`status`),
  KEY `people_export_job_company_idx` (`company_id`),
  KEY `people_export_job_people_idx` (`people_id`),
  KEY `people_export_job_period_start_idx` (`period_start`),
  KEY `people_export_job_period_end_idx` (`period_end`),
  KEY `FK_PEOPLE_EXPORT_JOB_FILE` (`file_id`),
  CONSTRAINT `FK_PEOPLE_EXPORT_JOB_COMPANY` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_PEOPLE_EXPORT_JOB_FILE` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_PEOPLE_EXPORT_JOB_PEOPLE` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `link_type` set(\'prospect\',\'employee\',\'client\',\'provider\',\'franchisee\',\'professor\',\'family\',\'salesman\',\'owner\',\'sellers-client\',\'director\',\'manager\',\'admin\',\'courier\') CHARACTER SET utf8 DEFAULT NULL,
  `comission` decimal(15,2) DEFAULT NULL,
  `minimum_comission` int(11) NOT NULL DEFAULT \'2000\',
  `enable` tinyint(1) NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `franchisee_id` (`company_id`,`people_id`,`link_type`) USING BTREE,
  KEY `franchisor_id` (`people_id`) USING BTREE,
  CONSTRAINT `people_link_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_link_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3097 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_media` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `media_type_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `people_id_2` (`people_id`,`media_type_id`) USING BTREE,
  KEY `people_id` (`people_id`),
  KEY `file_id` (`file_id`),
  KEY `media_type_id` (`media_type_id`),
  CONSTRAINT `people_media_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_media_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_media_ibfk_3` FOREIGN KEY (`media_type_id`) REFERENCES `media_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_client_id` int(11) NOT NULL,
  `order_value` float NOT NULL,
  PRIMARY KEY (`id`),
  KEY `people_client_id` (`people_client_id`),
  CONSTRAINT `people_order_ibfk_1` FOREIGN KEY (`people_client_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `people_id` (`people_id`),
  CONSTRAINT `people_package_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_package_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_procurator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `procurator_id` int(11) NOT NULL,
  `grantor_id` int(11) NOT NULL,
  `muniment_signature_id` int(11) NOT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`procurator_id`,`grantor_id`),
  UNIQUE KEY `muniment_signature_id` (`muniment_signature_id`),
  KEY `provider_id` (`grantor_id`),
  CONSTRAINT `people_procurator_ibfk_1` FOREIGN KEY (`procurator_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_procurator_ibfk_2` FOREIGN KEY (`grantor_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`people_id`,`role_id`),
  KEY `people_id` (`people_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `people_role_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_role_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_role_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `professional_people_id` int(11) DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'recurring\',
  `weekday` smallint(6) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT \'1\',
  `payload` json DEFAULT NULL,
  `creation_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alter_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `people_schedule_context_idx` (`context`),
  KEY `people_schedule_company_idx` (`company_id`),
  KEY `people_schedule_people_idx` (`people_id`),
  KEY `people_schedule_professional_people_idx` (`professional_people_id`),
  KEY `people_schedule_mode_idx` (`mode`),
  KEY `people_schedule_weekday_idx` (`weekday`),
  KEY `people_schedule_active_idx` (`active`),
  CONSTRAINT `FK_PEOPLE_SCHEDULE_COMPANY` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_PEOPLE_SCHEDULE_PEOPLE` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_PEOPLE_SCHEDULE_PROFESSIONAL_PEOPLE` FOREIGN KEY (`professional_people_id`) REFERENCES `people` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `people_support` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `support_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `commission` decimal(15,2) DEFAULT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `support_id` (`support_id`,`company_id`) USING BTREE,
  KEY `provider_id` (`company_id`),
  CONSTRAINT `people_support_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `phone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ddi` smallint(5) unsigned NOT NULL,
  `ddd` smallint(5) unsigned NOT NULL,
  `phone` int(10) unsigned NOT NULL,
  `confirmed` tinyint(1) NOT NULL DEFAULT \'0\',
  `people_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E7927C743147C936` (`people_id`),
  KEY `phone` (`phone`,`ddd`,`people_id`) USING BTREE,
  CONSTRAINT `phone_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33402 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
