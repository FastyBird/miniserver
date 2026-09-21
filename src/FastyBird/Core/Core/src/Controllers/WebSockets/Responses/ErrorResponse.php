<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets\Responses;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\WebSockets as WebSocketsExceptions;
use Nette;
use Nette\Utils;
use function is_array;
use function sprintf;

/**
 * Communication error response
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Responses
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class ErrorResponse implements IResponse
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	private Utils\ArrayHash $headers;

	private int $statusCode;

	/**
	 * @throws Exceptions\InvalidArgument
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
	 * @throws Exceptions\InvalidArgument
	 */
	public function setStatus(int $statusCode): void
	{
		if ($statusCode < 100 || $statusCode > 599) {
			throw new Exceptions\InvalidArgument(sprintf('Bad HTTP response "%s".', $statusCode));
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
	public function __toString(): string
	{
		return Utils\Json::encode($this->create());
	}

}
