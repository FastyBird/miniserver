<?php declare(strict_types = 1);

/**
 * Container.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 * @since          0.7.0
 *
 * @date           11.01.22
 */

namespace FastyBird\Core\Persistence\JsonApi\Hydrators;

use FastyBird\Core\Encoding\JsonApi;
use FastyBird\Core\Encoding\JsonApi\IDocument;
use FastyBird\Core\Exceptions;
use Nette\DI;
use Psr\Log;
use SplObjectStorage;

/**
 * API hydrators container
 *
 * @template T of object
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 */
class Container
{

	/** @var SplObjectStorage<Hydrator<T>, null> */
	private SplObjectStorage $hydrators;

	private Log\LoggerInterface $logger;

	/** @var JsonApi\SchemaContainer<T>|null */
	private JsonApi\SchemaContainer|null $jsonApiSchemaContainer = null;

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
	public function findHydrator(IDocument $document): Hydrator|null
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
	 * @return JsonApi\SchemaContainer<T>
	 *
	 * @throws DI\MissingServiceException
	 */
	private function getSchemaContainer(): JsonApi\SchemaContainer
	{
		if ($this->jsonApiSchemaContainer !== null) {
			return $this->jsonApiSchemaContainer;
		}

		$this->jsonApiSchemaContainer = $this->container->getByType(JsonApi\SchemaContainer::class);

		return $this->jsonApiSchemaContainer;
	}

}
