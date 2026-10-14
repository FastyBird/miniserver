<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Logging;

use FastyBird\Core\Tests;
use React\Http\Message\ServerRequest;
use ReflectionMethod;
use Sentry;
use Throwable;

/**
 * The Sentry client Core configures sends the API key header, as the bearer token, as "[Filtered]",
 * and no request body
 */
final class SentryHeadersTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Throwable
	 */
	public function testClientFiltersTheCredentialHeaders(): void
	{
		$container = $this->createContainer(__DIR__ . '/sentry.neon');

		// The builder, not the client: building the client would install Sentry's global error
		// handlers, which nothing can remove again
		$integrations = $container->getByType(Sentry\ClientBuilderInterface::class)->getOptions()->getIntegrations();
		self::assertIsArray($integrations);

		$integration = null;

		foreach ($integrations as $candidate) {
			if ($candidate instanceof Sentry\Integration\RequestIntegration) {
				$integration = $candidate;
			}
		}

		self::assertInstanceOf(Sentry\Integration\RequestIntegration::class, $integration);

		$headers = (new ReflectionMethod($integration, 'sanitizeHeaders'))->invoke($integration, [
			'X-Api-Key' => ['b3a1f0c2d4e6f8a0'],
			'x-api-key' => ['b3a1f0c2d4e6f8a0'],
			'Authorization' => ['Bearer eyJ'],
			'Accept' => ['application/json'],
		]);

		self::assertSame(
			[
				'X-Api-Key' => ['[Filtered]'],
				'x-api-key' => ['[Filtered]'],
				'Authorization' => ['[Filtered]'],
				'Accept' => ['application/json'],
			],
			$headers,
		);
	}

	/**
	 * A request body can carry a password or a token, so an event carries no body at all: a JSON
	 * body would be decoded into it, and any other sent as it is
	 *
	 * @throws Throwable
	 */
	public function testClientSendsNoRequestBody(): void
	{
		$container = $this->createContainer(__DIR__ . '/sentry.neon');

		$options = $container->getByType(Sentry\ClientBuilderInterface::class)->getOptions();

		// The name Sentry keeps for it; it deprecated the other one, 'none', in 3.10
		self::assertSame('never', $options->getMaxRequestBodySize());

		$integration = new Sentry\Integration\RequestIntegration();

		foreach (['application/json', 'application/vnd.api+json'] as $contentType) {
			$body = (new ReflectionMethod($integration, 'captureRequestBody'))->invoke(
				$integration,
				$options,
				new ServerRequest(
					'POST',
					'/api/accounts-module/v1/session',
					['Content-Type' => $contentType, 'Content-Length' => '64'],
					'{"data":{"attributes":{"uid":"john","password":"PROBEPASSWORD"}}}',
				),
			);

			self::assertTrue($body === null || $body === '', $contentType);
		}
	}

}
