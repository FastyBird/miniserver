<?php declare(strict_types = 1);

namespace FastyBird\Plugin\ApiKey\Middleware;

use FastyBird\Core\Api\Exceptions;
use FastyBird\Plugin\ApiKey\Models;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function array_change_key_case;
use function assert;
use function is_string;
use function reset;
use function strtolower;
use function strtr;
use function strval;
use const CASE_LOWER;

/**
 * API key check middleware
 */
final readonly class Validator implements MiddlewareInterface
{

	private const string API_KEY_HEADER = 'X-Api-Key';

	public function __construct(
		private Models\Entities\KeyRepository $keyRepository,
		private Localization\Translator $translator,
	)
	{
	}

	/**
	 * @throws Exceptions\JsonApiError
	 */
	#[Override]
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// Request has to have Authorization header
		if ($request->hasHeader(self::API_KEY_HEADER)) {
			$headers = $this->readHeaders($request);

			$headerApiKey = $headers[strtolower(self::API_KEY_HEADER)] ?? null;
			assert(is_string($headerApiKey) || $headerApiKey === null);

			if ($headerApiKey !== null) {
				$apiKey = $this->keyRepository->findOneByKey($headerApiKey);

				if ($apiKey === null) {
					throw new Exceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNAUTHORIZED,
						strval($this->translator->translate('//apikey-plugin.base.messages.unauthorized.heading')),
						strval($this->translator->translate('//apikey-plugin.base.messages.unauthorized.message')),
					);
				}

				return $handler->handle($request);
			}
		}

		throw new Exceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNAUTHORIZED,
			strval($this->translator->translate('//apikey-plugin.base.messages.unauthorized.heading')),
			strval($this->translator->translate('//apikey-plugin.base.messages.unauthorized.message')),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function readHeaders(ServerRequestInterface $request): array
	{
		$headers = [];

		foreach ($request->getHeaders() as $k => $v) {
			$headers[strtr($k, '_', '-')] = reset($v);
		}

		return array_change_key_case($headers, CASE_LOWER);
	}

}
