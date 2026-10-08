<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\DI;

use Nette\DI;

/**
 * The fbCore > webSockets section, which WebSocketsExtension::getConfigSchema() declares and
 * casts to this class (census T10.2)
 */
final readonly class Config
{

	/**
	 * @param array<mixed> $routes
	 * @param array<mixed> $mapping
	 */
	public function __construct(
		public readonly Config\Storage $storage,
		public readonly Config\Server $server,
		public readonly array $routes,
		public readonly array $mapping,
		public readonly string|DI\Definitions\Statement|null $loop,
		public readonly Config\Access $access,
	)
	{
	}

}
