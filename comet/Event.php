<?php
declare(strict_types=1);

namespace Comet;

// TODO We need validation methods seems to that Laravel and Go provides

abstract class Event
{
    protected $payload;

    function __construct()
    {
    }

    public static function create()
    {
        return new static();
    }

    public static function createFromPayload(string $string)
    {
        $event = new static();
        $event->payload = $string;
        $event->withString($string);
        return $event;
    }

    public function withArray(array $arr)
    {
        foreach($arr as $key => $value) {
            if(property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function withString(string $json)
    {
        $arr = json_decode($json, true);
        $this->withArray($arr);
    }

    public function getPayload()
    {
        return $this->payload;
    }
}
