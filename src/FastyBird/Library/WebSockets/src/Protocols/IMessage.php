<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Protocols;

/**
 * Communication message interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Protocols
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IMessage extends IData
{

	public function addFrame(IFrame $fragment): void;

	public function getOpCode(): int;

}
