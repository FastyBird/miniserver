<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Protocols;

/**
 * Communication frame interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Protocols
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IFrame extends IData
{

	/**
	 * Add incoming data to the frame from peer
	 */
	public function addBuffer(string $buffer): void;

	/**
	 * Is this the final frame in a fragmented message?
	 */
	public function isFinal(): bool;

	/**
	 * Is the payload masked?
	 */
	public function isMasked(): bool;

	public function getOpCode(): int;

	/**
	 * 32-bit string
	 */
	public function getMaskingKey(): string;

}
