<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Exceptions;

use Exception as PHPException;
use FastyBird\Core\Exceptions;

final class Terminate extends PHPException implements Exceptions\Exception
{

}
