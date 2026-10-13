<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use DateTimeImmutable;
use DateTimeZone;
use FastyBird\Core\Security\Entities;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Types;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use Throwable;
use function bin2hex;
use function date_default_timezone_get;
use function hash;
use function hex2bin;
use function strlen;

/**
 * The parts of API keys that need no database: the secret, its hash, and how a key decides that
 * it is active and not expired
 */
final class ApiKeySecretTest extends TestCase
{

	private const string ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	/**
	 * @throws Throwable
	 */
	public function testSecretIsHexOf256RandomBits(): void
	{
		$secret = Identity\ApiKeySecret::generate();

		self::assertSame(64, strlen($secret));
		self::assertSame(Identity\ApiKeySecret::BYTES, strlen((string) hex2bin($secret)));
		self::assertSame($secret, bin2hex((string) hex2bin($secret)));
		self::assertNotSame($secret, Identity\ApiKeySecret::generate());
	}

	/**
	 * @throws Throwable
	 */
	public function testHashIsSha256OfTheSecret(): void
	{
		$secret = Identity\ApiKeySecret::generate();

		self::assertSame(hash('sha256', $secret), Identity\ApiKeySecret::hash($secret));
		self::assertNotSame($secret, Identity\ApiKeySecret::hash($secret));
	}

	/**
	 * @throws Throwable
	 */
	public function testOnlyAnActiveKeyIsActive(): void
	{
		$apiKey = $this->apiKey();

		self::assertTrue($apiKey->isActive());

		$apiKey->setState(Types\ApiKeyState::SUSPENDED);
		self::assertFalse($apiKey->isActive());

		$apiKey->setState(Types\ApiKeyState::DELETED);
		self::assertFalse($apiKey->isActive());
	}

	/**
	 * @throws Throwable
	 */
	public function testKeyExpiresAtItsExpiryInstant(): void
	{
		$now = new DateTimeImmutable('2026-10-13T12:00:00+00:00');

		self::assertFalse($this->apiKey()->isExpired($now));
		self::assertFalse($this->apiKey(new DateTimeImmutable('2026-10-13T12:00:01+00:00'))->isExpired($now));
		self::assertTrue($this->apiKey(new DateTimeImmutable('2026-10-13T12:00:00+00:00'))->isExpired($now));
		self::assertTrue($this->apiKey(new DateTimeImmutable('2026-10-13T11:59:59+00:00'))->isExpired($now));

		// The same instant, written in another offset
		self::assertTrue($this->apiKey(new DateTimeImmutable('2026-10-13T14:00:00+02:00'))->isExpired($now));
		self::assertFalse($this->apiKey(new DateTimeImmutable('2026-10-13T14:00:01+02:00'))->isExpired($now));
	}

	/**
	 * A DATETIME column drops the offset and is read back in PHP's default time zone, so the key
	 * keeps its expiry in that zone, as the same instant
	 *
	 * @throws Throwable
	 */
	public function testExpiryIsKeptInTheDefaultTimeZone(): void
	{
		$expiresAt = new DateTimeImmutable('2027-01-01T00:00:00+05:00');

		foreach ([$this->apiKey($expiresAt), $this->apiKeyWithExpirySet($expiresAt)] as $apiKey) {
			$stored = $apiKey->getExpiresAt();

			self::assertNotNull($stored);
			self::assertSame($expiresAt->getTimestamp(), $stored->getTimestamp());
			self::assertSame(
				(new DateTimeZone(date_default_timezone_get()))->getName(),
				$stored->getTimezone()->getName(),
			);
		}
	}

	/**
	 * @throws Throwable
	 */
	private function apiKey(DateTimeImmutable|null $expiresAt = null): Entities\ApiKeys\ApiKey
	{
		return new Entities\ApiKeys\ApiKey(
			Uuid\Uuid::fromString(self::ACCOUNT),
			'Integration',
			hash('sha256', 'secret'),
			$expiresAt,
		);
	}

	/**
	 * @throws Throwable
	 */
	private function apiKeyWithExpirySet(DateTimeImmutable $expiresAt): Entities\ApiKeys\ApiKey
	{
		$apiKey = $this->apiKey();
		$apiKey->setExpiresAt($expiresAt);

		return $apiKey;
	}

}
