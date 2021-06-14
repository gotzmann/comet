<?php
declare(strict_types=1);

namespace Comet\Validation;

/**
 * Class Validation
 * @package Comet\Validation
 */
class Validation extends \Rakit\Validation\Validation
{
	/**
     * Return errors from ErrorBag as array 
     *
     * @return array
     */
    public function getErrors()
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
