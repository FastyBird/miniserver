<?php declare(strict_types = 1);

namespace FastyBird\Core\Logging;

use React\Http\Io\BufferedBody;
use Sentry\Integration;
use Throwable;
use Tracy;
use function array_merge;
use function array_unique;
use function array_values;
use function clearstatcache;
use function fclose;
use function filesize;
use function fopen;
use function in_array;
use function is_file;
use function is_string;
use function preg_match;
use function substr;
use function unlink;

/**
 * Keeps credentials out of what Core writes about an error: Tracy's blue screens and Sentry events
 *
 * A request's credentials are the bearer token (Authorization), the API key (X-Api-Key) and the
 * token cookies a browser sends with every request: `token` (Security\Identity\TokenReader::COOKIE_NAME)
 * and `refresh_token`, which the Accounts module's front end keeps beside it. Tracy hides only
 * `authorization` of them, and only from what it dumps; Sentry filters Authorization and Cookie but
 * not X-Api-Key. Credentials passed as arguments are marked #[\SensitiveParameter] where they are
 * read (Security\Identity) and looked up (Security\Models, Security\Queries), which PHP turns into a
 * \SensitiveParameterValue in a stack trace and Tracy prints as hidden. So is a password, along a
 * sign-in (Security\Identity\User::login(), Security\Identity\Authenticator::authenticate()), which a
 * blue screen would otherwise show under a parameter name it does not hide, and a Sentry event
 * under any name.
 *
 * A request's body carries credentials as well: a sign-in's password, a session refresh's refresh
 * token, and the response to either carries both tokens. The HTTP server buffers each body whole
 * (React\Http\Io\BufferedBody), and a controller decodes it from a string, so both reach the frames
 * a blue screen dumps; Sentry would attach a request's body to its event. Neither shows one.
 */
final class SensitiveData
{

	/**
	 * Keys a blue screen hides wherever it dumps them, compared case-insensitively: request
	 * headers, $_SERVER, $_COOKIE, and array keys and properties inside dumped objects, such as a
	 * PSR-7 request's headers and cookies or a token entity's token. The whole Cookie header is
	 * hidden, because it carries the token cookies verbatim, and so is the whole of a buffered
	 * request or response body
	 */
	public const array BLUE_SCREEN_KEYS = [
		'authorization',
		'x-api-key',
		'http_authorization',
		'http_x_api_key',
		'redirect_http_authorization',
		'token',
		'refresh_token',
		'cookie',
		'http_cookie',
		BufferedBody::class . '::$buffer',
	];

	/**
	 * The shape of an API key's secret and of its SHA-256 hash (Security\Identity\ApiKeySecret)
	 */
	public const string API_KEY_MATERIAL = '/^[0-9a-f]{64}$/';

	/**
	 * The shape of a signed token (Security\Identity\TokenBuilder): its header and its claims are
	 * both a base64url-encoded JSON object, which always starts with `eyJ`
	 */
	public const string TOKEN_MATERIAL = '/^eyJ[A-Za-z0-9_-]*\.eyJ[A-Za-z0-9_-]*\.[A-Za-z0-9_-]*$/';

	/**
	 * A JSON text with a member that holds a credential, the shape of a body that carries one: a
	 * sign-in's `password`, a session's `token` and `refresh` (Accounts' session resource), and
	 * the `refresh_token` cookie's name
	 */
	public const string BODY_MATERIAL = '/"(?:password|token|refresh|refresh_token)"\s*:/i';

	/**
	 * How much of a request's body a Sentry event carries: none
	 */
	public const string SENTRY_REQUEST_BODY = 'none';

	/**
	 * Request headers a Sentry event carries as "[Filtered]": Sentry's own defaults
	 * (RequestIntegration::DEFAULT_SENSITIVE_HEADERS), and the API key
	 */
	public const array SENTRY_HEADERS = [
		'Authorization',
		'Cookie',
		'Set-Cookie',
		'X-Forwarded-For',
		'X-Real-IP',
		'X-Api-Key',
	];

	/**
	 * Configures a blue screen to hide the request's credentials
	 */
	public static function hideInBlueScreen(Tracy\BlueScreen $blueScreen): Tracy\BlueScreen
	{
		if (in_array(self::BLUE_SCREEN_KEYS[1], $blueScreen->keysToHide, true)) {
			return $blueScreen;
		}

		$blueScreen->keysToHide = array_values(array_unique(array_merge(
			$blueScreen->keysToHide,
			self::BLUE_SCREEN_KEYS,
		)));

		// An API key's secret and its hash are both 64 lowercase hex characters. The hash is a
		// query parameter of the key's lookup, so it reaches DBAL's and PDO's stack frames, whose
		// arguments no key name marks; any value of that shape is hidden instead. A signed token is
		// a query parameter of its own lookup in the same way. A body is decoded from a string,
		// which is an argument of the decoder's frame
		$scrubber = $blueScreen->scrubber;

		$blueScreen->scrubber = static fn (string $key, mixed $value, string|null $class): bool => (
			is_string($value)
			&& (
				preg_match(self::API_KEY_MATERIAL, $value) === 1
				|| preg_match(self::TOKEN_MATERIAL, $value) === 1
				|| preg_match(self::BODY_MATERIAL, $value) === 1
			)
		) || ($scrubber !== null && $scrubber($key, $value, $class) === true);

		return $blueScreen;
	}

	/**
	 * Writes the HTML blue screen only. Tracy's renderToFile() also writes a markdown report next
	 * to it, which lists the request headers verbatim, without the hiding a dump applies, so the
	 * report's file is taken first, which makes Tracy skip it, and removed again.
	 */
	public static function renderBlueScreen(Tracy\BlueScreen $blueScreen, Throwable $ex, string $file): bool
	{
		$report = substr($file, 0, -5) . '.md';

		$handle = is_file($report) ? false : @fopen($report, 'x'); // intentionally @

		try {
			return self::hideInBlueScreen($blueScreen)->renderToFile($ex, $file);
		} finally {
			if ($handle !== false) {
				fclose($handle);

				clearstatcache(true, $report);

				if (filesize($report) === 0) {
					@unlink($report); // intentionally @
				}
			}
		}
	}

	/**
	 * The Sentry client's integrations: the defaults, with the request integration filtering the
	 * API key as well
	 *
	 * @return list<Integration\IntegrationInterface>
	 */
	public static function sentryIntegrations(): array
	{
		return [
			new Integration\RequestIntegration(null, ['pii_sanitize_headers' => self::SENTRY_HEADERS]),
		];
	}

}
