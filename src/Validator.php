<?php               
declare(strict_types=1);

namespace Comet;

use Comet\Validation;

// Based on https://github.com/rakit/validation

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
		$this->setValidator('uuid', new Validation\Uuid);
	}

	// Magic call to any of the parent methods
    public function __call (string $name, array $args) 
    {	echo "call parent";
        return parent::$name(...$args);
    }

    /**
     * Validate $inputs
     *
     * @param array $inputs
     * @param array $rules
     * @param array $messages
     * @return Validation
     */
    public function validate(array $inputs, array $rules, array $messages = []): \Rakit\Validation\Validation
    {
        $validation = $this->make($inputs, $rules, $messages);
        $validation->validate();
        return $validation;
    }

    /**
     * Given $inputs, $rules and $messages to make the Comet Validation class instance
     * NB! Output type declared as \Rakit\Validation\Validation to conform OOP rules
     *
     * @param array $inputs
     * @param array $rules
     * @param array $messages
     * @return Validation
     */
    public function make(array $inputs, array $rules, array $messages = []): \Rakit\Validation\Validation
    {
        $messages = array_merge($this->messages, $messages);
        $validation = new \Comet\Validation\Validation($this, $inputs, $rules, $messages);
        $validation->setTranslations($this->getTranslations());

        return $validation;
    }	
}