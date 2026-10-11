<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Devices;

use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Caching as NetteCaching;
use Ramsey\Uuid;

/**
 * Device property repository
 */
final readonly class Repository
{

	use Nette\SmartObject;

	public function __construct(
		private DevicesCaching\Container $moduleCaching,
		private IRepository|null $repository = null,
	)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function find(Uuid\UuidInterface $id): States\DeviceProperty|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Device properties state repository is not registered');
		}

		/** @phpstan-var States\DeviceProperty|null $state */
		$state = $this->moduleCaching->getStateStorageCache()->load(
			$id->toString(),
			function () use ($id): States\DeviceProperty|null {
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
