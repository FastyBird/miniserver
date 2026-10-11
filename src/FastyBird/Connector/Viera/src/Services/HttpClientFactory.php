<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Services;

use GuzzleHttp;
use InvalidArgumentException;
use React\EventLoop;
use React\Http;
use React\Socket\Connector;

/**
 * HTTP client factory
 */
final class HttpClientFactory
{

	private const int CONNECTION_TIMEOUT = 10;

	private GuzzleHttp\Client|null $client = null;

	private Http\Io\Transaction|null $asyncClient = null;

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @return ($async is true ? Http\Io\Transaction : GuzzleHttp\Client)
	 *
	 * @throws InvalidArgumentException
	 */
	public function create(bool $async = true): GuzzleHttp\Client|Http\Io\Transaction
	{
		if ($async) {
			if ($this->asyncClient === null) {
				$connector = new Connector(
					[
						'timeout' => self::CONNECTION_TIMEOUT,
					],
					$this->eventLoop,
				);

				$this->asyncClient = new Http\Io\Transaction(
					Http\Io\Sender::createFromLoop($this->eventLoop, $connector),
					$this->eventLoop,
				);
			}

			return $this->asyncClient;
		} else {
			if ($this->client === null) {
				$this->client = new GuzzleHttp\Client();
			}

			return $this->client;
		}
	}

}
