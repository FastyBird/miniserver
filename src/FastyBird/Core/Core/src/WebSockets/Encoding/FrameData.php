<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Encoding;

/**
 * Communication data interface
 */
interface FrameData
{

	/**
	 * Determine if the message is complete or still fragmented
	 */
	public function isCoalesced(): bool;

	/**
	 * Get the number of bytes the payload is set to be
	 */
	public function getPayloadLength(): int;

	/**
	 * Get the payload (message) sent from peer
	 */
	public function getPayload(): string;

	/**
	 * Get raw contents of the message
	 */
	public function getContents(): string;

}
