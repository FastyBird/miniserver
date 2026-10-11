<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Caching;

use Nette\Caching;

/**
 * Module cache container
 */
readonly class Container
{

	public function __construct(
		private Caching\Cache $configurationBuilderCache,
		private Caching\Cache $configurationRepositoryCache,
	)
	{
	}

	public function getConfigurationBuilderCache(): Caching\Cache
	{
		return $this->configurationBuilderCache;
	}

	public function getConfigurationRepositoryCache(): Caching\Cache
	{
		return $this->configurationRepositoryCache;
	}

}
