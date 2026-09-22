<?php declare(strict_types = 1);

namespace FastyBird\Core\Types\DoctrineTimestampable;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms;
use Doctrine\DBAL\Types;
use ValueError;
use function sprintf;
use function strlen;
use function strval;
use function substr;

/**
 * Doctrine DBAL type that persists DateTime values normalized to UTC
 */
class UTCDateTime extends Types\DateTimeType
{

	// Define datatype name
	public const string UTC_DATETIME = 'utcdatetime';

	private static DateTimeZone|null $utc = null;

	/**
	 * @throws Types\ConversionException
	 * @throws ValueError
	 */
	// DBAL 4's DateTimeType::convertToPHPValue() declares ': ?DateTime'. DateTimeInterface|null
	// is WIDER than that, which is a declaration-time fatal, and because Type::addType()
	// instantiates the class it would fire the first time any Connection is built rather than
	// at container compile. DateTime|null matches DBAL 4 exactly and is a legal narrowing on
	// DBAL 3, whose parent declares no return type at all. It is also what this method really
	// returns -- DateTime::createFromFormat().
	// phpcs:ignore Generic.NamingConventions.CamelCapsFunctionName.ScopeNotCamelCaps
	public function convertToPHPValue(mixed $value, Platforms\AbstractPlatform $platform): DateTime|null
	{
		if ($value === null) {
			return null;
		}

		if (self::$utc === null) {
			self::$utc = new DateTimeZone('UTC');
		}

		$val = DateTime::createFromFormat($platform->getDateTimeFormatString(), $value, self::$utc);

		if ($val === false) {
			// ConversionException::conversionFailed() is deleted in DBAL 4, but the class stays
			// constructible under both majors -- DBAL 3 has it extend Doctrine\DBAL\Exception,
			// itself a plain \Exception subclass; DBAL 4 has it extend \Exception and implement
			// Doctrine\DBAL\Exception, which became an interface. Neither declares a constructor,
			// so \Exception's is inherited either way. This reproduces the factory's message.
			// The type name comes from the constant rather than $this->getName(), since DBAL 4
			// removes getName() from the parent.
			$excerpt = strval($value);
			$excerpt = strlen($excerpt) > 32 ? substr($excerpt, 0, 20) . '...' : $excerpt;

			throw new Types\ConversionException(
				sprintf(
					'Could not convert database value "%s" to Doctrine Type %s',
					$excerpt,
					self::UTC_DATETIME,
				),
			);
		}

		return $val;
	}

	public function getName(): string
	{
		return self::UTC_DATETIME;
	}

	public function convertToDatabaseValue(mixed $value, Platforms\AbstractPlatform $platform): string|null
	{
		if ($value === null) {
			return null;
		}

		if (self::$utc === null) {
			self::$utc = new DateTimeZone('UTC');
		}

		$value->setTimeZone(self::$utc);

		return $value->format($platform->getDateTimeFormatString());
	}

}
