<?php
declare(strict_types=1);

use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response as SlimResponse;
use Illuminate\Database\Capsule\Manager as DB;
//use Illuminate\Database\Capsule\Manager as Capsule;
//use Illuminate\Support\Facades\DB;
use Comet\Event;

const SBERPRIME_EVENTS_TABLE = 'sberprime_events';
//namespace Handlers;

//use ConsumerServicePaymentEvent;
/*


class ConsumerServicePaymentHandler
{
    public static function handle(SlimRequest $request, SlimResponse $response, $args)
    {
        var_dump($response->getBody());
        $response->getBody()->write("{Comet} ConsumerServicePaymentHandler!");
        return $response;
    }
};
*/

// interface MiddlewareInterface
// public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
$consumerServicePaymentHandler = function(SlimRequest $request, SlimResponse $response, $args)
{
    //var_dump($response->getBody());
    //$response->getBody()->write("{Comet} ConsumerServicePaymentHandler!");

    //$body = $request->getParsedBody();

    //$event = new ConsumerServicePaymentEvent();
    //$event->fillFromArray($body);
    $payload = (string) $request->getBody();
    //$event->fillFromPayload($payload);
    $event = ConsumerServicePaymentEvent::createFromPayload($payload);
//var_dump($event);
//    $event2 = Comet\Event::createFromPayload($payload);
//var_dump($event2);

//var_dump($parsedBody);
echo "\npacket_id=" . $event->packetId;
//echo "\nstatus=" . Event::STATUS_NEW;
//echo "\nDB=" . DB::class;

//    $fortunes = Capsule::table('fortune')->get();
//    $payload = json_encode($fortunes);
//    $response->getBody()->write($payload);
//    return $response
//        ->withHeader('Content-Type', 'application/json');

    // TODO In case of problems there we miss the timeout response?
    // TODO Check if this is duplicate event based on packetId or other data?
    // TODO Automatic store to DB only fields marked as existed in table
    // -- uuid VARCHAR(100) NOT NULL,
//    global $capsule;
    // FIXME If there NOT NULL field in DB that we omit in field list - insert() methods just go some limbo wihtout returning anything

    // FIXME Is UTC the right timezone to store mostly Moscow times?
    // TODO Set MySQL or Postgre configs to work with UTC timezone by default
    // Date format like '2019-01-01T13:12:34.231+0300'
    $timezone = new DateTimeZone('UTC');
    $paymentDate = DateTime::createFromFormat('Y-m-d?H:i:s.vO', $event->paymentDate);
    $paymentDate->setTimezone($timezone);
    $paymentExpired = DateTime::createFromFormat('Y-m-d?H:i:s.vO', $event->paymentExpired);
    $paymentExpired->setTimezone($timezone);

    try {
        $result = DB::table(SBERPRIME_EVENTS_TABLE)->insert([
        //$result = Capsule::table(SBERPRIME_EVENTS_TABLE)->insert([
        //$result = Capsule::table('sberprime_events')->insert([
        //$result = DB::table('sberprime_events')->insert([
            'type'                  => ConsumerServicePaymentEvent::TYPE,
            'status'                => Event::STATUS_NEW,

            'client_key'            => $event->clientKey,
            'client_key_type'       => $event->clientKeyType,
            'customer_id'           => $event->customerId,
            'packet_id'             => $event->packetId,
            'pay_system_transaction_id' => $event->paySystemTransactionId,
            'pay_system_type'       => $event->paySystemType,
            'payment_date'          => $paymentDate,
            'payment_expired'       => $paymentExpired,
            'payment_order_id'      => $event->paymentOrderId,
            'service_catalog_type'  => $event->serviceCatalogType,
            'service_external_id'   => $event->serviceExternalId,

            'payload'               => $event->getPayload(),
        ]);
    } catch(Exception $e) {
        // TODO Log exception
        echo "\n[ERR] " . $e->getMessage();
        // TODO What status code we should return?
        return $response->withStatus(503);
    }

    if (!$result) {
        return $response->withStatus(503);
    }
//var_dump($result);



/*
    // responses:
        '200':
          description: OK
        '201':
          description: Created
        '401':
          description: Unauthorized
        '403':
          description: Forbidden
        '404':
          description: Not Found
*/
    return $response->withStatus(201);
};
/*
function get(SlimRequest $request, SlimResponse $response, $args)
{
    //var_dump($response->getBody()); // Slim/Psr7/Stream
    $response->getBody()->write("{Comet} ConsumerServicePaymentHandler!");
    return $response;
}

$the = function(SlimRequest $request, SlimResponse $response, $args)
{
    var_dump($response->getBody());
    $response->getBody()->write("{Comet} ConsumerServicePaymentHandler!");
    return $response;
};
*/
