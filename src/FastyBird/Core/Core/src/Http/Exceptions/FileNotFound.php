<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Exceptions;

use FastyBird\Core\Exceptions;
use RuntimeException;

final class FileNotFound extends RuntimeException implements Exceptions\Exception
{

}
