<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Security;

use DateTimeImmutable;
use Doctrine\DBAL;
use Doctrine\ORM;
use Error;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Security;
use FastyBird\Module\Accounts\Tests\Cases\Unit\DbTestCase;
use Lcobucci\JWT;
use Nette\DI;
use Ramsey\Uuid;
use RuntimeException;
use function file_get_contents;

/**
 * The accounts side of the lcobucci/jwt 4.3 fixture tokens (census T11.3, #634; #643 relies on
 * it): valid.jwt, persisted as an access token the way a sign-in persists one, resolves through
 * this module's IdentityFactory to its account's identity, and that account holds the roles the
 * token claims. The lookup is by the token string, so this is where a library that wrote the
 * string back differently would lock every existing session out.
 */
final class FixtureTokenIdentityTest extends DbTestCase
{

	private const string FIXTURE = __DIR__ . '/../../../../../../Core/Core/tests/fixtures/tokens/valid.jwt';

	// the identity john.doe@fastybird.com of account 5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34 (sql/dummy.data.sql)
	private const string IDENTITY = '77331268-efbf-bd34-49ef-bfbdefbfbd04';

	/**
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws ORM\Exception\ORMException
	 * @throws PersistenceExceptions\Query
	 * @throws RuntimeException
	 * @throws SecurityExceptions\UnauthorizedAccess
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testThePersistedFixtureTokenResolvesToItsIdentity(): void
	{
		$minted = file_get_contents(self::FIXTURE);

		if ($minted === false) {
			throw new RuntimeException('The valid.jwt fixture could not be read');
		}

		$identity = $this->getEntityManager()->find(
			Entities\Identities\Identity::class,
			Uuid\Uuid::fromString(self::IDENTITY),
		);
		self::assertInstanceOf(Entities\Identities\Identity::class, $identity);

		$this->getEntityManager()->persist(
			new Entities\Tokens\AccessToken($identity, $minted, new DateTimeImmutable('2120-03-01T10:20:30+00:00')),
		);
		$this->getEntityManager()->flush();
		$this->getEntityManager()->clear();

		$token = $this->getContainer()->getByType(Identity\TokenValidator::class)->validate($minted);
		self::assertInstanceOf(JWT\UnencryptedToken::class, $token);

		$resolved = $this->getContainer()->getByType(Security\IdentityFactory::class)->create($token);

		self::assertInstanceOf(Entities\Identities\Identity::class, $resolved);
		self::assertSame(self::IDENTITY, $resolved->getId()->toString());
		self::assertSame(
			$token->claims()->get(Constants::TOKEN_CLAIM_USER),
			$resolved->getAccount()->getId()->toString(),
		);
		self::assertSame(
			$token->claims()->get(Constants::TOKEN_CLAIM_ROLES),
			$this->getContainer()->getByType(Identity\EnforcerFactory::class)
				->getEnforcer()
				->getRolesForUser($resolved->getAccount()->getId()->toString()),
		);
	}

}
