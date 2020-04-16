<?php
declare(strict_types=1);

use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Response as SlimResponse;
use Illuminate\Database\Capsule\Manager as ORM;

$servicePaymentHandler = function(SlimRequest $request, SlimResponse $response, $args)
{
    global $log;

    $payload = (string) $request->getBody();
    $event = ConsumerServicePaymentEvent::createFromPayload($payload);

    $log->info("[servicePaymentHandler] " . $payload);

    // TODO In case of problems there we miss the timeout response?
    // TODO Check if this is duplicate event based on packetId or other data?
    // TODO Automatic store to DB only fields marked as existed in table

    // FIXME If there NOT NULL field in DB that we omit in field list - insert() methods just go some limbo wihtout returning anything

    try {
        $result = ORM::table('sberprime_events')->insert([
            $event->toArray()
        ]);
    } catch(Exception $e) {
        // TODO Log exception
        $log->error("[ERR] " . $e->getMessage());
        // TODO What status code we should return?
        // FIXME Другие коды ошибок!
        return $response->withStatus(503);
    }

    if (!$result) {
        // TODO Обеспечить text/plain;charset=UTF-8
        return $response->withStatus(503);
    }

    // Add new OR exisiting user to the Nextcloud
    // https://docs.nextcloud.com/server/16/admin_manual/configuration_user/instruction_set_for_users.html
    // TODO URL : ocs/v1.php/cloud/users
    // REAL : https://files.sberdisk.ru/ocs/v2.php/apps/notifications/api/v2/notifications
    // TODO ADD HTTP Client

    return $response->withStatus(201);
};
