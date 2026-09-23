<?php declare(strict_types = 1);

namespace FastyBird\Core\Routing;

use Closure;
use FastyBird\Core\Controllers\WebSockets as Application;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use Nette\Utils;
use Override;
use function array_flip;
use function array_key_exists;
use function array_pop;
use function array_reverse;
use function array_unshift;
use function call_user_func;
use function count;
use function explode;
use function http_build_query;
use function ini_get;
use function ip2long;
use function is_array;
use function is_scalar;
use function is_string;
use function lcfirst;
use function ltrim;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function rawurldecode;
use function rawurlencode;
use function rtrim;
use function sprintf;
use function str_replace;
use function strlen;
use function strncmp;
use function strpbrk;
use function strrpos;
use function strtolower;
use function strtr;
use function substr;
use function trim;
use function ucwords;

/**
 * The bidirectional WAMP router for a single route mask
 */
final class WampRoute implements IWampRouter
{

	public const string CONTROLLER_KEY = 'controller';

	public const string MODULE_KEY = 'module';

	/**
	 * Url type
	 *
	 * @internal
	 */
	public const int HOST = 1;

	public const int PATH = 2;

	public const int RELATIVE = 3;

	/**
	 * Keys used in {@link WampRoute::$styles} or metadata {@link WampRoute::__construct}
	 */
	public const string VALUE = 'value';

	public const string PATTERN = 'pattern';

	public const string FILTER_IN = 'filterIn';

	public const string FILTER_OUT = 'filterOut';

	public const string FILTER_TABLE = 'filterTable';

	public const string FILTER_STRICT = 'filterStrict';

	/**
	 * Fixity types - how to handle default value? {@link WampRoute::$metadata}
	 *
	 * @internal
	 */
	public const int OPTIONAL = 0;

	public const int PATH_OPTIONAL = 1;

	public const int CONSTANT = 2;

	public static array $styles = [
		'#' => [
			// default style for path parameters
			self::PATTERN => '[^/]+',
			self::FILTER_OUT => [self::class, 'param2path'],
		],
		'?#' => [
			// default style for query parameters
		],
		'module' => [
			self::PATTERN => '[a-z][a-z0-9.-]*',
			self::FILTER_IN => [self::class, 'path2controller'],
			self::FILTER_OUT => [self::class, 'controller2path'],
		],
		'controller' => [
			self::PATTERN => '[a-z][a-z0-9.-]*',
			self::FILTER_IN => [self::class, 'path2controller'],
			self::FILTER_OUT => [self::class, 'controller2path'],
		],
		'action' => [
			self::PATTERN => '[a-z][a-z0-9-]*',
			self::FILTER_IN => [self::class, 'dash2Camel'],
			self::FILTER_OUT => [self::class, 'camel2Dash'],
		],
		'method' => [
			self::PATTERN => '[a-z][a-z0-9-]*',
			self::FILTER_IN => [self::class, 'dash2Camel'],
			self::FILTER_OUT => [self::class, 'camel2Dash'],
		],
		'?module' => [],
		'?controller' => [],
		'?action' => [],
		'?method' => [],
	];

	private string $mask;

	/** @var int HOST, PATH, RELATIVE */
	private int $type;

	private array $sequence = [];

	/**
	 * Regular expression pattern
	 */
	private string $re;

	/**
	 * Parameter aliases in regular expression
	 *
	 * @var array<string>
	 */
	private array $aliases = [];

	/**
	 * Array of [value & fixity, filterIn, filterOut]
	 */
	private array $metadata = [];

	private array $xlat = [];

	/**
	 * http | https
	 */
	private string $scheme;

	private array $allowedOrigins = [];

	private string $httpHost;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(string $mask, string|array|callable $metadata)
	{
		if (is_string($metadata)) {
			[$controller, $action] = $this->splitName($metadata);

			if (!$controller) {
				throw new Exceptions\InvalidArgument(
					sprintf(
						'Second argument must be array or string in format Controller:action, "%s" given.',
						$metadata,
					),
				);
			}

			$metadata = [self::CONTROLLER_KEY => $controller];

			if ($action !== '') {
				$metadata[Application\Controller\Controller::ACTION_KEY] = $action;
			}
		} elseif ($metadata instanceof Closure) {
			$metadata = [
				self::CONTROLLER_KEY => 'IPub:WebSocket',
				'callback' => $metadata,
			];
		}

		$this->setMask($mask, $metadata);
	}

