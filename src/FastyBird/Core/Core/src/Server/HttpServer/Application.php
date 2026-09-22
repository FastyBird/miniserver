<?php declare(strict_types = 1);

/**
 * Application.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Application
 * @since          1.0.0
 *
 * @date           23.02.21
 */

namespace FastyBird\Core\Server\HttpServer;

use FastyBird\Core\Events;
use FastyBird\Core\Routing;
use Nette;
use Psr\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Sunrise\Http\Message\ServerRequestFactory;
use function header;
use function in_array;
use function sprintf;
use function str_replace;
use function strtolower;
use function ucwords;

/**
 * Base application service
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Application
{

	use Nette\SmartObject;

	private const array UNIQUE_HEADERS = [
		'content-type',
	];

	public function __construct(
		private readonly Routing\IRouter $router,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * Dispatch application in middleware cycle!
	 *
	 * @throws RuntimeException
	 */
	public function run(): ResponseInterface
	{
		$request = ServerRequestFactory::fromGlobals();

		$this->dispatcher?->dispatch(new Events\HttpServerRequest($request));

		$response = $this->router->handle($request);

		$this->dispatcher?->dispatch(new Events\HttpServerResponse($request, $response));

		$this->sendStatus($response);
		$this->sendHeaders($response);
		$this->sendBody($response);

		return $response;
	}

	protected function sendStatus(ResponseInterface $response): void
	{
		$version = $response->getProtocolVersion();
		$status = $response->getStatusCode();
		$phrase = $response->getReasonPhrase();

		header(sprintf('HTTP/%s %s %s', $version, $status, $phrase));
	}

	protected function sendHeaders(ResponseInterface $response): void
	{
		foreach ($response->getHeaders() as $name => $values) {
			$this->sendHeader($name, $values);
		}
	}

	/**
	 * @param array<string> $values
	 */
	protected function sendHeader(string $name, array $values): void
	{
		$name = str_replace('-', ' ', $name);
		$name = ucwords($name);
		$name = str_replace(' ', '-', $name);

		$replace = in_array(strtolower($name), self::UNIQUE_HEADERS, true);

		foreach ($values as $value) {
			header(sprintf('%s: %s', $name, $value), $replace);
		}
	}

	/**
	 * @throws RuntimeException
	 */
	protected function sendBody(ResponseInterface $response): void
	{
		$stream = $response->getBody();
		$stream->rewind();

		while (!$stream->eof()) {
			echo $stream->read(8_192);
		}
	}

}
