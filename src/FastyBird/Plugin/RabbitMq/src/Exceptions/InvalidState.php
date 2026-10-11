<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Exceptions;

use RuntimeException;

class InvalidState extends RuntimeException implements Exception
{

}
