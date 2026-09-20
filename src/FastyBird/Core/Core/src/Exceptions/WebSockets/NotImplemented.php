<?php declare(strict_types = 1);

namespace FastyBird\Core\Exceptions\WebSockets;

use FastyBird\Core\Exceptions\Exception;
use Nette;

class NotImplemented extends Nette\NotImplementedException implements Exception
{

}
