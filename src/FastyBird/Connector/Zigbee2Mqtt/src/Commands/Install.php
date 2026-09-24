<?php declare(strict_types = 1);

/**
 * Install.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           11.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Commands;

use Doctrine\DBAL;
use Exception;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Types as Zigbee2MqttTypes;
use FastyBird\Core\Clock;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Commands as DevicesCommands;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette\Localization;
use Nette\Utils;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_search;
use function array_values;
use function assert;
use function count;
use function intval;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector install command
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:zigbee2mqtt-connector:install';

	private Input\InputInterface|null $input = null;

	private Output\OutputInterface|null $output = null;

	public function __construct(
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesManager $connectorsPropertiesManager,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly Helpers\Database $databaseHelper,
		private readonly Clock\Clock $clock,
		private readonly Localization\Translator $translator,
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Zigbee2MQTT connector installer');
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$this->input = $input;
		$this->output = $output;

		$io = new Style\SymfonyStyle($this->input, $this->output);

		$io->title((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.title'));

		$io->note((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.subtitle'));

		$this->askInstallAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createConnector(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.questions.provide.connector.identifier',
			),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\Connectors\Connector::class,
				);

				if ($connector !== null) {
					throw new Exceptions\Runtime(
						(string) $this->translator->translate(
							'//zigbee2mqtt-connector.cmd.install.messages.identifier.connector.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'zigbee2mqtt-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\Connectors\Connector::class,
				);

				if ($connector === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.identifier.connector.missing',
				),
			);

			return;
		}

		$name = $this->askConnectorName($io);

		$serverAddress = $this->askConnectorServerAddress($io);
		$serverPort = $this->askConnectorServerPort($io);
		$serverSecuredPort = $this->askConnectorServerSecuredPort($io);
		$username = $this->askConnectorUsername($io);
		$password = $username !== null ? $this->askConnectorPassword($io) : null;

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Connectors\Connector::class,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($connector instanceof Entities\Connectors\Connector);

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::CLIENT_MODE->value,
				'dataType' => ValuesTypes\DataType::STRING,
				'value' => Zigbee2MqttTypes\ClientMode::MQTT->value,
				'connector' => $connector,
			]));

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::SERVER->value,
				'dataType' => ValuesTypes\DataType::STRING,
				'value' => $serverAddress,
				'connector' => $connector,
			]));

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::PORT->value,
				'dataType' => ValuesTypes\DataType::UINT,
				'value' => $serverPort,
				'connector' => $connector,
			]));

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::SECURED_PORT->value,
				'dataType' => ValuesTypes\DataType::UINT,
				'value' => $serverSecuredPort,
				'connector' => $connector,
			]));

			if ($username !== null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::USERNAME->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'value' => $username,
					'connector' => $connector,
				]));
			}

			if ($password !== null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::PASSWORD->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'value' => $password,
					'connector' => $connector,
				]));
			}

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.create.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.create.connector.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.create.bridges'),
			true,
		);

		$createBridge = (bool) $io->askQuestion($question);

		if ($createBridge) {
			$connector = $this->connectorsRepository->find(
				$connector->getId(),
				Entities\Connectors\Connector::class,
			);
			assert($connector instanceof Entities\Connectors\Connector);

			$this->createBridge($io, $connector);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function editConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.create.connector'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createConnector($io);
			}

			return;
		}

		$name = $this->askConnectorName($io, $connector);

		$enabled = $connector->isEnabled();

		if ($connector->isEnabled()) {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.questions.disable.connector',
				),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.enable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		$serverAddress = $this->askConnectorServerAddress($io, $connector);
		$serverPort = $this->askConnectorServerPort($io, $connector);
		$serverSecuredPort = $this->askConnectorServerSecuredPort($io, $connector);
		$username = $this->askConnectorUsername($io, $connector);
		$password = $username !== null ? $this->askConnectorPassword($io, $connector) : null;

		$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Zigbee2MqttTypes\ConnectorPropertyIdentifier::CLIENT_MODE);

		$clientModeProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Zigbee2MqttTypes\ConnectorPropertyIdentifier::SERVER);

		$serverAddressProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Zigbee2MqttTypes\ConnectorPropertyIdentifier::PORT);

		$serverPortProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Zigbee2MqttTypes\ConnectorPropertyIdentifier::SECURED_PORT);

		$serverSecuredProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Zigbee2MqttTypes\ConnectorPropertyIdentifier::USERNAME);

		$usernameProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Zigbee2MqttTypes\ConnectorPropertyIdentifier::PASSWORD);

		$passwordProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));
			assert($connector instanceof Entities\Connectors\Connector);

			if ($clientModeProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::CLIENT_MODE->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'value' => Zigbee2MqttTypes\ClientMode::MQTT->value,
					'connector' => $connector,
				]));
			}

			if ($serverAddressProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::SERVER->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'value' => $serverAddress,
					'connector' => $connector,
				]));
			} elseif ($serverAddressProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->connectorsPropertiesManager->update($serverAddressProperty, Utils\ArrayHash::from([
					'value' => $serverAddress,
				]));
			}

			if ($serverPortProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::PORT->value,
					'dataType' => ValuesTypes\DataType::UINT,
					'value' => $serverPort,
					'connector' => $connector,
				]));
			} elseif ($serverPortProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->connectorsPropertiesManager->update($serverPortProperty, Utils\ArrayHash::from([
					'value' => $serverPort,
				]));
			}

			if ($serverSecuredProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::SECURED_PORT->value,
					'dataType' => ValuesTypes\DataType::UINT,
					'value' => $serverSecuredPort,
					'connector' => $connector,
				]));
			} elseif ($serverSecuredProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->connectorsPropertiesManager->update($serverSecuredProperty, Utils\ArrayHash::from([
					'value' => $serverSecuredPort,
				]));
			}

			if ($username !== null) {
				if ($usernameProperty === null) {
					$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::USERNAME->value,
						'dataType' => ValuesTypes\DataType::STRING,
						'value' => $username,
						'connector' => $connector,
					]));
				} elseif ($usernameProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
					$this->connectorsPropertiesManager->update($usernameProperty, Utils\ArrayHash::from([
						'value' => $username,
					]));
				}
			} elseif ($usernameProperty !== null) {
				$this->connectorsPropertiesManager->delete($usernameProperty);
			}

			if ($password !== null) {
				if ($passwordProperty === null) {
					$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Zigbee2MqttTypes\ConnectorPropertyIdentifier::PASSWORD->value,
						'dataType' => ValuesTypes\DataType::STRING,
						'value' => $password,
						'connector' => $connector,
					]));
				} elseif ($passwordProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
					$this->connectorsPropertiesManager->update($passwordProperty, Utils\ArrayHash::from([
						'value' => $password,
					]));
				}
			} elseif ($passwordProperty !== null) {
				$this->connectorsPropertiesManager->delete($passwordProperty);
			}

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.update.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.update.connector.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.manage.bridges'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$connector = $this->connectorsRepository->find(
			$connector->getId(),
			Entities\Connectors\Connector::class,
		);
		assert($connector instanceof Entities\Connectors\Connector);

		$this->askManageConnectorAction($io, $connector);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function deleteConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.messages.remove.connector.confirm',
				['name' => $connector->getName() ?? $connector->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->connectorsManager->delete($connector);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.remove.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.remove.connector.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function manageConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$this->askManageConnectorAction($io, $connector);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function listConnectors(Style\SymfonyStyle $io): void
	{
		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$connectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\Connectors\Connector::class,
		);
		usort(
			$connectors,
			static fn (Entities\Connectors\Connector $a, Entities\Connectors\Connector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.bridgesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Bridge::class);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				count($devices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createBridge(Style\SymfonyStyle $io, Entities\Connectors\Connector $connector): void
	{
		$identifier = $this->findNextDeviceIdentifier($connector, 'zigbee2mqtt-bridge-%d');

		$name = $this->askDeviceName($io);

		$baseTopic = $this->askDeviceBaseTopic($io);

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$bridge = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Devices\Bridge::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($bridge instanceof Entities\Devices\Bridge);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Zigbee2MqttTypes\DevicePropertyIdentifier::BASE_TOPIC->value,
				'dataType' => ValuesTypes\DataType::STRING,
				'value' => $baseTopic,
				'device' => $bridge,
			]));

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.create.bridge.success',
					['name' => $bridge->getName() ?? $bridge->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.create.bridge.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.manage.devices'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$bridge = $this->devicesRepository->find($bridge->getId(), Entities\Devices\Bridge::class);
		assert($bridge instanceof Entities\Devices\Bridge);

		$this->askManageBridgeAction($io, $connector, $bridge);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function editBridge(Style\SymfonyStyle $io, Entities\Connectors\Connector $connector): void
	{
		$bridge = $this->askWhichBridge($io, $connector);

		if ($bridge === null) {
			$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.noBridges'));

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.create.bridge'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createBridge($io, $connector);
			}

			return;
		}

		$name = $this->askDeviceName($io, $bridge);

		$baseTopic = $this->askDeviceBaseTopic($io, $bridge);

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceVariableProperties();
		$findDevicePropertyQuery->forDevice($bridge);
		$findDevicePropertyQuery->byIdentifier(Zigbee2MqttTypes\DevicePropertyIdentifier::BASE_TOPIC);

		$baseTopicProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$bridge = $this->devicesManager->update($bridge, Utils\ArrayHash::from([
				'name' => $name,
			]));
			assert($bridge instanceof Entities\Devices\Bridge);

			if ($baseTopicProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Zigbee2MqttTypes\DevicePropertyIdentifier::BASE_TOPIC->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'value' => $baseTopic,
					'device' => $bridge,
				]));
			} elseif ($baseTopicProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($baseTopicProperty, Utils\ArrayHash::from([
					'value' => $baseTopic,
				]));
			}

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.update.bridge.success',
					['name' => $bridge->getName() ?? $bridge->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.update.bridge.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.manage.devices'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$bridge = $this->devicesRepository->find($bridge->getId(), Entities\Devices\Bridge::class);
		assert($bridge instanceof Entities\Devices\Bridge);

		$this->askManageBridgeAction($io, $connector, $bridge);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function deleteBridge(Style\SymfonyStyle $io, Entities\Connectors\Connector $connector): void
	{
		$bridge = $this->askWhichBridge($io, $connector);

		if ($bridge === null) {
			$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.noBridges'));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.messages.remove.bridge.confirm',
				['name' => $bridge->getName() ?? $bridge->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->devicesManager->delete($bridge);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.remove.bridge.success',
					['name' => $bridge->getName() ?? $bridge->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.remove.bridge.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function manageBridge(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): void
	{
		$bridge = $this->askWhichBridge($io, $connector);

		if ($bridge === null) {
			$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.noBridges'));

			return;
		}

		$this->askManageBridgeAction($io, $connector, $bridge);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listBridges(Style\SymfonyStyle $io, Entities\Connectors\Connector $connector): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);
		usort(
			$devices,
			static fn (Entities\Devices\Device $a, Entities\Devices\Device $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.baseTopic'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.devicesCnt'),
		]);

		foreach ($devices as $index => $device) {
			$findDevicePropertyQuery = new Queries\Entities\FindDeviceVariableProperties();
			$findDevicePropertyQuery->forDevice($device);
			$findDevicePropertyQuery->byIdentifier(Zigbee2MqttTypes\DevicePropertyIdentifier::BASE_TOPIC);

			$baseTopicProperty = $this->devicesPropertiesRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesEntities\Devices\Properties\Variable::class,
			);

			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forParent($device);

			$childDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);

			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$baseTopicProperty?->getValue(),
				count($childDevices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function discoverDevices(Style\SymfonyStyle $io, Entities\Connectors\Connector $connector): void
	{
		$findDevicesQuery = new Queries\Entities\FindBridgeDevices();
		$findDevicesQuery->forConnector($connector);

		if ($this->devicesRepository->getResultSet($findDevicesQuery, Entities\Devices\Bridge::class)->count() === 0) {
			$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.noBridges'));

			return;
		}

		if ($this->output === null) {
			throw new Exceptions\InvalidState('Something went wrong, console output is not configured');
		}

		$executedTime = $this->clock->getNow();

		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			throw new Exceptions\InvalidState('Something went wrong, console app is not configured');
		}

		$serviceCmd = $symfonyApp->find(DevicesCommands\Connector::NAME);

		$io->info(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.discover.starting'),
		);

		$result = $serviceCmd->run(new Input\ArrayInput([
			'--connector' => $connector->getId()->toString(),
			'--mode' => DevicesTypes\ConnectorMode::DISCOVER->value,
			'--no-interaction' => true,
			'--quiet' => true,
		]), $this->output);

		$this->databaseHelper->clear();

		$io->newLine(2);

		$io->info(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.discover.stopping'),
		);

		if ($result !== Console\Command\Command::SUCCESS) {
			$io->error(
				(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.discover.error'),
			);

			return;
		}

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.id'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.model'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.manufacturer'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.bridge'),
		]);

		$foundDevices = 0;

		$findDevicesQuery = new Queries\Entities\FindBridgeDevices();
		$findDevicesQuery->forConnector($connector);

		$bridges = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Bridge::class);

		foreach ($bridges as $bridge) {
			$findDevicesQuery = new Queries\Entities\FindSubDevices();
			$findDevicesQuery->forConnector($bridge->getConnector());
			$findDevicesQuery->forParent($bridge);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\SubDevice::class);

			foreach ($devices as $device) {
				$createdAt = $device->getCreatedAt();

				if (
					$createdAt !== null
					&& $createdAt->getTimestamp() > $executedTime->getTimestamp()
				) {
					$foundDevices++;

					$table->addRow([
						$foundDevices,
						$device->getId()->toString(),
						$device->getName() ?? $device->getIdentifier(),
						$device->getHardwareModel(),
						$device->getHardwareManufacturer(),
						$bridge->getName() ?? $bridge->getIdentifier(),
					]);
				}
			}
		}

		if ($foundDevices > 0) {
			$io->info(sprintf(
				(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.foundDevices'),
				$foundDevices,
			));

			$table->render();

			$io->newLine();

		} else {
			$io->info(
				(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.noDevicesFound'),
			);
		}

		$io->success(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.discover.success'),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function editDevice(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Bridge $bridge,
	): void
	{
		$device = $this->askWhichDevice($io, $connector, $bridge);

		if ($device === null) {
			$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.noDevices'));

			return;
		}

		$name = $this->askDeviceName($io, $device);

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.update.device.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function deleteDevice(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Bridge $bridge,
	): void
	{
		$device = $this->askWhichDevice($io, $connector, $bridge);

		if ($device === null) {
			$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.messages.noDevices'));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.messages.remove.device.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->devicesManager->delete($device);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.messages.remove.device.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function listDevices(Style\SymfonyStyle $io, Entities\Devices\Bridge $bridge): void
	{
		$findDevicesQuery = new Queries\Entities\FindSubDevices();
		$findDevicesQuery->forParent($bridge);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\SubDevice::class);
		usort(
			$devices,
			static fn (Entities\Devices\SubDevice $a, Entities\Devices\SubDevice $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.data.name'),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askInstallAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.actions.create.connector',
				),
				1 => (string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.actions.update.connector',
				),
				2 => (string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.actions.remove.connector',
				),
				3 => (string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.actions.manage.connector',
				),
				4 => (string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.actions.list.connectors',
				),
				5 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.create.connector',
			)
			|| $whatToDo === '0'
		) {
			$this->createConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.update.connector',
			)
			|| $whatToDo === '1'
		) {
			$this->editConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.remove.connector',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.manage.connector',
			)
			|| $whatToDo === '3'
		) {
			$this->manageConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.list.connectors',
			)
			|| $whatToDo === '4'
		) {
			$this->listConnectors($io);

			$this->askInstallAction($io);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askManageConnectorAction(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.create.bridge'),
				1 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.update.bridge'),
				2 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.remove.bridge'),
				3 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.manage.bridge'),
				4 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.list.bridges'),
				5 => (string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.install.actions.discover.devices',
				),
				6 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.nothing'),
			],
			6,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.create.bridge',
			)
			|| $whatToDo === '0'
		) {
			$this->createBridge($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.update.bridge',
			)
			|| $whatToDo === '1'
		) {
			$this->editBridge($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.remove.bridge',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteBridge($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.remove.bridge',
			)
			|| $whatToDo === '3'
		) {
			$this->manageBridge($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.list.bridges',
			)
			|| $whatToDo === '4'
		) {
			$this->listBridges($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.discover.devices',
			)
			|| $whatToDo === '5'
		) {
			$this->discoverDevices($io, $connector);

			$this->askManageConnectorAction($io, $connector);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function askManageBridgeAction(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Bridge $bridge,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.update.device'),
				1 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.remove.device'),
				2 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.list.devices'),
				3 => (string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.actions.nothing'),
			],
			3,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.update.device',
			)
			|| $whatToDo === '0'
		) {
			$this->editDevice($io, $connector, $bridge);

			$this->askManageBridgeAction($io, $connector, $bridge);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.remove.device',
			)
			|| $whatToDo === '1'
		) {
			$this->deleteDevice($io, $connector, $bridge);

			$this->askManageBridgeAction($io, $connector, $bridge);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.actions.list.devices',
			)
			|| $whatToDo === '2'
		) {
			$this->listDevices($io, $bridge);

			$this->askManageBridgeAction($io, $connector, $bridge);
		}
	}

	private function askConnectorName(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.questions.provide.connector.name',
			),
			$connector?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askConnectorServerAddress(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector|null $connector = null,
	): string
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.questions.provide.connector.address',
			),
			$connector?->getServerAddress() ?? Entities\Connectors\Connector::DEFAULT_SERVER_ADDRESS,
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askConnectorServerPort(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector|null $connector = null,
	): int
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.questions.provide.connector.port',
			),
			$connector?->getServerPort() ?? Entities\Connectors\Connector::DEFAULT_SERVER_PORT,
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			return $answer;
		});

		return intval($io->askQuestion($question));
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askConnectorServerSecuredPort(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector|null $connector = null,
	): int
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.questions.provide.connector.securedPort',
			),
			$connector?->getServerSecuredPort() ?? Entities\Connectors\Connector::DEFAULT_SERVER_SECURED_PORT,
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			return $answer;
		});

		return intval($io->askQuestion($question));
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askConnectorUsername(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.questions.provide.connector.username',
			),
			$connector?->getUsername(),
		);

		$username = $io->askQuestion($question);

		return strval($username) === '' ? null : strval($username);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askConnectorPassword(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.questions.provide.connector.password',
			),
			$connector?->getPassword(),
		);

		$password = $io->askQuestion($question);

		return strval($password) === '' ? null : strval($password);
	}

	private function askDeviceName(Style\SymfonyStyle $io, Entities\Devices\Device|null $device = null): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.provide.device.name'),
			$device?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askDeviceBaseTopic(
		Style\SymfonyStyle $io,
		Entities\Devices\Bridge|null $device = null,
	): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.questions.provide.device.baseTopic',
			),
			$device?->getBaseTopic() ?? Entities\Devices\Bridge::BASE_TOPIC,
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\Connectors\Connector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\Connectors\Connector::class,
		);
		usort(
			$systemConnectors,
			static fn (Entities\Connectors\Connector $a, Entities\Connectors\Connector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($systemConnectors as $connector) {
			$connectors[$connector->getIdentifier()] = $connector->getName() ?? $connector->getIdentifier();
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//zigbee2mqtt-connector.cmd.install.questions.select.item.connector',
			),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\Connectors\Connector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($connectors))) {
				$answer = array_values($connectors)[$answer];
			}

			$identifier = array_search($answer, $connectors, true);

			if ($identifier !== false) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\Connectors\Connector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\Connectors\Connector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichBridge(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): Entities\Devices\Bridge|null
	{
		$bridges = [];

		$findDevicesQuery = new Queries\Entities\FindBridgeDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Bridge::class);
		usort(
			$connectorDevices,
			static fn (Entities\Devices\Bridge $a, Entities\Devices\Bridge $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($connectorDevices as $bridge) {
			$bridges[$bridge->getIdentifier()] = $bridge->getName() ?? $bridge->getIdentifier();
		}

		if (count($bridges) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.select.item.bridge'),
			array_values($bridges),
			count($bridges) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $bridges): Entities\Devices\Bridge {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($bridges))) {
					$answer = array_values($bridges)[$answer];
				}

				$identifier = array_search($answer, $bridges, true);

				if ($identifier !== false) {
					$findDeviceQuery = new Queries\Entities\FindBridgeDevices();
					$findDeviceQuery->byIdentifier($identifier);
					$findDeviceQuery->forConnector($connector);

					$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Bridge::class);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$bridge = $io->askQuestion($question);
		assert($bridge instanceof Entities\Devices\Bridge);

		return $bridge;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Bridge $bridge,
	): Entities\Devices\SubDevice|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindSubDevices();
		$findDevicesQuery->forConnector($connector);
		$findDevicesQuery->forParent($bridge);

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\Devices\SubDevice::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\Devices\SubDevice $a, Entities\Devices\SubDevice $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($connectorDevices as $device) {
			$devices[$device->getIdentifier()] = $device->getName() ?? $device->getIdentifier();
		}

		if (count($devices) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.install.questions.select.item.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $bridge, $devices): Entities\Devices\SubDevice {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($devices))) {
					$answer = array_values($devices)[$answer];
				}

				$identifier = array_search($answer, $devices, true);

				if ($identifier !== false) {
					$findDeviceQuery = new Queries\Entities\FindSubDevices();
					$findDeviceQuery->byIdentifier($identifier);
					$findDeviceQuery->forConnector($connector);
					$findDeviceQuery->forParent($bridge);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\Devices\SubDevice::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\Devices\SubDevice);

		return $device;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function findNextDeviceIdentifier(Entities\Connectors\Connector $connector, string $pattern): string
	{
		for ($i = 1; $i <= 100; $i++) {
			$identifier = sprintf($pattern, $i);

			$findDeviceQuery = new Queries\Entities\FindDevices();
			$findDeviceQuery->forConnector($connector);
			$findDeviceQuery->byIdentifier($identifier);

			$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class);

			if ($device === null) {
				return $identifier;
			}
		}

		throw new Exceptions\InvalidState('Could not find free device identifier');
	}

}
