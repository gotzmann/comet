<?php               
declare(strict_types=1);

namespace Comet;

use Comet\Validation;

/**
 * Class Validator
 * Based on https://github.com/rakit/validation
 * @package Comet
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
		$this->setValidator('uuid', new Validation\Rules\Uuid);
	}

	// Magic call to any of the parent methods
    public function __call (string $name, array $args) 
    {	
        return parent::$name(...$args);
    }

    /**
     * Validate $inputs
     *
     * @param $inputs
     * @param array $rules
     * @param array $messages
     * @return Validation
     */
    public function validate($inputs, array $rules, array $messages = []): \Rakit\Validation\Validation
    {
        $validation = $this->make($inputs, $rules, $messages);
        $validation->validate();
        return $validation;
    }

    /**
     * Given $inputs, $rules and $messages to make the Comet Validation class instance
     * NB! Output type declared as \Rakit\Validation\Validation to conform OOP rules
     *
     * @param $inputs
     * @param array $rules
     * @param array $messages
     * @return Validation
     */
    public function make($inputs, array $rules, array $messages = []): \Rakit\Validation\Validation
    {
    	// We should convert any input into array 
    	if (is_object($inputs)) {
    		$inputs = $inputs->toArray();
    	} else if (is_string($inputs)) {
	        $inputs = json_decode($inputs, true);
    	}

        $messages = array_merge($this->messages, $messages);
        $validation = new \Comet\Validation\Validation($this, $inputs, $rules, $messages);
        $validation->setTranslations($this->getTranslations());

        return $validation;
    }	
}
