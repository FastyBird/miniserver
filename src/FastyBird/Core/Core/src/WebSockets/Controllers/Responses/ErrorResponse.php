<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers\Responses;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use Nette;
use Nette\Utils;
use Override;
use function is_array;
use function sprintf;

/**
 * Communication error response
 */
final class ErrorResponse implements ControllerResponse
{

	private Utils\ArrayHash $headers;

	private int $statusCode;

	/**
	 * @throws CoreExceptions\InvalidArgument
	 */
	public function __construct(int $statusCode, Utils\ArrayHash|array|null $headers = null)
	{
		$this->headers = new Utils\ArrayHash();

		$this->setStatus($statusCode);

		if ($headers) {
			if (is_array($headers)) {
				$this->setHeaders($headers);

			} elseif ($headers instanceof Utils\ArrayHash) {
				$this->setHeaders((array) $headers);

			} else {
				throw new WebSocketsExceptions\BadResponse('Invalid headers argument received');
			}
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 */
	public function setStatus(int $statusCode): void
	{
		if ($statusCode < 100 || $statusCode > 599) {
			throw new CoreExceptions\InvalidArgument(sprintf('Bad HTTP response "%s".', $statusCode));
		}

		$this->statusCode = $statusCode;
	}

	public function setHeaders(array $headers): void
	{
		$this->headers = new Utils\ArrayHash();

		foreach ($headers as $key => $value) {
			$this->addHeader($key, $value);
		}
	}

	public function addHeader(string $header, mixed $value): void
	{
		$this->headers->offsetSet($header, $value);
	}

	#[Override]
	public function create(): array|null
	{
		$headers = [];
		$headers[] = 'HTTP/1.1 ' . $this->statusCode;

		foreach ($this->headers as $key => $value) {
			$headers[] = $key . ':' . $value;
		}

		return $headers;
	}

	/**
	 * @throws Nette\Utils\JsonException
	 */
	#[Override]
	public function __toString(): string
	{
		return Utils\Json::encode($this->create());
	}

}
