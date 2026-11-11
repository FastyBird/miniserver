<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use CuyZ\Valinor;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use FastyBird\Core\Documents\Exceptions;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use Throwable;
use ValueError;
use function implode;
use function is_array;
use function is_string;
use function is_subclass_of;
use function mb_check_encoding;
use function sprintf;
use function str_contains;

/**
 * The one place a plain array becomes an object of a mapped class
 *
 * Every consumer maps through map() and catches Exceptions\MappingFailed; none of them imports a mapping library or
 * prints its errors. Two libraries stand behind it while the classes move from one to the other (#724): a class which
 * implements Orisai's MappedObject is still mapped by orisai, any other class by Valinor. A class leaves the first
 * branch by dropping that interface, together with its orisai rules and the attributes and types Valinor reads
 */
final readonly class Mapper
{

	private const string UNPRINTABLE = '*root*: A key or a value is not valid UTF-8.';

	private Valinor\Mapper\TreeMapper $strict;

	private Valinor\Mapper\TreeMapper $lenient;

	public function __construct(private ObjectMapper\Processing\Processor $processor)
	{
		$builder = new Valinor\MapperBuilder();

		// One builder for both branches of strictness, with the settings every class shares and nothing else. No
		// converter is registered on it: a converter registered on the builder runs on every node and makes Valinor
		// stop choosing between the object types of a union by what the input holds. Converters stay attributes on
		// the property they convert (see MapBlankToNull). No file cache either: see docs/conventions.md
		$builder = $builder
			->supportDateFormats(DateTimeInterface::ATOM)
			->registerConstructor(
				// The date constructors of Valinor itself, as they were, but a string that does not parse is an error
				// message for every string, a NUL byte included: createFromFormat() throws a ValueError for it, which no
				// union can recover from, and a union of a date and a string (the value of a state) has to take the
				// string, as orisai did, which tried the string before the date. The mutable DateTime has one too
				/** @param non-empty-string|int|float $value */
				static fn (string|int|float $value): DateTimeImmutable => self::date(DateTimeImmutable::class, $value),
				/** @param non-empty-string|int|float $value */
				static fn (string|int|float $value): DateTime => self::date(DateTime::class, $value),
				static function (string $value): Uuid\UuidInterface {
					// What orisai's UuidRule takes, which is what isValid() says: fromString() alone also reads a UUID
					// written without dashes
					if (!Uuid\Uuid::isValid($value)) {
						throw Valinor\Mapper\Tree\Message\MessageBuilder::newError(
							'Value {source_value} is not a valid UUID.',
						)->build();
					}

					return Uuid\Uuid::fromString($value);
				},
			);

		$this->strict = $builder->mapper();
		$this->lenient = $builder->allowSuperfluousKeys()->mapper();
	}

	/**
	 * @template T of object
	 *
	 * @param array<mixed> $data
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws Exceptions\MappingFailed
	 */
	public function map(array $data, string $class, MappingOptions $options = new MappingOptions()): object
	{
		if (is_subclass_of($class, ObjectMapper\MappedObject::class)) {
			return $this->mapWithOrisai($data, $class, $options);
		}

		try {
			return ($options->allowUnknownFields ? $this->lenient : $this->strict)->map($class, $data);
		} catch (Valinor\Mapper\MappingError $ex) {
			throw new Exceptions\MappingFailed(self::print($ex, $data), 0, $ex);

			// @phpstan-ignore catch.neverThrown (TreeMapper::map() declares only MappingError, but formatting a message of its own with a key or a value that is not valid UTF-8 throws this)
		} catch (Valinor\Utility\String\StringFormatterError $ex) {
			throw self::unprintable($data, $ex);
		}
	}

	/**
	 * @template T of ObjectMapper\MappedObject
	 *
	 * @param array<mixed> $data
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws Exceptions\MappingFailed
	 */
	private function mapWithOrisai(array $data, string $class, MappingOptions $options): object
	{
		$processingOptions = new ObjectMapper\Processing\Options();
		$processingOptions->setAllowUnknownFields($options->allowUnknownFields);

		try {
			return $this->processor->process($data, $class, $processingOptions);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\MappingFailed($errorPrinter->printError($ex), 0, $ex);

			// @phpstan-ignore catch.neverThrown (Processor::process() declares only InvalidData, but orisai reads a date with DateTimeImmutable::createFromFormat(), which throws this for a string with a NUL byte)
		} catch (ValueError $ex) {
			throw new Exceptions\MappingFailed('*root*: ' . $ex->getMessage(), 0, $ex);
		}
	}

	/**
	 * @template T of DateTime|DateTimeImmutable
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws ValueError
	 * @phpstan-pure
	 */
	private static function date(string $class, string|int|float $value): DateTime|DateTimeImmutable
	{
		$text = (string) $value;
		$date = str_contains($text, "\0") ? false : $class::createFromFormat(DateTimeInterface::ATOM, $text);

		if ($date === false) {
			throw self::notADate();
		}

		return $date;
	}

	/**
	 * What Valinor says of a date that none of its formats reads
	 *
	 * @phpstan-pure
	 */
	private static function notADate(): Throwable
	{
		return Valinor\Mapper\Tree\Message\MessageBuilder::newError(
			'Value {source_value} does not match any of the following formats: {formats}.',
		)
			->withCode('cannot_parse_datetime_format')
			->withParameter('formats', '`' . DateTimeInterface::ATOM . '`')
			->build();
	}

	/**
	 * One line for every error, in the order Valinor found them: the path of the value that was refused, a colon and
	 * what was wrong with it. The path of the whole input is Valinor's "*root*"
	 *
	 * Valinor prints a key or a value into the text of its message, and fails doing it when that is not valid UTF-8 (an
	 * unknown key, a list key, a UUID that is refused), with a StringFormatterError from here or from the mapping itself.
	 * The input is refused, not the printing
	 *
	 * @param array<mixed> $data
	 *
	 * @throws Exceptions\MappingFailed
	 */
	private static function print(Valinor\Mapper\MappingError $error, array $data): string
	{
		$lines = [];

		try {
			foreach ($error->messages()->errors() as $message) {
				$lines[] = sprintf('%s: %s', $message->path(), $message->toString());
			}
		} catch (Valinor\Utility\String\StringFormatterError $ex) {
			throw self::unprintable($data, $ex);
		}

		return implode("\n", $lines);
	}

	/**
	 * The same exception is Valinor's answer to a body of a message it cannot parse, which is a defect of whoever wrote
	 * the message, a constructor that refuses a value, and not a fault of the input: it is blamed on the input only when
	 * the input holds a key or a value that is not valid UTF-8, and says what it says otherwise
	 *
	 * @param array<mixed> $data
	 */
	private static function unprintable(
		array $data,
		Valinor\Utility\String\StringFormatterError $error,
	): Exceptions\MappingFailed
	{
		return new Exceptions\MappingFailed(
			self::hasInvalidUtf8($data) ? self::UNPRINTABLE : '*root*: ' . $error->getMessage(),
			0,
			$error,
		);
	}

	/**
	 * @param array<mixed> $data
	 */
	private static function hasInvalidUtf8(array $data): bool
	{
		foreach ($data as $key => $value) {
			if (is_string($key) && !mb_check_encoding($key, 'UTF-8')) {
				return true;
			}

			if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
				return true;
			}

			if (is_array($value) && self::hasInvalidUtf8($value)) {
				return true;
			}
		}

		return false;
	}

}
