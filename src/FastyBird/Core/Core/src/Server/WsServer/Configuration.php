<?php declare(strict_types = 1);

namespace FastyBird\Core\Server\WsServer;

use Nette;

/**
 * WebSockets server configuration container
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Server
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Configuration
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public function __construct(
		private int $port = 8_080,
		private string $address = '0.0.0.0',
		private bool $enableSSL = false,
		private array $sslSettings = [],
	)
	{
	}

	public function setPort(int $port): void
	{
		$this->port = $port;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function setAddress(string $address): void
	{
		$this->address = $address;
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	public function isSslEnabled(): bool
	{
		return $this->enableSSL;
	}

	public function getSslConfiguration(): array
	{
		return $this->sslSettings;
	}

}
