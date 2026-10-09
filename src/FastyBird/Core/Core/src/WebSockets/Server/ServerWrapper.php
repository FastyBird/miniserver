<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Server;

use FastyBird\Core\WebSockets\Entities;
use Throwable;

interface ServerWrapper
{

	public function handleOpen(Entities\Client $client): void;

	public function handleMessage(Entities\Client $client, string $message): void;

	public function handleClose(Entities\Client $client): void;

	public function handleError(Entities\Client $client, Throwable $ex): void;

}
