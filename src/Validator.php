<?php

declare(strict_types=1);

namespace Meteor;

use Meteor\Validation;
use JsonException;

/**
 * Class Validator
 * Based on https://github.com/rakit/validation
 * @package Meteor
 */
class Validator extends \Rakit\Validation\Validator
{
    /**
     * Constructor
     *
     * @param array $messages
     * @return void
     */
    public function __construct(array $messages = [])
    {
        parent::__construct($messages);
        $this->useHumanizedKeys = false;
        $this->setValidator('uuid', new Validation\Rules\Uuid());
    }

    // Magic call to any of the parent methods
    public function __call(string $name, array $args)
    {
        return parent::$name(...$args);
    }


    /**
     * @throws JsonException
     */
    public function make(mixed $inputs, array $rules, array $messages = []): Validation\Validation
    {
        // We should convert any input into array
        if (is_object($inputs)) {
            $inputs = $inputs->toArray();
        }

        if (is_string($inputs)) {
            $inputs = json_decode($inputs, true, 512, JSON_THROW_ON_ERROR);
        }

        $messages = array_merge($this->messages, $messages);
        $validation = new Validation\Validation($this, $inputs, $rules, $messages);
        $validation->setTranslations($this->getTranslations());

        return $validation;
    }
}
