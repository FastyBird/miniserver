<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Http;

use Exception;
use Fig\Http\Message;
use Nette;
use Nette\Http;
use OverflowException;
use Throwable;
use function array_fill_keys;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_shift;
use function array_splice;
use function count;
use function end;
use function explode;
use function implode;
use function inet_pton;
use function is_array;
use function is_string;
use function key;
use function preg_last_error;
use function preg_match;
use function preg_replace;
use function preg_split;
use function sprintf;
use function strcasecmp;
use function strlen;
use function strncmp;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function time;
use function trim;
use function unpack;
use const PHP_SAPI;

/**
 * HTTP request factory
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Http
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class RequestFactory
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/**
	 * @internal
	 */
	public const CHARS = '\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}';

	/**
	 * Undefined method
	 *
	 * @internal
	 */
	public const METHOD_EXTENDED = 'extended';

	public const EOM = "\r\n\r\n";

	/**
	 * Cookie part names to snake_case array values
	 */
	protected static array $cookieParts = [
		'domain' => 'Domain',
		'path' => 'Path',
		'max_age' => 'Max-Age',
		'expires' => 'Expires',
		'version' => 'Version',
		'secure' => 'Secure',
		'port' => 'Port',
		'discard' => 'Discard',
		'comment' => 'Comment',
		'comment_url' => 'Comment-Url',
		'http_only' => 'HttpOnly',
	];

	/**
	 * The maximum number of bytes the request can be
	 * This is a security measure to prevent attacks
	 */
	private int $maxSize = 4_096;

	private bool $binary = false;

	private array $proxies = [];

	/**
	 * Whether PHP is running with FastCGI or not.
	 */
	private static bool|null $fcgi = null;

	public function __construct()
	{
		if (self::$fcgi === null) {
			self::$fcgi = PHP_SAPI === 'cgi-fcgi';
		}
	}

	public function setBinary(bool $binary = true): void
	{
		$this->binary = $binary;
	}

	public function setProxy(array|string $proxy): void
	{
		$this->proxies = (array) $proxy;
	}

	/**
	 * @throws Throwable
	 */
	public function createHttpRequest(string $packet): IRequest|null
	{
		if (strlen($packet) > $this->maxSize) {
			throw new OverflowException(
				sprintf('Maximum buffer size of %s exceeded parsing HTTP header', $this->maxSize),
			);
		}

		if (!$this->isEom($packet)) {
			return null;
		}

		$parsedHeaders = explode("\r\n", $packet);

		$http = array_shift($parsedHeaders);

		$rawBody = null;

		foreach ($parsedHeaders as $i => $header) {
			if (trim($header) === '') {
				unset($parsedHeaders[$i]);

				$rawBody = trim(implode("\r\n", array_splice($parsedHeaders, $i)));

				break;
			}
		}

		if (preg_match('#^([^\s]+)\s+([^\s]+)\s+HTTP/(1\.(?:0|1))$#i', $http, $matches) === 0) {
			throw new Exception(
				'HTTP headers are not well-formed: %s',
				0,
				$http,
			);
		}

		switch ($method = strtoupper($matches[1])) {
			case Message\RequestMethodInterface::METHOD_DELETE:
			case Message\RequestMethodInterface::METHOD_GET:
			case Message\RequestMethodInterface::METHOD_HEAD:
			case Message\RequestMethodInterface::METHOD_OPTIONS:
			case Message\RequestMethodInterface::METHOD_PATCH:
			case Message\RequestMethodInterface::METHOD_POST:
			case Message\RequestMethodInterface::METHOD_PUT:
				// Nothing to do here
				break;
			default:
				$method = self::METHOD_EXTENDED;
		}

		$url = new Http\Url($matches[2]);

		$httpVersion = (float) $matches[3];

		$headers = [];

		foreach ($parsedHeaders as $header) {
			[$name, $value] = explode(':', $header, 2);
			$headers[strtolower($name)] = trim($value);
		}

		// Check for the Host header
		if (isset($headers['host'])) {
			$url->setScheme('ws');

			if (strpos($headers['host'], ':') !== false) {
				$hostParts = explode(':', $headers['host']);

				$url->setHost(trim($hostParts[0]));
				$url->setPort((int) trim($hostParts[1]));

				if ($url->getPort() === 443) {
					$url->setScheme('wss');
				}
			} else {
				$url->setHost($headers['host']);
			}
		}

		// Check if a query is present
		$path = $matches[2];
		$qpos = strpos($path, '?');

		if ($qpos) {
			$url->setQuery(substr($path, $qpos + 1));
			$url->setPath(substr($path, 0, $qpos));
		}

		$remoteAddr = null;
		$remoteHost = null;
		$proxyParams = [];

		// Use real client address and host if trusted proxy is used
		$usingTrustedProxy = $remoteAddr && array_filter(
			$this->proxies,
			fn ($proxy): bool => $this->ipMatch($remoteAddr, $proxy),
		);

		if ($usingTrustedProxy) {
			if ($headers['http_forwarded'] !== []) {
				$forwardParams = preg_split('/[,;]/', $headers['http_forwarded']);

				foreach ($forwardParams as $forwardParam) {
					[$key, $value] = explode('=', $forwardParam, 2) + [1 => null];
					$proxyParams[strtolower(trim($key))][] = trim($value, " \t\"");
				}

				if (isset($proxyParams['for'])) {
					$address = $proxyParams['for'][0];

					$remoteAddr = strpos($address, '[') === false
						// IPv4
						? explode(':', $address)[0]
						// IPv6
						: substr($address, 1, strpos($address, ']') - 1);
				}

				if (isset($proxyParams['host']) && count($proxyParams['host']) === 1) {
					$host = $proxyParams['host'][0];
					$startingDelimiterPosition = strpos($host, '[');

					//IPv4
					if ($startingDelimiterPosition === false) {
						$remoteHostArr = explode(':', $host);
						$remoteHost = $remoteHostArr[0];

						if (isset($remoteHostArr[1])) {
							$url->setPort((int) $remoteHostArr[1]);
						}

						//IPv6
					} else {
						$endingDelimiterPosition = strpos($host, ']');
						$remoteHost = substr($host, strpos($host, '[') + 1, $endingDelimiterPosition - 1);
						$remoteHostArr = explode(':', substr($host, $endingDelimiterPosition));

						if (isset($remoteHostArr[1])) {
							$url->setPort((int) $remoteHostArr[1]);
						}
					}
				}

				$scheme = (isset($proxyParams['proto']) && count(
					$proxyParams['proto'],
				) === 1)
					? $proxyParams['proto'][0]
					: 'http';

				$url->setScheme(strcasecmp($scheme, 'https') === 0 ? 'https' : 'http');

			} else {
				if ($headers['http_x_forwarded_proto'] !== []) {
					$url->setScheme(strcasecmp($headers['http_x_forwarded_proto'], 'https') === 0 ? 'https' : 'http');
				}

				if ($headers['http_x_forwarded_port'] !== []) {
					$url->setPort((int) $headers['http_x_forwarded_port']);
				}

				if ($headers['http_x_forwarded_for'] !== []) {
					$xForwardedForWithoutProxies = array_filter(
						explode(',', $headers['http_x_forwarded_for']),
						fn ($ip): bool => !array_filter(
							$this->proxies,
							fn ($proxy): bool => $this->ipMatch(trim($ip), $proxy),
						),
					);

					$remoteAddr = trim(end($xForwardedForWithoutProxies));
					$xForwardedForRealIpKey = key($xForwardedForWithoutProxies);
				}

				if (isset($xForwardedForRealIpKey) && $headers['http_x_forwarded_host'] !== []) {
					$xForwardedHost = explode(',', $headers['http_x_forwarded_host']);
					if (isset($xForwardedHost[$xForwardedForRealIpKey])) {
						$remoteHost = trim($xForwardedHost[$xForwardedForRealIpKey]);
					}
				}
			}
		}

		// COOKIE
		$cookies = isset($headers['cookie']) ? $this->parseCookie($headers['cookie']) : ['cookies' => []];
		$cookies = $cookies['cookies'];

		// remove invalid characters
		$reChars = '#^[' . self::CHARS . ']*+\z#u';

		if (!$this->binary) {
			$list = [&$cookies];

			foreach ($list as $key => $val) {
				foreach ($val as $k => $v) {
					if (is_string($k) && (!preg_match($reChars, $k) || preg_last_error())) {
						unset($list[$key][$k]);

					} elseif (is_array($v)) {
						$list[$key][$k] = $v;
						$list[] = &$list[$key][$k];

					} else {
						$list[$key][$k] = (string) preg_replace('#[^' . self::CHARS . ']+#u', '', $v);
					}
				}
			}

			unset($list, $key, $val, $k, $v);
		}

		$request = new Request(
			new Http\UrlScript($url),
			[],
			[],
			$cookies,
			$headers,
			$method,
			$remoteAddr,
			$remoteHost,
			static fn (): string|null => $rawBody,
		);

		$request->setProtocolVersion($httpVersion);

		return $request;
	}

	/**
	 * Determine if the message has been buffered as per the HTTP specification
	 */
	private function isEom(string $message): bool
	{
		return (bool) strpos($message, self::EOM);
	}

	private function parseCookie(string $cookie): array
	{
		// Explode the cookie string using a series of semicolons
		$pieces = array_filter(array_map('trim', explode(';', $cookie)));

		// The name of the cookie (first kvp) must include an equal sign.
		if ($pieces === [] || !strpos($pieces[0], '=')) {
			return [];
		}

		// Create the default return array
		$data = array_merge(array_fill_keys(array_keys(self::$cookieParts), null), [
			'cookies' => [],
			'data' => [],
			'path' => null,
			'http_only' => false,
			'discard' => false,
			'domain' => null,
		]);

		$foundNonCookies = 0;

		// Add the cookie pieces into the parsed data array
		foreach ($pieces as $part) {

			$cookieParts = explode('=', $part, 2);
			$key = trim($cookieParts[0]);

			// A single value (e.g. secure, httpOnly), otherwise strip wrapping quotes
			$value = count($cookieParts) === 1
				? true
				: trim($cookieParts[1], " \n\r\t\0\x0B\"");

			// Only check for non-cookies when cookies have been found
			if ($data['cookies'] !== []) {
				foreach (self::$cookieParts as $mapValue => $search) {
					if (!strcasecmp($search, $key)) {
						$data[$mapValue] = $mapValue === 'port' ? array_map('trim', explode(',', $value)) : $value;
						$foundNonCookies++;

						continue 2;
					}
				}
			}

			// If cookies have not yet been retrieved, or this value was not found in the pieces array, treat it as a
			// cookie. IF non-cookies have been parsed, then this isn't a cookie, it's cookie data. Cookies then data.
			$data[$foundNonCookies ? 'data' : 'cookies'][$key] = $value;
		}

		// Calculate the expires date
		if (!$data['expires'] && $data['max_age']) {
			$data['expires'] = time() + (int) $data['max_age'];
		}

		// Check path attribute according RFC6265 http://tools.ietf.org/search/rfc6265#section-5.2.4
		// "If the attribute-value is empty or if the first character of the
		// attribute-value is not %x2F ("/"):
		//   Let cookie-path be the default-path.
		// Otherwise:
		//   Let cookie-path be the attribute-value."
		if (!$data['path'] || substr($data['path'], 0, 1) !== '/') {
			$data['path'] = '/';
		}

		return $data;
	}

	/**
	 * Is IP address in CIDR block?
	 */
	private function ipMatch($ip, $mask): bool
	{
		[$mask, $size] = explode('/', $mask . '/');

		$tmp = static fn ($n) => sprintf('%032b', $n);
		$ip = implode('', array_map($tmp, unpack('N*', inet_pton($ip))));
		$mask = implode('', array_map($tmp, unpack('N*', inet_pton($mask))));
		$max = strlen($ip);

		if (!$max || $max !== strlen($mask) || (int) $size < 0 || (int) $size > $max) {
			return false;
		}

		return strncmp($ip, $mask, $size === '' ? $max : (int) $size) === 0;
	}

}
