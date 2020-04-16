<?php
declare(strict_types=1);

use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response as SlimResponse;
use Illuminate\Database\Capsule\Manager as ORM;

const SBERPRIME_EVENTS_TABLE = 'sberprime_events';

$consumerServicePaymentHandler = function(SlimRequest $request, SlimResponse $response, $args)
{
    $payload = (string) $request->getBody();
    $event = ConsumerServicePaymentEvent::createFromPayload($payload);

    // TODO Log this event
    //echo "\npacket_id=" . $event->packetId;

    // TODO In case of problems there we miss the timeout response?
    // TODO Check if this is duplicate event based on packetId or other data?
    // TODO Automatic store to DB only fields marked as existed in table

    // FIXME If there NOT NULL field in DB that we omit in field list - insert() methods just go some limbo wihtout returning anything

    try {
        $result = ORM::table(SBERPRIME_EVENTS_TABLE)->insert([
            $event->toArray()
        ]);
    } catch(Exception $e) {
        // TODO Log exception
        echo "\n[ERR] " . $e->getMessage();
        // TODO What status code we should return?
        // FIXME Другие коды ошибок!
        return $response->withStatus(503);
    }

    if (!$result) {
        // TODO Обеспечить text/plain;charset=UTF-8
        return $response->withStatus(503);
    }

    return $response->withStatus(201);
};
