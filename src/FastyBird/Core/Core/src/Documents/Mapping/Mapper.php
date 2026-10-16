<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use CuyZ\Valinor;
use DateTimeInterface;
use FastyBird\Core\Documents\Exceptions;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function implode;
use function is_subclass_of;
use function sprintf;

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
			throw new Exceptions\MappingFailed(self::print($ex), 0, $ex);
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
		}
	}

	/**
	 * One line for every error, in the order Valinor found them: the path of the value that was refused, a colon and
	 * what was wrong with it. The path of the whole input is Valinor's "*root*"
	 */
	private static function print(Valinor\Mapper\MappingError $error): string
	{
		$lines = [];

		foreach ($error->messages()->errors() as $message) {
			$lines[] = sprintf('%s: %s', $message->path(), $message->toString());
		}

		return implode("\n", $lines);
	}

}
