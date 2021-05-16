<?php declare(strict_types = 1);

/**
 * ApiKeyValidatorMiddleware.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           16.05.20
 */

namespace FastyBird\MiniServer\Middleware;

use Contributte\Translation;
use FastyBird\JsonApi\Exceptions  as JsonApiExceptions;
use FastyBird\MiniServer\Models;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * API key check middleware
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ApiKeyValidatorMiddleware implements MiddlewareInterface
{

	private const API_KEY_HEADER = 'X-Api-Key';

	/** @var Models\ApiKeys\IKeyRepository */
	private Models\ApiKeys\IKeyRepository $keyRepository;

	/** @var Translation\Translator */
	private Translation\Translator $translator;

	public function __construct(
		Models\ApiKeys\IKeyRepository $keyRepository,
		Translation\Translator $translator
	) {
		$this->keyRepository = $keyRepository;
		$this->translator = $translator;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 *
	 * @return ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 * @throws Translation\Exceptions\InvalidArgument
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// Request has to have Authorization header
		if ($request->hasHeader(self::API_KEY_HEADER)) {
			$headers = $this->readHeaders($request);

			/** @var string|null $headerApiKey */
			$headerApiKey = $headers[strtolower(self::API_KEY_HEADER)] ?? null;

			if ($headerApiKey !== null) {
				$apiKey = $this->keyRepository->findOneByKey($headerApiKey);

				if ($apiKey === null) {
					throw new JsonApiExceptions\JsonApiErrorException(
						StatusCodeInterface::STATUS_UNAUTHORIZED,
						$this->translator->translate('//node.base.messages.notAuthorized.heading'),
						$this->translator->translate('//node.base.messages.notAuthorized.message')
					);
				}

				return $handler->handle($request);
			}
		}

		throw new JsonApiExceptions\JsonApiErrorException(
			StatusCodeInterface::STATUS_UNAUTHORIZED,
			$this->translator->translate('//node.base.messages.notAuthorized.heading'),
			$this->translator->translate('//node.base.messages.notAuthorized.message')
		);
	}

	/**
	 * @param ServerRequestInterface $request
	 *
	 * @return mixed[]
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
