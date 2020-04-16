<?php
declare(strict_types=1);

//namespace Comet;

// TODO We need validation methods seems to that Laravel and Go provides

abstract class Event
{
    // Statuses to track movement from stage to stage

    const STATUS_NEW        = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PROCESSED  = 'processed';
    const STATUS_FAILED     = 'failed';
    const STATUS_DECLINED   = 'declined';

    protected $type;
    protected $status;
    protected $payload;

    function __construct()
    {
        $this->type = static::class;
        $this->status = self::STATUS_NEW;
    }

    public static function create()
    {
        return new static();
    }

    // TODO Warnings if there JSON fields that has no CLASS properties?
    // TODO Use 'required' qualificators for properties that MUST be presented in JSON payload
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
