<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Entities;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Models;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ServerRequestInterface;
use function hash_equals;
use function reset;

/**
 * Reads the API key a machine client sends in the X-Api-Key header. Registered only when
 * fbCore > security > enable > apiKeys is on; Services\Auth consults it only for a request that
 * carries no bearer token.
 */
final readonly class ApiKeyReader
{

	public const string HEADER_NAME = 'x-api-key';

	public function __construct(
		private readonly Models\ApiKeys\Repository $repository,
		private readonly ClockInterface $clock,
	)
	{
	}

	/**
	 * A request without the header, or with an empty one, yields null. A key that is unknown,
	 * suspended, deleted or expired throws, exactly as an invalid bearer token does.
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws SecurityExceptions\UnauthorizedAccess
	 */
	public function read(ServerRequestInterface $request): Entities\ApiKeys\ApiKey|null
	{
		$values = $request->getHeader(self::HEADER_NAME);

		$secret = reset($values);

		if ($secret === false || $secret === '') {
			return null;
		}

		$hash = ApiKeySecret::hash($secret);

		$apiKey = $this->repository->findOneByHash($hash);

		if (
			$apiKey === null
			// The lookup matched on the hash already; this compares it again in constant time, so
			// the decision does not rest on how the database collates or compares the column
			|| !hash_equals($apiKey->getHash(), $hash)
			|| !$apiKey->isActive()
			|| $apiKey->isExpired($this->clock->now())
		) {
			throw new SecurityExceptions\UnauthorizedAccess('API key is not valid');
		}

		return $apiKey;
	}

}
