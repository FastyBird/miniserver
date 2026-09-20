<?php declare(strict_types = 1);

namespace FastyBird\Core\Server\WsServer;

use FastyBird\Core\Entities\WsServer as Entities;
use Throwable;

interface IWrapper
{

	public function handleOpen(Entities\IClient $client): void;

	public function handleMessage(Entities\IClient $client, string $message): void;

	public function handleClose(Entities\IClient $client): void;

	public function handleError(Entities\IClient $client, Throwable $ex): void;

}
