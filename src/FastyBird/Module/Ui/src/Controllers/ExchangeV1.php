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

use FastyBird\Core\Controllers\WebSockets as WebSocketsControllers;
use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Entities\WsServer as WsServerEntities;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Events;
use FastyBird\Module\Ui\Exceptions;
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
final class ExchangeV1 extends WebSocketsControllers\Controller\Controller
{

	public function __construct(
		private readonly Models\Configuration\Widgets\DataSources\Repository $dataSourcesConfigurationRepository,
		private readonly Ui\Logger $logger,
		private readonly ApplicationDocuments\DocumentFactory $documentFactory,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct();
	}

	/**
	 * @param WsServerEntities\Topics\ITopic<mixed> $topic
	 */
	public function actionSubscribe(
		WsServerEntities\IClient $client,
		WsServerEntities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Client subscribed to topic',
			[
				'source' => MetadataTypes\Sources\Module::UI->value,
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
					WebSocketsControllers\WampApplication::MSG_EVENT,
					$topic->getId(),
					Utils\Json::encode([
						'routing_key' => Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
						'source' => MetadataTypes\Sources\Module::UI->value,
						'data' => $dataSource->toArray(),
					]),
				]));
			}
		} catch (Throwable $ex) {
			$this->logger->error(
				'State could not be sent to subscriber',
				[
					'source' => MetadataTypes\Sources\Module::UI->value,
					'type' => 'exchange-controller',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);
		}
	}

	/**
	 * @param array<string, mixed> $args
	 * @param WsServerEntities\Topics\ITopic<mixed> $topic
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws Utils\JsonException
	 */
	public function actionCall(
		array $args,
		WsServerEntities\IClient $client,
		WsServerEntities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Received RPC call from client',
			[
				'source' => MetadataTypes\Sources\Module::UI->value,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
				'data' => $args,
			],
		);

		if (!array_key_exists('routing_key', $args) || !array_key_exists('source', $args)) {
			throw new Exceptions\InvalidArgument('Provided message has invalid format');
		}

		switch ($args['routing_key']) {
			case Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_ACTION_ROUTING_KEY:
				/** @var array<string, mixed>|null $data */
				$data = isset($args['data']) && is_array($args['data']) ? $args['data'] : null;

				if ($data !== null) {
					$document = $this->documentFactory->create(
						Documents\Widgets\DataSources\Actions\Action::class,
						$data,
					);

					$this->handleDataSourceAction($client, $topic, $document);
				}

				break;
			default:
				throw new Exceptions\InvalidArgument('Provided message has unsupported routing key');
		}

		$this->payload->data = [
			'response' => 'accepted',
		];
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	private function handleDataSourceAction(
		WsServerEntities\IClient $client,
		WsServerEntities\Topics\ITopic $topic,
		Documents\Widgets\DataSources\Actions\Action $entity,
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
				WebSocketsControllers\WampApplication::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
					'source' => MetadataTypes\Sources\Module::UI->value,
					'data' => $dataSource->toArray(),
				]),
			]));
		}
	}

}
