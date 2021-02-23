<?php declare(strict_types = 1);

/**
 * Application.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Application
 * @since          0.1.0
 *
 * @date           23.02.21
 */

namespace FastyBird\MiniServer\Application;

use IPub\SlimRouter\Routing;
use Nette;
use Psr\Http\Message\ResponseInterface;
use Sunrise\Http\ServerRequest\ServerRequestFactory;
use Throwable;

class Application implements IApplication
{

	use Nette\SmartObject;

	private const UNIQUE_HEADERS = [
		'content-type',
	];

	/** @var Routing\IRouter */
	private Routing\IRouter $router;

	public function __construct(
		Routing\IRouter $router
	) {
		$this->router = $router;
	}

	/**
	 * Dispatch application in middleware cycle!
	 *
	 * @return string|int|bool|void|ResponseInterface|null
	 */
	public function run()
	{
		$request = ServerRequestFactory::fromGlobals();

		try {
			$response = $this->router->handle($request);

		} catch (Throwable $e) {
			throw $e;
		}

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
	 * @param string[] $values
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

	protected function sendBody(ResponseInterface $response): void
	{
		$stream = $response->getBody();
		$stream->rewind();

		while (!$stream->eof()) {
			echo $stream->read(8192);
		}
	}

}
