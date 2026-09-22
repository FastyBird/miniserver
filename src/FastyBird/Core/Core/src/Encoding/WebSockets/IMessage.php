<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\WebSockets;

/**
 * Communication message interface
 */
interface IMessage extends IData
{

	public function addFrame(IFrame $fragment): void;

	public function getOpCode(): int;

}
