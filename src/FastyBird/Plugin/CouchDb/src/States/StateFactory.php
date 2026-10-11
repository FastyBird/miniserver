<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\States;

use FastyBird\Plugin\CouchDb\Exceptions;
use InvalidArgumentException;
use Orisai\ObjectMapper;
use PHPOnCouch;
use function class_exists;

/**
 * State object factory
 */
final readonly class StateFactory
{

	public function __construct(
		private ObjectMapper\Processing\Processor $stateMapper,
	)
	{
	}

	/**
	 * @template T of State
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	public function create(string $class, PHPOnCouch\CouchDocument $document): State
	{
		if (!class_exists($class)) {
			throw new Exceptions\InvalidState('State could not be created');
		}

		$data = [];

		foreach ($document->getKeys() as $key) {
			$data[$key] = $document->get($key);
		}

		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->stateMapper->process($data, $class, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\InvalidArgument('Could not map data to state: ' . $errorPrinter->printError($ex));
		}
	}

}
