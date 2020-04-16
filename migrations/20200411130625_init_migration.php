<?php

// TODO Replace Phinx migrations with something simpler, maybe "invented here"

/*
    How to create new migration?
    CLI: php vendor/robmorgan/phinx/bin/phinx create NewMigration

    How to process all migrations?
    CLI: php vendor/robmorgan/phinx/bin/phinx migrate
*/

use Phinx\Migration\AbstractMigration;

class InitMigration extends AbstractMigration
{

    public function up()
    {
        $this->execute("

            -- sberdisk_users

            CREATE TABLE IF NOT EXISTS users (

                id INT(11)                  UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid                        VARCHAR(100) NOT NULL,

                -- TODO All other fields required for representing all user info for Billing & Subscription

                created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            -- sberprime_events

            CREATE TABLE IF NOT EXISTS sberprime_events (

                id                          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid                        VARCHAR(100),
                type                        VARCHAR(100), -- type of event like CONSUMER_SERVICE_PAYMENT_EVENT
                status                      VARCHAR(100), -- status = NEW -> PROCESSING -> PROCESSED / DECLINED / FAILED

                client_key                  VARCHAR(100),
                client_key_type             VARCHAR(100), -- ENUM
                customer_id                 VARCHAR(100), -- UUID
                packet_id                   VARCHAR(100) NOT NULL,
                pay_system_transaction_id   VARCHAR(100),
                pay_system_type             VARCHAR(100), -- ENUM
                payment_date                DATETIME,
                payment_expired             DATETIME,
                payment_order_id            VARCHAR(100),
                service_catalog_type        VARCHAR(100),
                service_external_id         VARCHAR(100), -- ENUM

                payload                     JSON, -- original POST body of request,
                created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id),
                UNIQUE KEY (packet_id)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            -- sberdisk_tariffs

            CREATE TABLE IF NOT EXISTS sberdisk_tariffs (

                id                          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid                        VARCHAR(100) NOT NULL,

                -- TODO All other fields required for representing all tariffs in Billing

                created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id)

            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        ");

    }

    public function down()
    {
    }
}
