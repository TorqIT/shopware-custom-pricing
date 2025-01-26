<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Commercial\CustomPricing\Domain\CustomPriceCollector;
use Shopware\Commercial\CustomPricing\Entity\CustomPrice\CustomPriceDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Torq\Shopware\CustomPricing\Constants\ConfigConstants;
use Torq\Shopware\CustomPricing\Exception\CustomPriceApiBehaviourIncorrectException;

class CustomPriceApiDirector 
{
    
    private static bool $supressApiCall = false;
    private static bool $forceApiCall = false;

    public static function getForceApiCall(): bool{
        return self::$forceApiCall;
    }

    public static function setForceApiCall(bool $forceApiCall){
        if(self::$supressApiCall == true){
            throw new CustomPriceApiBehaviourIncorrectException();
        }

        self::$forceApiCall = $forceApiCall;
    }

    public static function getSupressApiCall(): bool{
        return self::$supressApiCall;
    }

    public static function setSupressApiCall(bool $supressApiCall){
        if(self::$forceApiCall == true){
            throw new CustomPriceApiBehaviourIncorrectException();
        }

        self::$supressApiCall = $supressApiCall;
    }
}
