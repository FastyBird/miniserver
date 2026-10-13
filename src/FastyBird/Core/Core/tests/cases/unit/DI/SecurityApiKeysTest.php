<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use Doctrine\ORM;
use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Security\Commands;
use FastyBird\Core\Security\Entities;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Models;
use FastyBird\Core\Security\Services;
use FastyBird\Core\Tests;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use Symfony\Component\Console;
use Throwable;
use function hash;

/**
 * fbCore > security > enable > apiKeys: off by default, when nothing of API keys is registered and
 * Services\Auth ignores the X-Api-Key header; on, when the reader, the models and the four
 * fb:api-key:* commands are registered and Auth reads the header.
 */
final class SecurityApiKeysTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const string SECRET = 'b3a1f0c2d4e6f8a0b2c4d6e8f0a2b4c6d8e0f2a4b6c8d0e2f4a6b8c0d2e4f6a8';

	private const array SERVICES = [
		'fbCore.security.apiKey.reader' => Identity\ApiKeyReader::class,
		'fbCore.security.doctrine.apiKeysRepository' => Models\ApiKeys\Repository::class,
		'fbCore.security.doctrine.apiKeysManager' => Models\ApiKeys\Manager::class,
		'fbCore.security.commands.apiKeys.create' => Commands\CreateApiKey::class,
		'fbCore.security.commands.apiKeys.list' => Commands\ListApiKeys::class,
		'fbCore.security.commands.apiKeys.suspend' => Commands\SuspendApiKey::class,
		'fbCore.security.commands.apiKeys.delete' => Commands\DeleteApiKey::class,
	];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Error
	 * @throws Throwable
	 */
	public function testOffByDefault(): void
	{
		$container = $this->createContainer();

		foreach (self::SERVICES as $name => $type) {
			self::assertFalse($container->hasService($name), $name);
			self::assertSame([], $container->findByType($type), $type);
		}

		$user = $container->getByType(Identity\User::class);

		$container->getByType(Services\Auth::class)->login(
			new ServerRequest('GET', '/api/v1/devices', ['X-Api-Key' => self::SECRET]),
		);

		self::assertFalse($user->isLoggedIn());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Error
	 * @throws Throwable
	 */
	public function testOnRegistersTheKeyServicesAndFailsClosedWithoutAccounts(): void
	{
		$container = $this->createContainer(__DIR__ . '/securityApiKeysEnabled.neon');

		foreach (self::SERVICES as $name => $type) {
			self::assertSame([$name], $container->findByType($type), $type);
		}

		// Fail closed: nothing here resolves accounts (that is the Accounts module's job), so a key
		// can neither be created nor sign anyone in
		$entityManager = $container->getByType(ORM\EntityManagerInterface::class);

		(new ORM\Tools\SchemaTool($entityManager))->createSchema(
			$entityManager->getMetadataFactory()->getAllMetadata(),
		);

		self::assertSame([], $container->findByType(Identity\AccountIdentityProvider::class));

		$create = $container->getByType(Commands\CreateApiKey::class);

		self::assertSame(
			Console\Command\Command::FAILURE,
			(new Console\Tester\CommandTester($create))->execute(
				['--name' => 'Integration', '--account' => self::ACCOUNT],
			),
		);

		$entityManager->persist(new Entities\ApiKeys\ApiKey(
			Uuid\Uuid::fromString(self::ACCOUNT),
			'Integration',
			hash('sha256', self::SECRET),
		));
		$entityManager->flush();

		// The key itself is valid...
		self::assertNotNull($container->getByType(Identity\ApiKeyReader::class)->read($this->request()));

		// ...and Auth refuses it
		$this->expectException(SecurityExceptions\UnauthorizedAccess::class);

		$container->getByType(Services\Auth::class)->login($this->request());
	}

	/**
	 * @throws Throwable
	 */
	private function request(): ServerRequest
	{
		return new ServerRequest('GET', '/api/v1/devices', ['X-Api-Key' => self::SECRET]);
	}

}
