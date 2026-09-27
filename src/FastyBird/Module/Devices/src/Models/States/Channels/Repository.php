<?php declare(strict_types = 1);

/**
 * Repository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Module\Devices\Models\States\Channels;

use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Caching as NetteCaching;
use Ramsey\Uuid;

/**
 * Channel property repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesCaching\Container $moduleCaching,
		private readonly IRepository|null $repository = null,
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
