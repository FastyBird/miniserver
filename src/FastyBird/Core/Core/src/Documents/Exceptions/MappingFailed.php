<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Exceptions;

use FastyBird\Core\Exceptions;
use RuntimeException;

/**
 * Input data could not be mapped to the requested class. The message is the printed list of what was wrong
 */
final class MappingFailed extends RuntimeException implements Exceptions\Exception
{

}
