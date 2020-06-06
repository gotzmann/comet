<?php               
declare(strict_types=1);

namespace Comet;

// Based on https://github.com/rakit/validation

class Validator extends \Rakit\Validation\Validator
{

	// Magic call to any of the parent methods
    public function __call (string $name, array $args) 
    {	echo "call parent";
        return parent::$name(...$args);
    }

    /**
     * Return errors from ErrorBag as array 
     *
     * @return array
     */
    public function errors(): ErrorBag
    {

    	echo "!!!";

    	$arr = [];

		foreach ($this->errors->messages as $key => $error) {
			foreach ($error as $rule => $message) {
//			$errors[$key][] = $message;
				$errors[$key] = $message;
			}
		}

        return $arr;
    }
	
}
