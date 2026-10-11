<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Channels\Async;

use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette\Caching as NetteCaching;
use Ramsey\Uuid;
use React\Promise;
use Throwable;

/**
 * Asynchronous channel property repository
 */
final readonly class Repository
{

	public function __construct(
		private Models\States\Channels\Repository $fallback,
		private DevicesCaching\Container $moduleCaching,
		private IRepository|null $repository = null,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty|null>
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

		/** @phpstan-var States\ChannelProperty|null $state */
		$state = $this->moduleCaching->getStateStorageCache()->load($id->toString());

		if ($state !== null) {
			return Promise\resolve($state);
		}

		$deferred = new Promise\Deferred();

		$this->repository->find($id)
			->then(function (States\ChannelProperty|null $state) use ($deferred, $id): void {
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
