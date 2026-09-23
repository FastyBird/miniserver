<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\WebSockets;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use function array_keys;
use function implode;

/**
 * Manage the various protocols of the WebSocket protocol
 */
final class ProtocolProxy
{

	/**
	 * Storage of enabled protocols
	 *
	 * @var array<IProtocol>
	 */
	private array $protocols = [];

	/**
	 * Get the protocol negotiator for the request, if supported
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function getProtocol(Http\IRequest $httpRequest): IProtocol
	{
		foreach ($this->protocols as $protocol) {
			if ($protocol->isVersion($httpRequest)) {
				return $protocol;
			}
		}

		throw new Exceptions\InvalidArgument('Version not found');
	}

	public function isProtocolEnabled(Http\IRequest $httpRequest): bool
	{
		foreach ($this->protocols as $protocol) {
			if ($protocol->isVersion($httpRequest)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enable support for a specific version of the WebSocket protocol
	 */
	public function enableProtocol(IProtocol $protocol): void
	{
		$this->protocols[$protocol->getVersion()] = $protocol;
	}

	/**
	 * Disable support for a specific WebSocket protocol
	 *
	 * @param string $protocolId The version ID to un-support
	 */
	public function disableProtocol(string $protocolId): void
	{
		unset($this->protocols[$protocolId]);
	}

	/**
	 * Get a string of protocols supported (comma separated)
	 */
	public function getSupportedProtocols(): string
	{
		return implode(',', array_keys($this->protocols));
	}

}
