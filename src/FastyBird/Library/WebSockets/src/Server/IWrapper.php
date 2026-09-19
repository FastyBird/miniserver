<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Server;

use FastyBird\Library\WebSockets\Entities;
use Throwable;

interface IWrapper
{

	public function handleOpen(Entities\Clients\IClient $client): void;

	public function handleMessage(Entities\Clients\IClient $client, string $message): void;

	public function handleClose(Entities\Clients\IClient $client): void;

	public function handleError(Entities\Clients\IClient $client, Throwable $ex): void;

}
