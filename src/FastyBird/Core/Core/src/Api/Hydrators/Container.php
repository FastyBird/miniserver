<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Hydrators;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use Psr\Log;
use SplObjectStorage;

/**
 * API hydrators container
 *
 * @template T of object
 */
final class Container
{

	/** @var SplObjectStorage<Hydrator<T>, null> */
	private SplObjectStorage $hydrators;

	private Log\LoggerInterface $logger;

	/**
	 * @param Encoding\SchemaContainer<T> $schemaContainer
	 */
	public function __construct(
		private readonly Encoding\SchemaContainer $schemaContainer,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		$this->hydrators = new SplObjectStorage();
	}

	/**
	 * @return Hydrator<T>|null
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	public function findHydrator(Encoding\Document $document): Hydrator|null
	{
		$this->hydrators->rewind();

		foreach ($this->hydrators as $hydrator) {
			$schema = $this->schemaContainer->getSchemaByClassName($hydrator->getEntityName());

			if ($schema->getType() === $document->getResource()->getType()) {
				return $hydrator;
			}
		}

		$this->logger->debug('Hydrator for given document was not found', [
			'source' => 'hydrators-container',
			'type' => 'find-hydrator',
			'document' => [
				'type' => $document->getResource()
					->getType(),
				'id' => $document->getResource()
					->getId(),
			],
		]);

		return null;
	}

	/**
	 * @param Hydrator<T> $hydrator
	 */
	public function add(Hydrator $hydrator): void
	{
		if (!$this->hydrators->offsetExists($hydrator)) {
			$this->hydrators->offsetSet($hydrator);
		}
	}

}
