<?php

namespace Torq\Shopware\CustomPricing\Exception;

class CustomPriceApiBehaviourIncorrectException extends \Exception
{
    public function __construct(string $message = "CustomPriceApiDirector cannot be set to both force and suppress API calls!")
    {
        parent::__construct($message);
    }
}