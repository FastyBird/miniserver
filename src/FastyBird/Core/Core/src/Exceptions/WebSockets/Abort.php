<?php declare(strict_types = 1);

namespace FastyBird\Core\Exceptions\WebSockets;

use Exception as PHPException;
use FastyBird\Core\Exceptions\Exception;

class Abort extends PHPException implements Exception
{

}