	/**
	 * Maps command line arguments to a Request object
	 *
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function match(Http\IRequest $httpRequest): Application\Request|null
	{
		// Combine with precedence: mask (params in URL-path), fixity, query, (post,) defaults

		// 1) URL MASK
		$url = $httpRequest->getUrl();
		$re = $this->re;

		if ($this->type === self::HOST) {
			$host = $url->getHost();
			$path = '//' . $host . $url->getPath();
			$parts = ip2long($host) ? [$host] : array_reverse(explode('.', $host));
			$re = strtr($re, [
				'/%basePath%/' => preg_quote($url->getBasePath(), '#'),
				'%tld%' => preg_quote($parts[0], '#'),
				'%domain%' => preg_quote(
					isset($parts[1]) ? sprintf('%s.%s', $parts[1], $parts[0]) : $parts[0],
					'#',
				),
				'%sld%' => preg_quote($parts[1] ?? '', '#'),
				'%host%' => preg_quote($host, '#'),
			]);

		} elseif ($this->type === self::RELATIVE) {
			$basePath = $url->getBasePath();

			if (strncmp($url->getPath(), $basePath, strlen($basePath)) !== 0) {
				return null;
			}

			$path = substr($url->getPath(), strlen($basePath));

		} else {
			$path = $url->getPath();
		}

		if ($path !== '') {
			$path = rtrim(rawurldecode($path), '/') . '/';
		}

		if (!$matches = Utils\Strings::match($path, $re)) {
			// stop, not matched
			return null;
		}

		// Assigns matched values to parameters
		$params = [];

		foreach ($matches as $k => $v) {
			if (is_string($k) && $v !== '') {
				$params[$this->aliases[$k]] = $v;
			}
		}

		// 2) CONSTANT FIXITY
		foreach ($this->metadata as $name => $meta) {
			if (!isset($params[$name]) && isset($meta['fixity']) && $meta['fixity'] !== self::OPTIONAL) {
				$params[$name] = null; // cannot be overwriten in 3) and detected by isset() in 4)
			}
		}

		// 3) QUERY
		if ($this->xlat) {
			$params += self::renameKeys($httpRequest->getQuery(), array_flip($this->xlat));

		} else {
			$params += $httpRequest->getQuery();
		}

		// 4) APPLY FILTERS & FIXITY
		foreach ($this->metadata as $name => $meta) {
			if (isset($params[$name])) {
				if (isset($meta[self::FILTER_TABLE][$params[$name]])) { // applies filterTable only to scalar parameters
					$params[$name] = $meta[self::FILTER_TABLE][$params[$name]];

				} elseif (isset($meta[self::FILTER_TABLE]) && $meta[self::FILTER_STRICT] !== []) {
					return null; // rejected by filterTable
				} elseif (isset($meta[self::FILTER_IN])) { // applies filterIn only to scalar parameters
					$params[$name] = call_user_func($meta[self::FILTER_IN], (string) $params[$name]);

					if ($params[$name] === null && !isset($meta['fixity'])) {
						return null; // rejected by filter
					}
				}
			} elseif (isset($meta['fixity'])) {
				$params[$name] = $meta[self::VALUE];
			}
		}

		if (isset($this->metadata[null][self::FILTER_IN])) {
			$params = call_user_func($this->metadata[null][self::FILTER_IN], $params);

			if ($params === null) {
				return null;
			}
		}

		// 5) BUILD Request
		if (!isset($params[self::CONTROLLER_KEY])) {
			throw new Exceptions\InvalidState('Missing controller in route definition.');
		} elseif (!is_string($params[self::CONTROLLER_KEY])) {
			return null;
		}

		$controller = $params[self::CONTROLLER_KEY];

		unset($params[self::CONTROLLER_KEY]);

		if (isset($this->metadata[self::MODULE_KEY])) {
			$controller = (isset($params[self::MODULE_KEY]) ? $params[self::MODULE_KEY] . ':' : '') . $controller;
			unset($params[self::MODULE_KEY]);
		}

		return new Application\Request($controller, $params);
	}

	/**
	 * Constructs absolute URL from Request object
	 */
	#[Override]
	public function constructUrl(Application\IRequest $appRequest): string|null
	{
		$params = $appRequest->getParameters();
		$metadata = $this->metadata;

		$controller = $appRequest->getControllerName();
		$params[self::CONTROLLER_KEY] = $controller;

		if (isset($metadata[self::MODULE_KEY])) { // try split into module and [submodule:]controller parts
			$module = $metadata[self::MODULE_KEY];

			$a
				= isset($module['fixity'])
				&& strncmp(
					$controller,
					$module[self::VALUE] . ':',
					strlen($module[self::VALUE]) + 1,
				) === 0
			 ? strlen($module[self::VALUE]) : strrpos($controller, ':');

			if ($a === false) {
				$params[self::MODULE_KEY] = isset($module[self::VALUE]) ? '' : null;

			} else {
				$params[self::MODULE_KEY] = substr($controller, 0, $a);
				$params[self::CONTROLLER_KEY] = substr($controller, $a + 1);
			}
		}

		if (isset($metadata[null][self::FILTER_OUT])) {
			$params = call_user_func($metadata[null][self::FILTER_OUT], $params);

			if ($params === null) {
				return null;
			}
		}

		foreach ($metadata as $name => $meta) {
			if (!isset($params[$name])) {
				continue; // retains null values
			}

			if (isset($meta['fixity'])) {
				if ($params[$name] === false) {
					$params[$name] = '0';

				} elseif (is_scalar($params[$name])) {
					$params[$name] = (string) $params[$name];
				}

				if ($params[$name] === $meta[self::VALUE]) { // remove default values; null values are retain
					unset($params[$name]);

					continue;
				} elseif ($meta['fixity'] === self::CONSTANT) {
					return null; // missing or wrong parameter '$name'
				}
			}

			if (is_scalar($params[$name]) && isset($meta['filterTable2'][$params[$name]])) {
				$params[$name] = $meta['filterTable2'][$params[$name]];

			} elseif (isset($meta['filterTable2']) && $meta[self::FILTER_STRICT] !== []) {
				return null;
			} elseif (isset($meta[self::FILTER_OUT])) {
				$params[$name] = call_user_func($meta[self::FILTER_OUT], $params[$name]);
			}

			if (isset($meta[self::PATTERN]) && !preg_match($meta[self::PATTERN], rawurldecode($params[$name]))) {
				return null; // pattern not match
			}
		}

		// Compositing path
		$sequence = $this->sequence;
		$brackets = [];
		$required = null; // null for auto-optional
		$url = '';
		$i = count($sequence) - 1;

		do {
			$url = $sequence[$i] . $url;
			if ($i === 0) {
				break;
			}

			$i--;

			$name = $sequence[$i];
			$i--; // parameter name

			if ($name === ']') { // opening optional part
				$brackets[] = $url;

			} elseif ($name[0] === '[') { // closing optional part
				$tmp = array_pop($brackets);

				if ($required < count($brackets) + 1) { // is this level optional?
					if ($name !== '[!') { // and not "required"-optional
						$url = $tmp;
					}
				} else {
					$required = count($brackets);
				}
			} elseif ($name[0] === '?') { // "foo" parameter
				continue;
			} elseif (isset($params[$name]) && $params[$name] !== '') { // intentionally ==
				$required = count($brackets); // make this level required
				$url = $params[$name] . $url;
				unset($params[$name]);

			} elseif (isset($metadata[$name]['fixity'])) { // has default value?
				// auto-optional
				$url = ($required === null && !$brackets) ? '' : $metadata[$name]['defOut'] . $url;
			} else {
				return null; // missing parameter '$name'
			}
		} while (true);

		// build query string
		if ($this->xlat) {
			$params = self::renameKeys($params, $this->xlat);
		}

		$sep = ini_get('arg_separator.input');
		$query = http_build_query($params, '', $sep ? $sep[0] : '&');

		if ($query !== '') { // intentionally ==
			$url .= '?' . $query;
		}

		return $url;
	}

