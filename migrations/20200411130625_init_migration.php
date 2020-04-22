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

            BEGIN;

            -- sberdisk_users

            CREATE TABLE IF NOT EXISTS users (

                id                          BIGSERIAL PRIMARY KEY,
                uuid                        VARCHAR(100) NOT NULL,

                -- TODO All other fields required for representing all user info for Billing & Subscription

                created_at                  TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at                  TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP

            );

            -- sberprime_events

            CREATE TABLE IF NOT EXISTS sberprime_events (

                id                          BIGSERIAL PRIMARY KEY,
                uuid                        VARCHAR(100),
                type                        VARCHAR(100), -- type of event like ConsumerServicePaymentEvent
                status                      VARCHAR(100), -- status like new -> processing -> processed / declined / failed

                client_key                  VARCHAR(100),
                client_key_type             VARCHAR(100), -- ENUM
                customer_id                 VARCHAR(100), -- UUID
                packet_id                   VARCHAR(100),
                pay_system_transaction_id   VARCHAR(100),
                pay_system_type             VARCHAR(100), -- ENUM
                payment_date                TIMESTAMPTZ,
                payment_expired             TIMESTAMPTZ,
                payment_order_id            VARCHAR(100),
                promo_code                  VARCHAR(100),
                service_catalog_type        VARCHAR(100),
                service_external_id         VARCHAR(100), -- ENUM

                payload                     JSONB, -- original POST body of request,
                created_at                  TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at                  TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP

            );

            -- sberdisk_tariffs

--            CREATE TABLE IF NOT EXISTS sberdisk_tariffs (

--                id                          BIGSERIAL PRIMARY KEY,
--                uuid                        VARCHAR(100) NOT NULL,

                -- TODO All other fields required for representing all tariffs in Billing

--                created_at                  TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
--                updated_at                  TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

--            );

            COMMIT;

        ");

    }

    public function down()
    {
    }
}
