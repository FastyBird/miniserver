<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Mapping;

use FastyBird\Core\Documents\Exceptions;
use Orisai\ObjectMapper;

/**
 * The one place a plain array becomes an object of a mapped class
 *
 * Every consumer maps through map() and catches Exceptions\MappingFailed; none of them imports the mapping library
 * or prints its errors
 */
final readonly class Mapper
{

	public function __construct(private ObjectMapper\Processing\Processor $processor)
	{
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
	public function map(array $data, string $class, MappingOptions $options = new MappingOptions()): object
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

}
