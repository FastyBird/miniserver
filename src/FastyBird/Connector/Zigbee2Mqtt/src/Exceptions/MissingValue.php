<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Exceptions;

use LogicException as PHPLogicException;

final class MissingValue extends PHPLogicException implements Exception
{

}
