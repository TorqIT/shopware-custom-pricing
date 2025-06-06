<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Torq\Shopware\CustomPricing\Exception\CustomPriceApiBehaviourIncorrectException;

class CustomPriceApiDirector 
{
    
    private static bool $supressApiCall = false;
    private static bool $forceApiCall = false;

    public static function getForceApiCall(): bool{
        return self::$forceApiCall;
    }

    public static function setForceApiCall(bool $forceApiCall){
        if(self::$supressApiCall == true && $forceApiCall == true){
            throw new CustomPriceApiBehaviourIncorrectException();
        }

        self::$forceApiCall = $forceApiCall;
    }

    public static function getSupressApiCall(): bool{
        return self::$supressApiCall;
    }

    public static function setSupressApiCall(bool $suppressApiCall){
        if(self::$forceApiCall == true && $suppressApiCall == true){
            throw new CustomPriceApiBehaviourIncorrectException();
        }

        self::$supressApiCall = $suppressApiCall;
    }


    public static function runWithSuppressedApi(callable $function) : mixed {
        $forceOriginal = self::$forceApiCall;
        $supressOriginal = self::$supressApiCall;

        self::setForceApiCall(false);
        self::setSupressApiCall(true);
        $result = $function();
        self::setSupressApiCall($forceOriginal);
        self::setSupressApiCall($supressOriginal);

        return $result;
    }
}
