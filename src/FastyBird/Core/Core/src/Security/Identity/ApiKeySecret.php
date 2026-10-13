<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use Random\RandomException;
use SensitiveParameter;
use function bin2hex;
use function hash;
use function random_bytes;

/**
 * The secret of an API key: 256 random bits, hex encoded. Only its SHA-256 hash is stored, so a
 * key is found by hashing what the client sends. A fast hash is enough: the secret is a uniformly
 * random 256-bit value, not a password a person chose, so there is nothing to brute-force.
 */
final class ApiKeySecret
{

	public const int BYTES = 32;

	/**
	 * @return non-empty-string
	 *
	 * @throws RandomException
	 */
	public static function generate(): string
	{
		return bin2hex(random_bytes(self::BYTES));
	}

	/**
	 * @return non-empty-string lowercase hex, 64 characters
	 */
	public static function hash(#[SensitiveParameter] string $secret): string
	{
		return hash('sha256', $secret);
	}

}