	/**
	 * Parse mask and array of default values; initializes object
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function setMask(string $mask, array $metadata): void
	{
		$this->mask = $mask;

		// Detect '//host/path' vs. '/abs. path' vs. 'relative path'
		if (preg_match('#(?:(https?):)?(//.*)#A', $mask, $m)) {
			$this->type = self::HOST;
			[, $this->scheme, $mask] = $m;

		} elseif (substr($mask, 0, 1) === '/') {
			$this->type = self::PATH;

		} else {
			$this->type = self::RELATIVE;
		}

		foreach ($metadata as $name => $meta) {
			if (!is_array($meta)) {
				$metadata[$name] = $meta = [self::VALUE => $meta];
			}

			if (array_key_exists(self::VALUE, $meta)) {
				if (is_scalar($meta[self::VALUE])) {
					$metadata[$name][self::VALUE] = (string) $meta[self::VALUE];
				}

				$metadata[$name]['fixity'] = self::CONSTANT;
			}
		}

		if (strpbrk($mask, '?<>[]') === false) {
			$this->re = '#' . preg_quote($mask, '#') . '/?\z#A';
			$this->sequence = [$mask];
			$this->metadata = $metadata;

			return;
		}

		// PARSE MASK
		// <parameter-name[=default] [pattern]> or [ or ] or ?...
		$parts = Utils\Strings::split($mask, '/<([^<>= ]+)(=[^<> ]*)? *([^<>]*)>|(\[!?|\]|\s*\?.*)/');

		$this->xlat = [];
		$i = count($parts) - 1;

		// PARSE QUERY PART OF MASK
		if (isset($parts[$i - 1]) && substr(ltrim($parts[$i - 1]), 0, 1) === '?') {
			// name=<parameter-name [pattern]>
			$matches = Utils\Strings::matchAll($parts[$i - 1], '/(?:([a-zA-Z0-9_.-]+)=)?<([^> ]+) *([^>]*)>/');

			foreach ($matches as [, $param, $name, $pattern]) { // $pattern is not used
				$meta = self::$styles['?' . $name] ?? self::$styles['?#'];

				if (isset($metadata[$name])) {
					$meta = $metadata[$name] + $meta;
				}

				if (array_key_exists(self::VALUE, $meta)) {
					$meta['fixity'] = self::OPTIONAL;
				}

				unset($meta['pattern']);

				$meta['filterTable2'] = (
					!array_key_exists(self::FILTER_TABLE, $meta)
					|| $meta[self::FILTER_TABLE] === []
					|| $meta[self::FILTER_TABLE] === null
				)
					? null
					: array_flip($meta[self::FILTER_TABLE]);

				$metadata[$name] = $meta;
				if ($param !== '') {
					$this->xlat[$name] = $param;
				}
			}

			$i -= 5;
		}

		// PARSE PATH PART OF MASK
		$brackets = 0; // optional level
		$re = '';
		$sequence = [];
		$autoOptional = true;
		$aliases = [];

		do {
			$part = $parts[$i]; // part of path

			if (strpbrk($part, '<>') !== false) {
				throw new Exceptions\InvalidArgument(sprintf('Unexpected "%s" in mask "%s".', $part, $mask));
			}

			array_unshift($sequence, $part);

			$re = preg_quote($part, '#') . $re;

			if ($i === 0) {
				break;
			}

			$i--;

			$part = $parts[$i]; // [ or ]

			if ($part === '[' || $part === ']' || $part === '[!') {
				$brackets += $part[0] === '[' ? -1 : 1;
				if ($brackets < 0) {
					throw new Exceptions\InvalidArgument(sprintf('Unexpected "%s" in mask "%s".', $part, $mask));
				}

				array_unshift($sequence, $part);

				$re = ($part[0] === '[' ? '(?:' : ')?') . $re;
				$i -= 4;

				continue;
			}

			$pattern = trim($parts[$i]);
			$i--; // validation condition (as regexp)
			$default = $parts[$i];
			$i--; // default value
			$name = $parts[$i];
			$i--; // parameter name
			array_unshift($sequence, $name);

			if ($name[0] === '?') { // "foo" parameter
				$name = substr($name, 1);
				$re = $pattern
					? '(?:' . preg_quote($name, '#') . sprintf('|%s)%s', $pattern, $re)
					: preg_quote($name, '#') . $re;
				$sequence[1] = $name . $sequence[1];

				continue;
			}

			// pattern, condition & metadata
			$meta = self::$styles[$name] ?? self::$styles['#'];

			if (isset($metadata[$name])) {
				$meta = $metadata[$name] + $meta;
			}

			if ($pattern === '' && isset($meta[self::PATTERN])) {
				$pattern = $meta[self::PATTERN];
			}

			if ($default !== '') {
				$meta[self::VALUE] = substr($default, 1);
				$meta['fixity'] = self::PATH_OPTIONAL;
			}

			$meta['filterTable2'] = (
				!array_key_exists(self::FILTER_TABLE, $meta)
				|| $meta[self::FILTER_TABLE] === []
				|| $meta[self::FILTER_TABLE] === null
			)
				? null
				: array_flip($meta[self::FILTER_TABLE]);
			if (array_key_exists(self::VALUE, $meta)) {
				if (isset($meta['filterTable2'][$meta[self::VALUE]])) {
					$meta['defOut'] = $meta['filterTable2'][$meta[self::VALUE]];

				} elseif (isset($meta[self::FILTER_OUT])) {
					$meta['defOut'] = call_user_func($meta[self::FILTER_OUT], $meta[self::VALUE]);

				} else {
					$meta['defOut'] = $meta[self::VALUE];
				}
			}

			$meta[self::PATTERN] = sprintf('#(?:%s)\\z#A', $pattern);

			// include in expression
			$aliases['p' . $i] = $name;
			$re = '(?P<p' . $i . '>(?U)' . $pattern . ')' . $re;
			if ($brackets) { // is in brackets?
				if (!isset($meta[self::VALUE])) {
					$meta[self::VALUE] = $meta['defOut'] = null;
				}

				$meta['fixity'] = self::PATH_OPTIONAL;

			} elseif (!$autoOptional) {
				unset($meta['fixity']);

			} elseif (isset($meta['fixity'])) { // auto-optional
				$re = '(?:' . $re . ')?';
				$meta['fixity'] = self::PATH_OPTIONAL;

			} else {
				$autoOptional = false;
			}

			$metadata[$name] = $meta;

		} while (true);

		if ($brackets) {
			throw new Exceptions\InvalidArgument(sprintf('Missing "[" in mask "%s".', $mask));
		}

		$this->aliases = $aliases;
		$this->re = '#' . $re . '/?\z#A';
		$this->metadata = $metadata;
		$this->sequence = $sequence;
	}

	/**
	 * Returns mask
	 */
	public function getMask(): string
	{
		return $this->mask;
	}

