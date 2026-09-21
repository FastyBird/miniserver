<?php declare(strict_types = 1);

/**
 * Validator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Middleware;

use FastyBird\Core\Exceptions\JsonApi as JsonApiExceptions;
use FastyBird\Plugin\ApiKey\Models;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
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
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Validator implements MiddlewareInterface
{

	private const API_KEY_HEADER = 'X-Api-Key';

	public function __construct(
		private readonly Models\Entities\KeyRepository $keyRepository,
		private readonly Localization\Translator $translator,
	)
	{
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
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
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNAUTHORIZED,
						strval($this->translator->translate('//apikey-plugin.base.messages.unauthorized.heading')),
						strval($this->translator->translate('//apikey-plugin.base.messages.unauthorized.message')),
					);
				}

				return $handler->handle($request);
			}
		}

		throw new JsonApiExceptions\JsonApiError(
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
