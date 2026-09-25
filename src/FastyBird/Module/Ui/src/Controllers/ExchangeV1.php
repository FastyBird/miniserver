<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Controllers;

use FastyBird\Core\Constants;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Entities\Topics;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Events;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use FastyBird\Module\Ui\Types;
use Nette\Utils;
use Psr\EventDispatcher;
use Throwable;
use function array_key_exists;
use function is_array;

/**
 * Exchange sockets controller
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExchangeV1 extends Controllers\Controller
{

	/**
	 * The roles the HTTP API requires to change a widget data source: DataSourcesV1 limits its
	 * create, update and delete to them.
	 */
	private const array WRITE_ROLES = [
		Constants::ROLE_MANAGER,
		Constants::ROLE_ADMINISTRATOR,
	];

	public function __construct(
		private readonly Models\Configuration\Widgets\DataSources\Repository $dataSourcesConfigurationRepository,
		private readonly Ui\Logger $logger,
		private readonly CoreDocuments\DocumentFactory $documentFactory,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct();
	}

	/**
	 * @param Topics\ITopic<mixed> $topic
	 */
	public function actionSubscribe(
		Entities\ConnectedClient $client,
		Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Client subscribed to topic',
			[
				'source' => Sources\Module::UI->value,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
			],
		);

		try {
			$findDataSources = new Queries\Configuration\FindWidgetDataSources();

			$dataSources = $this->dataSourcesConfigurationRepository->findAllBy($findDataSources);

			foreach ($dataSources as $dataSource) {
				$client->send(Utils\Json::encode([
					Controllers\WampApplication::MSG_EVENT,
					$topic->getId(),
					Utils\Json::encode([
						'routing_key' => Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
						'source' => Sources\Module::UI->value,
						'data' => $dataSource->toArray(),
					]),
				]));
			}
		} catch (Throwable $ex) {
			$this->logger->error(
				'State could not be sent to subscriber',
				[
					'source' => Sources\Module::UI->value,
					'type' => 'exchange-controller',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		}
	}

	/**
	 * @param array<string, mixed> $args
	 * @param Topics\ITopic<mixed> $topic
	 *
	 * @throws UiExceptions\InvalidArgument
	 * @throws UiExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws Utils\JsonException
	 * @throws WebSocketsExceptions\ForbiddenRequest
	 */
	public function actionCall(
		array $args,
		Entities\ConnectedClient $client,
		Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Received RPC call from client',
			[
				'source' => Sources\Module::UI->value,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
				'data' => $args,
			],
		);

		// Every call needs an authenticated client, as every HTTP controller of this module does
		$this->authorize($client);

		if (!array_key_exists('routing_key', $args) || !array_key_exists('source', $args)) {
			throw new UiExceptions\InvalidArgument('Provided message has invalid format');
		}

		switch ($args['routing_key']) {
			case Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_ACTION_ROUTING_KEY:
				/** @var array<string, mixed>|null $data */
				$data = isset($args['data']) && is_array($args['data']) ? $args['data'] : null;

				$document = $data !== null
					? $this->documentFactory->create(
						UiDocuments\Widgets\DataSources\Actions\Action::class,
						$data,
					)
					: null;

				// A GET reads the data source, which DataSourcesV1 serves to any authenticated
				// user -- and the caller already is one. A SET, or an action that cannot be read,
				// changes state and takes the rule for a change.
				if ($document?->getAction() !== Types\DataSourceAction::GET) {
					$this->authorize($client, ...self::WRITE_ROLES);
				}

				if ($document !== null) {
					$this->handleDataSourceAction($client, $topic, $document);
				}

				break;
			default:
				throw new UiExceptions\InvalidArgument('Provided message has unsupported routing key');
		}

		$this->getPayload()->data = [
			'response' => 'accepted',
		];
	}

	/**
	 * @throws UiExceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	private function handleDataSourceAction(
		Entities\ConnectedClient $client,
		Topics\ITopic $topic,
		UiDocuments\Widgets\DataSources\Actions\Action $entity,
	): void
	{
		if ($entity->getAction() === Types\DataSourceAction::SET) {
			$dataSource = $this->dataSourcesConfigurationRepository->find($entity->getDataSource());

			if ($dataSource === null) {
				return;
			}

			$this->dispatcher?->dispatch(new Events\ActionCommandReceived($entity, $dataSource));

		} elseif ($entity->getAction() === Types\DataSourceAction::GET) {
			$dataSource = $this->dataSourcesConfigurationRepository->find($entity->getDataSource());

			if ($dataSource === null) {
				return;
			}

			$this->dispatcher?->dispatch(new Events\ActionCommandReceived($entity, $dataSource));

			$client->send(Utils\Json::encode([
				Controllers\WampApplication::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
					'source' => Sources\Module::UI->value,
					'data' => $dataSource->toArray(),
				]),
			]));
		}
	}

}
