<?php declare(strict_types = 1);

/**
 * StatesRepository.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           02.03.20
 */

namespace FastyBird\Plugin\CouchDb\Models\States;

use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\CouchDb\Connections;
use FastyBird\Plugin\CouchDb\Exceptions;
use FastyBird\Plugin\CouchDb\States;
use InvalidArgumentException;
use Nette;
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
 *
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesRepository
{

	use Nette\SmartObject;

	/**
	 * @param class-string<T> $entity
	 */
	public function __construct(
		private readonly Connections\Connection $client,
		private readonly States\StateFactory $stateFactory,
		private readonly string $entity = States\State::class,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
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
