<?php declare(strict_types = 1);

/**
 * Build.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Commands;

use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Addon\VirtualThermostat\Queries as VirtualThermostatQueries;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette\Localization;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_key_exists;
use function array_search;
use function array_values;
use function assert;
use function count;
use function sprintf;
use function usort;

/**
 * Virtual thermostat to HomeKit accessory bridge builder command
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Build extends Console\Command\Command
{

	public const NAME = 'fb:virtual-thermostat-addon-homekit-connector-bridge:build';

	public function __construct(
		private readonly VirtualThermostatAddonHomeKitConnector\Logger $logger,
		private readonly Builders\Builder $builder,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly Helpers\Database $databaseHelper,
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
			->setDescription('Bridge builder for Virtual thermostat to HomeKit accessory');
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.title',
			),
		);

		$io->note(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.subtitle',
			),
		);

		$this->askBuildAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function createBridge(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichThermostat($io);

		if ($device === null) {
			$io->warning(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.noVirtualThermostats',
				),
			);

			return;
		}

		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.noHomeKitConnectors',
				),
			);

			return;
		}

		try {
			$bridge = $this->builder->build($device, $connector);

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.create.bridge.success',
					['name' => $bridge->getName() ?? $bridge->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
					'type' => 'build-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.create.bridge.error',
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function editBridge(Style\SymfonyStyle $io): void
	{
		$bridge = $this->askWhichBridge($io);

		if ($bridge === null) {
			$io->warning(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.noBridges',
				),
			);

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.questions.create.bridge',
				),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createBridge($io);
			}

			return;
		}

		try {
			$bridge = $this->builder->build(
				$bridge->getParent(),
				$bridge->getConnector(),
			);

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.update.bridge.success',
					['name' => $bridge->getName() ?? $bridge->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
					'type' => 'build-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.update.bridge.error',
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function deleteBridge(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichBridge($io);

		if ($device === null) {
			$io->info((string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.noBridges',
			));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.remove.bridge.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.questions.continue',
			),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->transaction(function () use ($device): void {
				$this->devicesManager->delete($device);
			});

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.remove.bridge.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
					'type' => 'build-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.messages.remove.bridge.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function listBridges(Style\SymfonyStyle $io): void
	{
		$findDevicesQuery = new Queries\Entities\FindThermostatDevices();

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Thermostat::class);
		usort(
			$devices,
			static fn (Entities\Devices\Thermostat $a, Entities\Devices\Thermostat $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.data.name',
			),
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.data.thermostat',
			),
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.data.connector',
			),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$device->getParent()->getName() ?? $device->getParent()->getIdentifier(),
				$device->getConnector()->getName() ?? $device->getConnector()->getIdentifier(),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function askBuildAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.questions.whatToDo',
			),
			[
				0 => (string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.create.bridge',
				),
				1 => (string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.update.bridge',
				),
				2 => (string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.remove.bridge',
				),
				3 => (string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.list.bridges',
				),
				4 => (string) $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.nothing',
				),
			],
			4,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
			),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.create.bridge',
			)
			|| $whatToDo === '0'
		) {
			$this->createBridge($io);

			$this->askBuildAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.update.bridge',
			)
			|| $whatToDo === '1'
		) {
			$this->editBridge($io);

			$this->askBuildAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.remove.bridge',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteBridge($io);

			$this->askBuildAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.list.bridges',
			)
			|| $whatToDo === '3'
		) {
			$this->listBridges($io);

			$this->askBuildAction($io);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): HomeKitEntities\Connectors\Connector|null
	{
		$connectors = [];

		$findConnectorsQuery = new HomeKitQueries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			HomeKitEntities\Connectors\Connector::class,
		);
		usort(
			$systemConnectors,
			static fn (HomeKitEntities\Connectors\Connector $a, HomeKitEntities\Connectors\Connector $b): int => (
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
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.questions.select.item.connector',
			),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
			),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connectors): HomeKitEntities\Connectors\Connector {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
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
					$findConnectorQuery = new HomeKitQueries\Entities\FindConnectors();
					$findConnectorQuery->byIdentifier($identifier);

					$connector = $this->connectorsRepository->findOneBy(
						$findConnectorQuery,
						HomeKitEntities\Connectors\Connector::class,
					);

					if ($connector !== null) {
						return $connector;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$connector = $io->askQuestion($question);
		assert($connector instanceof HomeKitEntities\Connectors\Connector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichThermostat(
		Style\SymfonyStyle $io,
	): VirtualThermostatEntities\Devices\Device|null
	{
		$devices = [];

		$findDevicesQuery = new VirtualThermostatQueries\Entities\FindDevices();

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			VirtualThermostatEntities\Devices\Device::class,
		);
		usort(
			$connectorDevices,
			static fn (VirtualThermostatEntities\Devices\Device $a, VirtualThermostatEntities\Devices\Device $b): int => (
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
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.questions.select.item.device',
			),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
			),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($devices): VirtualThermostatEntities\Devices\Device {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
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
					$findDeviceQuery = new VirtualThermostatQueries\Entities\FindDevices();
					$findDeviceQuery->byIdentifier($identifier);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						VirtualThermostatEntities\Devices\Device::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof VirtualThermostatEntities\Devices\Device);

		return $device;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichBridge(
		Style\SymfonyStyle $io,
	): Entities\Devices\Thermostat|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindThermostatDevices();

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\Devices\Thermostat::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\Devices\Thermostat $a, Entities\Devices\Thermostat $b): int => (
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
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.questions.select.item.device',
			),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
			),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($devices): Entities\Devices\Thermostat {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
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
					$findDeviceQuery = new Queries\Entities\FindThermostatDevices();
					$findDeviceQuery->byIdentifier($identifier);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\Devices\Thermostat::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\Devices\Thermostat);

		return $device;
	}

}
