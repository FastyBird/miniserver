<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Exceptions;

use FastyBird\Core\Exceptions;
use Nette;

final class WampNotImplemented extends Nette\NotImplementedException implements Exceptions\Exception
{

}
