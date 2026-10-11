<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\Exceptions;

use RuntimeException;

final class InvalidState extends RuntimeException implements Exception
{

}
