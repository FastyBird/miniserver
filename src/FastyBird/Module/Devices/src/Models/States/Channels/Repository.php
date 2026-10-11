<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Channels;

use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\States;
use Nette\Caching as NetteCaching;
use Ramsey\Uuid;

/**
 * Channel property repository
 */
final readonly class Repository
{

	public function __construct(
		private DevicesCaching\Container $moduleCaching,
		private Reader|null $repository = null,
	)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function find(Uuid\UuidInterface $id): States\ChannelProperty|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Channel properties state repository is not registered');
		}

		/** @phpstan-var States\ChannelProperty|null $state */
		$state = $this->moduleCaching->getStateStorageCache()->load(
			$id->toString(),
			function () use ($id): States\ChannelProperty|null {
				if ($this->repository === null) {
					return null;
				}

				return $this->repository->find($id);
			},
			[
				NetteCaching\Cache::Tags => [$id->toString()],
			],
		);

		return $state;
	}

}
