<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Hydrators;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Exceptions;
use Nette\DI;
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

	/** @var Encoding\SchemaContainer<T>|null */
	private Encoding\SchemaContainer|null $jsonApiSchemaContainer = null;

	public function __construct(
		private readonly DI\Container $container,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		$this->hydrators = new SplObjectStorage();
	}

	/**
	 * @return Hydrator<T>|null
	 *
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidState
	 */
	public function findHydrator(Encoding\IDocument $document): Hydrator|null
	{
		$this->hydrators->rewind();

		foreach ($this->hydrators as $hydrator) {
			$schema = $this->getSchemaContainer()
				->getSchemaByClassName($hydrator->getEntityName());

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

	/**
	 * @return Encoding\SchemaContainer<T>
	 *
	 * @throws DI\MissingServiceException
	 */
	private function getSchemaContainer(): Encoding\SchemaContainer
	{
		if ($this->jsonApiSchemaContainer !== null) {
			return $this->jsonApiSchemaContainer;
		}

		$this->jsonApiSchemaContainer = $this->container->getByType(Encoding\SchemaContainer::class);

		return $this->jsonApiSchemaContainer;
	}

}