	/**
	 * Returns allowed origins
	 */
	public function getAllowedOrigins(): array
	{
		return $this->allowedOrigins;
	}

	/**
	 * Returns http host name
	 */
	public function getHttpHost(): string
	{
		return $this->httpHost;
	}

	/**
	 * Rename keys in array
	 */
	private static function renameKeys(array $arr, array $xlat): array
	{
		if ($xlat === []) {
			return $arr;
		}

		$res = [];
		$occupied = array_flip($xlat);

		foreach ($arr as $k => $v) {
			if (isset($xlat[$k])) {
				$res[$xlat[$k]] = $v;

			} elseif (!isset($occupied[$k])) {
				$res[$k] = $v;
			}
		}

		return $res;
	}

	/**
	 * Proprietary cache aim
	 *
	 * @return array<string>|null
	 *
	 * @internal
	 */
	public function getTargetControllers(): array|null
	{
		$m = $this->metadata;
		$module = '';

		if (isset($m[self::MODULE_KEY])) {
			if (isset($m[self::MODULE_KEY]['fixity']) && $m[self::MODULE_KEY]['fixity'] === self::CONSTANT) {
				$module = $m[self::MODULE_KEY][self::VALUE] . ':';

			} else {
				return null;
			}
		}

		if (isset($m[self::CONTROLLER_KEY]['fixity']) && $m[self::CONTROLLER_KEY]['fixity'] === self::CONSTANT) {
			return [$module . $m[self::CONTROLLER_KEY][self::VALUE]];
		}

		return null;
	}

