<?php

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
                type varchar(100), -- type of event like CONSUMER_SERVICE_PAYMENT_EVENT
                uuid VARCHAR(100) NOT NULL,

                -- payment_order_id

                payload JSON, -- original POST body of request,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                status VARCHAR(100), -- NEW -> PROCESSING -> PROCESSED / DECLINED / FAILED

                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
        ");

    }

    public function down()
    {
    }
}
