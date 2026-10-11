<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Models\States;

use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\States;
use InvalidArgumentException;
use PHPOnCouch;
use Psr\Log;
use Ramsey\Uuid;
use stdClass;
use Throwable;
use function count;
use function is_array;
use function is_object;

/**
 * State repository
 *
 * @template T of States\State
 */
final readonly class StatesRepository
{

	/**
	 * @param class-string<T> $entity
	 */
	public function __construct(
		private Connections\Connection $client,
		private States\StateFactory $stateFactory,
		private string $entity = States\State::class,
		private Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 */
	public function findOne(Uuid\UuidInterface $id): States\State|null
	{
		$doc = $this->getDocument($id);

		if ($doc === null) {
			return null;
		}

		return $this->stateFactory->create($this->entity, $doc);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getDocument(
		Uuid\UuidInterface $id,
	): PHPOnCouch\CouchDocument|null
	{
		try {
			$this->client->getClient()->asCouchDocuments();

			/** @var array<stdClass>|mixed $docs */
			$docs = $this->client->getClient()
				->find([
					'id' => [
						'$eq' => $id->toString(),
					],
				]);

			if (is_array($docs) && count($docs) >= 1 && is_object($docs[0])) {
				$doc = new PHPOnCouch\CouchDocument($this->client->getClient());

				return $doc->loadFromObject($docs[0]);
			}

			return null;
		} catch (PHPOnCouch\Exceptions\CouchNotFoundException) {
			return null;
		} catch (Throwable $ex) {
			$this->logger->error('Content could not be loaded', [
				'source' => Sources\Plugin::COUCHDB->value,
				'type' => 'state-repository',
				'record' => [
					'id' => $id->toString(),
				],
				'exception' => Logging\Logger::buildException($ex),
			]);

			throw new Exceptions\InvalidState(
				'Content could not be loaded from database: ' . $ex->getMessage(),
				0,
				$ex,
			);
		}
	}

}
