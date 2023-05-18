<?php

declare(strict_types=1);

namespace Meteor\Validation;

/**
 * Class Validation
 * @package Meteor\Validation
 */
class Validation extends \Rakit\Validation\Validation
{
    /**
     * Return errors from ErrorBag as array
     *
     * @return array
     */
    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->errors->toArray() as $key => $error) {
            foreach ($error as $rule => $message) {
                $errors[$key] = $message;
            }
        }

        return $errors;
    }
}
