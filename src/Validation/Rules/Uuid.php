<?php
declare(strict_types=1);

namespace Comet\Validation\Rules;

class Uuid extends \Rakit\Validation\Rule
{
    protected $message = ':attribute :value has been used';   
    
    public function check($value): bool
    {        
        return (bool) preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $value);
    }
}