	// ********************* Inflectors ******************

	/**
	 * camelCaseAction name -> dash-separated
	 */
	protected static function camel2dash(string $s): string
	{
		$s = preg_replace('#(.)(?=[A-Z])#', '$1-', $s);
		$s = strtolower($s);
		$s = rawurlencode($s);

		return $s;
	}

	/**
	 * dash-separated -> camelCaseAction name
	 */
	protected static function dash2Camel(string $s): string
	{
		$s = preg_replace('#-(?=[a-z])#', ' ', $s);
		$s = lcfirst(ucwords($s));
		$s = str_replace(' ', '', $s);

		return $s;
	}

	/**
	 * PascalCase:Controller name -> dash-and-dot-separated
	 */
	protected static function controller2path(string $s): string
	{
		$s = strtr($s, ':', '.');
		$s = preg_replace('#([^.])(?=[A-Z])#', '$1-', $s);
		$s = strtolower($s);
		$s = rawurlencode($s);

		return $s;
	}

	/**
	 * dash-and-dot-separated -> PascalCase:Controller name.
	 */
	protected static function path2controller(string $s): string
	{
		$s = preg_replace('#([.-])(?=[a-z])#', '$1 ', $s);
		$s = ucwords($s);
		$s = str_replace('. ', ':', $s);
		$s = str_replace('- ', '', $s);

		return $s;
	}

	/**
	 * Url encode
	 */
	protected static function param2path(string $s): string
	{
		return str_replace('%2F', '/', rawurlencode($s));
	}

	/**
	 * @return array<string>
	 */
	private function splitName(string $name): array
	{
		$pos = strrpos($name, ':');

		return $pos === false
			? ['', $name, '']
			: [substr($name, 0, $pos), substr($name, $pos + 1), ':'];
	}

}
