<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\States;

use FastyBird\Core\Documents;
use FastyBird\Plugin\CouchDb\Exceptions;
use InvalidArgumentException;
use PHPOnCouch;
use function class_exists;

/**
 * State object factory
 */
final readonly class StateFactory
{

	public function __construct(private Documents\Mapping\Mapper $stateMapper)
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
			return $this->stateMapper->map(
				$data,
				$class,
				new Documents\Mapping\MappingOptions(allowUnknownFields: true),
			);
		} catch (Documents\Exceptions\MappingFailed $ex) {
			throw new Exceptions\InvalidArgument('Could not map data to state: ' . $ex->getMessage());
		}
	}

}
