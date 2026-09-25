<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Exceptions;

use Exception as PHPException;
use FastyBird\Core\Exceptions;

class BadRequest extends PHPException implements Exceptions\Exception
{

}
