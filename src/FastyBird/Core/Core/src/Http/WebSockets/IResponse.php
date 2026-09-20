<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\WebSockets;

/**
 * HTTP response formater interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Http
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IResponse
{

	// HTTP 1.1 response code
	public const
		S101_SWITCHING_PROTOCOLS = 101,
		S200_OK = 200,
		S400_BAD_REQUEST = 400,
		S413_REQUEST_ENTITY_TOO_LARGE = 413,
		S500_INTERNAL_SERVER_ERROR = 500;

	/**
	 * Sets HTTP response code.
	 */
	public function setCode(int $code, string|null $reason = null): void;

	/**
	 * Returns HTTP response code
	 */
	public function getCode(): int;

	/**
	 * Adds HTTP header
	 *
	 * @param string $name  header name
	 * @param string $value header value
	 */
	public function addHeader(string $name, string $value): void;

	/**
	 * Returns value of an HTTP header
	 */
	public function getHeader(string $header, mixed $default = null): mixed;

	/**
	 * Returns a list of headers to sent
	 *
	 * @return array (name => value)
	 */
	public function getHeaders(): array;

	public function getReason(): string;

	public function setBody(string|null $body = null): void;

}
