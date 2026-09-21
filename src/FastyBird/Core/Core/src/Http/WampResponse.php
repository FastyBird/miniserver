<?php declare(strict_types = 1);

namespace FastyBird\Core\Http;

use FastyBird\Core\Exceptions;
use Nette;
use function array_key_exists;
use function sprintf;
use function strlen;

/**
 * WAMP transport HTTP handshake response formatter
 *
 * @package        FastyBird:Core!
 * @subpackage     Http
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class WampResponse implements IResponse
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/** @var int HTTP response code */
	private int $code = self::S200_OK;

	private string $reason;

	private string|null $body = null;

	/** @var array Array of reason phrases and their corresponding status codes */
	private static array $statusTexts = [
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Reserved for WebDAV advanced collections expired proposal',
		426 => 'Upgrade required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates (Experimental)',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
	];

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(int $code, private array $headers = [], string|null $body = null)
	{
		$this->setCode($code);
		$this->setBody($body);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function setCode(int $code, string|null $reason = null): void
	{
		if ($code < 100 || $code > 599) {
			throw new Exceptions\InvalidArgument(sprintf('Bad HTTP response "%s"', $code));
		}

		$this->code = $code;

		$this->reason = ($reason ?? (array_key_exists(
			$code,
			self::$statusTexts,
		) ? self::$statusTexts[$code] : 'Unknown status'));
	}

	public function getCode(): int
	{
		return $this->code;
	}

	public function addHeader(string $name, string $value): void
	{
		$this->headers[$name] = $value;
	}

	public function getHeader(string $header, mixed $default = null): mixed
	{
		if (isset($this->headers[$header])) {
			return $this->headers[$header];
		}

		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getReason(): string
	{
		return $this->reason;
	}

	public function setBody(string|null $body = null): void
	{
		$this->body = $body;
	}

	public function __toString(): string
	{
		$message = 'HTTP/1.1 ' . $this->code . ' ' . $this->reason;

		foreach ($this->getHeaders() as $header => $value) {
			$message .= "\r\n" . $header . ': ' . $value;
		}

		$message .= "\r\n";

		if ($this->body !== null && strlen($this->body) < 2_097_152) {
			$message .= $this->body;
		}

		$message .= "\r\n";

		return $message;
	}

}
