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

namespace FastyBird\Module\Devices\Models\States\Devices\Async;

use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Caching as NetteCaching;
use Ramsey\Uuid;
use React\Promise;
use Throwable;

/**
 * Asynchronous device property repository
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
		private readonly Models\States\Devices\Repository $fallback,
		private readonly DevicesCaching\Container $moduleCaching,
		private readonly IRepository|null $repository = null,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<States\DeviceProperty|null>
	 *
	 * @interal
	 */
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		if ($this->repository === null) {
			try {
				return Promise\resolve($this->fallback->find($id));
			} catch (Exceptions\NotImplemented $ex) {
				return Promise\reject($ex);
			}
		}

		/** @phpstan-var States\DeviceProperty|null $state */
		$state = $this->moduleCaching->getStateStorageCache()->load($id->toString());

		if ($state !== null) {
			return Promise\resolve($state);
		}

		$deferred = new Promise\Deferred();

		$this->repository->find($id)
			->then(function (States\DeviceProperty|null $state) use ($deferred, $id): void {
				$this->moduleCaching->getStateStorageCache()->save(
					$id->toString(),
					$state,
					[
						NetteCaching\Cache::Tags => [$id->toString()],
					],
				);

				$deferred->resolve($state);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

}
