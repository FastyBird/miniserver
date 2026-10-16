<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers\Responses;

use FastyBird\Core\Exceptions as CoreExceptions;
use Nette;
use Nette\Utils;
use Override;
use function sprintf;

/**
 * Communication error response
 */
final class ErrorResponse implements ControllerResponse
{

	/** @var array<string, mixed> */
	private array $headers = [];

	private int $statusCode;

	/**
	 * @param array<string, mixed>|null $headers
	 *
	 * @throws CoreExceptions\InvalidArgument
	 */
	public function __construct(int $statusCode, array|null $headers = null)
	{
		$this->setStatus($statusCode);

		if ($headers !== null && $headers !== []) {
			$this->setHeaders($headers);
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

	/**
	 * @param array<string, mixed> $headers
	 */
	public function setHeaders(array $headers): void
	{
		$this->headers = [];

		foreach ($headers as $key => $value) {
			$this->addHeader($key, $value);
		}
	}

	public function addHeader(string $header, mixed $value): void
	{
		$this->headers[$header] = $value;
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
