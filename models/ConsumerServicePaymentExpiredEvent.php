<?php
declare(strict_types=1);

// Прием владельцем Услуги извещения об истечении срока оплаченного периода подписки
// ServicePaymentExpiredEvent External
// https://sberx-event-sberx-dev.apps.ocp.sbercloud.ru/swagger-ui.html

class ConsumerServicePaymentExpiredEvent extends Event
{
    public $clientKey;
    public $clientKeyType;
    public $customerId;
    public $packetId;
    public $paySystemTransactionId;
    public $paySystemType;
    public $paymentDate;
    public $paymentExpired;
    public $paymentOrderId;
    public $serviceCatalogType;
    public $serviceExternalId;
    public $promoCode;

    public static function createFromPayload(string $string)
    {
        $event = parent::createFromPayload($string);

        // FIXME Is UTC the right timezone to store mostly Moscow times?
        // TODO Set MySQL or Postgre configs to work with UTC timezone by default
        // Date format like '2019-01-01T13:12:34.231+0300'
        $timezone = new DateTimeZone('UTC');
        $event->paymentDate = DateTime::createFromFormat('Y-m-d?H:i:s.vO', $event->paymentDate);
        $event->paymentDate->setTimezone($timezone);
        $event->paymentExpired = DateTime::createFromFormat('Y-m-d?H:i:s.vO', $event->paymentExpired);
        $event->paymentExpired->setTimezone($timezone);

        return $event;
    }

    public function toArray() : array
    {
        return [
            'type'                      => $this->type,
            'status'                    => $this->status,

            'client_key'                => $this->clientKey,
            'client_key_type'           => $this->clientKeyType,
            'customer_id'               => $this->customerId,
            'packet_id'                 => $this->packetId,
            'pay_system_transaction_id' => $this->paySystemTransactionId,
            'pay_system_type'           => $this->paySystemType,
            'payment_date'              => $this->paymentDate,
            'payment_expired'           => $this->paymentExpired,
            'payment_order_id'          => $this->paymentOrderId,
            'promo_code'                => $this->promoCode,
            'service_catalog_type'      => $this->serviceCatalogType,
            'service_external_id'       => $this->serviceExternalId,

            'payload'                   => $this->getPayload(),
        ];
    }
}
