<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Exceptions;

use RuntimeException;

final class CloudWsCallTimeout extends RuntimeException implements Exception
{

}
