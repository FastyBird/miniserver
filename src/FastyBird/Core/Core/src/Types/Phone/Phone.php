<?php declare(strict_types = 1);

namespace FastyBird\Core\Types\Phone;

use Doctrine\DBAL\Platforms;
use Doctrine\DBAL\Types;
use FastyBird\Core\Entities\Phone as Entities;
use FastyBird\Core\Exceptions;
use Override;

/**
 * Doctrine phone data type
 */
final class Phone extends Types\StringType
{

	// Data type name
	public const string PHONE = 'phone';

	public function getName(): string
	{
		return self::PHONE;
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidPhone
	 */
	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps

	#[Override]
	public function convertToPHPValue(mixed $value, Platforms\AbstractPlatform $platform): Entities\Phone|null
	{
		return $value === null ? null : Entities\Phone::fromNumber($value);
	}

	#[Override]
	public function convertToDatabaseValue(mixed $value, Platforms\AbstractPlatform $platform): mixed
	{
		if ($value instanceof Entities\Phone) {
			return $value->getRawOutput();
		}

		return $value;
	}

	public function requiresSQLCommentHint(Platforms\AbstractPlatform $platform): bool
	{
		return true;
	}

}
