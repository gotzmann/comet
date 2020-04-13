<?php
declare(strict_types=1);

use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response as SlimResponse;

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

    // TODO Check if this is duplicate event based on packetId or other data?

    // TODO Automatic store to DB only fields marked as existed in table
    $fortunes = Capsule::table(SBERPRIME_EVENTS_TABLE)->insert([
        'packet_id' => $event->packetId
    ]);


    //var_dump($parsedBody);
    echo "\npacketId=" . $event->packetId;
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
