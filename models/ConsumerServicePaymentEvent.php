<?php
declare(strict_types=1);

use Comet\Event;

//namespace Event;

class ConsumerServicePaymentEvent extends Event
{
    const TYPE = 'CONSUMER_SERVICE_PAYMENT_EVENT';

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
}
