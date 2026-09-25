<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Server;

use FastyBird\Core\WebSockets\Entities;
use Throwable;

interface ServerWrapper
{

	public function handleOpen(Entities\ConnectedClient $client): void;

	public function handleMessage(Entities\ConnectedClient $client, string $message): void;

	public function handleClose(Entities\ConnectedClient $client): void;

	public function handleError(Entities\ConnectedClient $client, Throwable $ex): void;

}
