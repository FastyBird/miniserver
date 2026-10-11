<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Exceptions;
use InvalidArgumentException;
use RingCentral\Psr7;
use RuntimeException;

/**
 * HTTP request
 */
final class Request extends Psr7\Request
{

	/**
	 * @param array<string, int|string|array<string>>|null $headers
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $method,
		string $uri,
		array|null $headers = null,
		string|null $body = null,
	)
	{
		try {
			parent::__construct($method, $uri, $headers ?? [], $body);
		} catch (InvalidArgumentException $ex) {
			throw new Exceptions\InvalidArgument('Request could not be created', $ex->getCode(), $ex);
		}
	}

	public function getContent(): string|null
	{
		try {
			$content = $this->getBody()->getContents();

			$this->getBody()->rewind();

			return $content;
		} catch (RuntimeException) {
			return null;
		}
	}

}
