<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\DI\Config;

/**
 * The fbCore > api > meta structure of Api\DI\Config
 */
final readonly class Meta
{

	/**
	 * @param string|array<mixed> $author
	 */
	public function __construct(
		public readonly string|array $author,
		public readonly string|null $copyright,
	)
	{
	}

}
