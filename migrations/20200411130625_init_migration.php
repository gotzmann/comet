<?php

// TODO Replace Phinx migrations with something lighter,
// and maybe "invented there"

/*
    How to create new migration?
    CLI: php vendor/robmorgan/phinx/bin/phinx create NewMigration

    How to process all migrations?
    CLI: php vendor/robmorgan/phinx/bin/phinx migrate
*/

use Phinx\Migration\AbstractMigration;

class InitMigration extends AbstractMigration
{
    /*
      - clientKey
      - clientKeyType
      - customerId
      - packetId
      - paySystemTransactionId
      - paySystemType
      - paymentDate
      - paymentExpired
      - paymentOrderId
      - serviceCatalogType
      - serviceExternalId
    */

    public function up()
    {
        $this->execute("

            CREATE TABLE IF NOT EXISTS users (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid VARCHAR(100) NOT NULL,

                -- payment_order_id

                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS sberprime_events (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                -- type of event like CONSUMER_SERVICE_PAYMENT_EVENT
                type varchar(100),
                uuid VARCHAR(100) NOT NULL,
                packet_id varchar(100),

                -- status = NEW -> PROCESSING -> PROCESSED / DECLINED / FAILED
                status VARCHAR(100),

                -- payment_order_id

                payload JSON, -- original POST body of request,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        ");

    }

    public function down()
    {
    }
}
