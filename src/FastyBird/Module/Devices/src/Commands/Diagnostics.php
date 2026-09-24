<?php declare(strict_types = 1);

/**
 * Diagnostics.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           11.08.24
 */

namespace FastyBird\Module\Devices\Commands;

use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Nette;
use Nette\Localization;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_merge;
use function array_search;
use function array_values;
use function assert;
use function count;
use function sprintf;
use function usort;

/**
 * Module diagnostics command
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Diagnostics extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:devices-module:diagnostics';

	public function __construct(
		private readonly Models\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Devices\Utilities\ConnectorConnection $connectorConnectionManager,
		private readonly Devices\Utilities\DeviceConnection $deviceConnectionManager,
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
			->setDescription('Devices module diagnostics')
			->setDefinition(
				new Input\InputDefinition([
					new Input\InputOption(
						'connector',
						'c',
						Input\InputOption::VALUE_REQUIRED,
						'Connector ID or identifier',
					),
				]),
			);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		if ($input->getOption('quiet') === false) {
			$io->title((string) $this->translator->translate('//devices-module.cmd.diagnostics.title'));

			$io->note((string) $this->translator->translate('//devices-module.cmd.diagnostics.subtitle'));
		}

		$this->askDiagnosticAction($io, $output);

		return self::SUCCESS;
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askDiagnosticAction(Style\SymfonyStyle $io, Output\OutputInterface $output): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//devices-module.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate(
					'//devices-module.cmd.diagnostics.actions.list.connectors',
				),
				1 => (string) $this->translator->translate('//devices-module.cmd.diagnostics.actions.list.devices'),
				2 => (string) $this->translator->translate('//devices-module.cmd.diagnostics.actions.list.channels'),
				3 => (string) $this->translator->translate('//devices-module.cmd.diagnostics.actions.list.properties'),
				4 => (string) $this->translator->translate('//devices-module.cmd.diagnostics.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//devices-module.cmd.diagnostics.actions.list.connectors',
			)
			|| $whatToDo === '0'
		) {
			$this->listConnectors($io, $output);

			$this->askDiagnosticAction($io, $output);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//devices-module.cmd.diagnostics.actions.list.devices',
			)
			|| $whatToDo === '1'
		) {
			$this->listDevices($io, $output);

			$this->askDiagnosticAction($io, $output);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//devices-module.cmd.diagnostics.actions.list.channels',
			)
			|| $whatToDo === '2'
		) {
			$this->listChannels($io, $output);

			$this->askDiagnosticAction($io, $output);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//devices-module.cmd.diagnostics.actions.list.properties',
			)
			|| $whatToDo === '3'
		) {
			$this->listProperties($io, $output);

			$this->askDiagnosticAction($io, $output);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listConnectors(Style\SymfonyStyle $io, Output\OutputInterface $output): void
	{
		$findConnectorsQuery = new Queries\Configuration\FindConnectors();

		$connectors = $this->connectorsConfigurationRepository->findAllBy($findConnectorsQuery);

		$progressBar = new Console\Helper\ProgressBar($output, count($connectors) * 2);
		$progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');

		$progressBar->start();

		usort(
			$connectors,
			static function (Documents\Connectors\Connector $a, Documents\Connectors\Connector $b) use ($progressBar): int {
				$progressBar->advance();

				return ($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier());
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
			(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.status'),
			(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$progressBar->advance();

			$findDevicesQuery = new Queries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

			$state = $this->connectorConnectionManager->getState($connector);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				$state->value,
				count($devices),
			]);
		}

		$progressBar->finish();

		$io->newLine(2);

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listDevices(Style\SymfonyStyle $io, Output\OutputInterface $output): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === false) {
			return;
		}

		$findDevicesQuery = new Queries\Configuration\FindDevices();

		if ($connector !== null) {
			$findDevicesQuery->forConnector($connector);
		}

		$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

		$progressBar = new Console\Helper\ProgressBar($output, count($devices) * 2);
		$progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');

		$progressBar->start();

		usort(
			$devices,
			function (Documents\Devices\Device $a, Documents\Devices\Device $b) use ($progressBar): int {
				$progressBar->advance();

				$connectorA = $this->connectorsConfigurationRepository->find($a->getConnector());
				assert($connectorA instanceof Documents\Connectors\Connector);
				$connectorB = $this->connectorsConfigurationRepository->find($b->getConnector());
				assert($connectorB instanceof Documents\Connectors\Connector);

				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				$connectorComparison = ($connectorA->getName() ?? $connectorA->getIdentifier()) <=> ($connectorB->getName() ?? $connectorB->getIdentifier());

				if ($connectorComparison !== 0) {
					return $connectorComparison;
				}

				return ($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier());
			},
		);

		$table = new Console\Helper\Table($io);
		if ($connector === null) {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.connector'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.status'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.channelsCnt'),
			]);
		} else {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.status'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.channelsCnt'),
			]);
		}

		foreach ($devices as $index => $device) {
			$progressBar->advance();

			$findChannelsQuery = new Queries\Configuration\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

			$state = $this->deviceConnectionManager->getState($device);

			if ($connector === null) {
				$deviceConnector = $this->connectorsConfigurationRepository->find($device->getConnector());
				assert($deviceConnector !== null);

				$table->addRow([
					$index + 1,
					$deviceConnector->getName() ?? $deviceConnector->getIdentifier(),
					$device->getName() ?? $device->getIdentifier(),
					$state->value,
					count($channels),
				]);
			} else {
				$table->addRow([
					$index + 1,
					$device->getName() ?? $device->getIdentifier(),
					$state->value,
					count($channels),
				]);
			}
		}

		$progressBar->finish();

		$io->newLine(2);

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function listChannels(Style\SymfonyStyle $io, Output\OutputInterface $output): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === false) {
			return;
		}

		$device = $this->askWhichDevice($io, $connector);

		if ($device === false) {
			return;
		}

		$findChannelsQuery = new Queries\Configuration\FindChannels();

		if ($device !== null) {
			$findChannelsQuery->forDevice($device);

		} elseif ($connector !== null) {
			$findDevicesQuery = new Queries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

			$findChannelsQuery->byDevices($devices);
		}

		$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

		$progressBar = new Console\Helper\ProgressBar($output, count($channels) * 2);
		$progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');

		$progressBar->start();

		usort(
			$channels,
			function (Documents\Channels\Channel $a, Documents\Channels\Channel $b) use ($progressBar): int {
				$progressBar->advance();

				$deviceA = $this->devicesConfigurationRepository->find($a->getDevice());
				assert($deviceA instanceof Documents\Devices\Device);
				$deviceB = $this->devicesConfigurationRepository->find($b->getDevice());
				assert($deviceB instanceof Documents\Devices\Device);

				$connectorA = $this->connectorsConfigurationRepository->find($deviceA->getConnector());
				assert($connectorA instanceof Documents\Connectors\Connector);
				$connectorB = $this->connectorsConfigurationRepository->find($deviceB->getConnector());
				assert($connectorB instanceof Documents\Connectors\Connector);

				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				$connectorComparison = ($connectorA->getName() ?? $connectorA->getIdentifier()) <=> ($connectorB->getName() ?? $connectorB->getIdentifier());

				if ($connectorComparison !== 0) {
					return $connectorComparison;
				}

				$deviceComparison = ($deviceA->getName() ?? $deviceA->getIdentifier()) <=> ($deviceB->getName() ?? $deviceB->getIdentifier());

				if ($deviceComparison !== 0) {
					return $deviceComparison;
				}

				return ($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier());
			},
		);

		$table = new Console\Helper\Table($io);
		if ($device === null) {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.connector'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.device'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.propertiesCnt'),
			]);
		} else {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.propertiesCnt'),
			]);
		}

		foreach ($channels as $index => $channel) {
			$progressBar->advance();

			$findPropertiesQuery = new Queries\Configuration\FindChannelProperties();
			$findPropertiesQuery->forChannel($channel);

			$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findPropertiesQuery);

			if ($device === null) {
				$channelDevice = $this->devicesConfigurationRepository->find($channel->getDevice());
				assert($channelDevice !== null);

				$channelConnector = $this->connectorsConfigurationRepository->find($channelDevice->getConnector());
				assert($channelConnector !== null);

				$table->addRow([
					$index + 1,
					$channelConnector->getName() ?? $channelConnector->getIdentifier(),
					$channelDevice->getName() ?? $channelDevice->getIdentifier(),
					$channel->getName() ?? $channel->getIdentifier(),
					count($properties),
				]);
			} else {
				$table->addRow([
					$index + 1,
					$channel->getName() ?? $channel->getIdentifier(),
					count($properties),
				]);
			}
		}

		$progressBar->finish();

		$io->newLine(2);

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listProperties(Style\SymfonyStyle $io, Output\OutputInterface $output): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//devices-module.cmd.diagnostics.questions.whatType'),
			[
				0 => (string) $this->translator->translate(
					'//devices-module.cmd.diagnostics.actions.properties.connectors',
				),
				1 => (string) $this->translator->translate(
					'//devices-module.cmd.diagnostics.actions.properties.devices',
				),
				2 => (string) $this->translator->translate(
					'//devices-module.cmd.diagnostics.actions.properties.channels',
				),
				3 => (string) $this->translator->translate('//devices-module.cmd.diagnostics.actions.nothing'),
			],
			3,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
		);

		$whatType = $io->askQuestion($question);

		if (
			$whatType === (string) $this->translator->translate(
				'//devices-module.cmd.diagnostics.actions.properties.connectors',
			)
			|| $whatType === '0'
		) {
			$this->listConnectorsProperties($io, $output);

		} elseif (
			$whatType === (string) $this->translator->translate(
				'//devices-module.cmd.diagnostics.actions.properties.devices',
			)
			|| $whatType === '1'
		) {
			$this->listDevicesProperties($io, $output);

		} elseif (
			$whatType === (string) $this->translator->translate(
				'//devices-module.cmd.diagnostics.actions.properties.channels',
			)
			|| $whatType === '2'
		) {
			$this->listChannelsProperties($io, $output);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listConnectorsProperties(Style\SymfonyStyle $io, Output\OutputInterface $output): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === false) {
			return;
		}

		$findPropertiesQuery = new Queries\Configuration\FindConnectorProperties();

		if ($connector !== null) {
			$findPropertiesQuery->forConnector($connector);
		}

		$properties = $this->connectorsPropertiesConfigurationRepository->findAllBy($findPropertiesQuery);

		$progressBar = new Console\Helper\ProgressBar($output, count($properties) * 2);
		$progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');

		$progressBar->start();

		usort(
			$properties,
			function (Documents\Connectors\Properties\Property $a, Documents\Connectors\Properties\Property $b) use ($progressBar): int {
				$progressBar->advance();

				$connectorA = $this->connectorsConfigurationRepository->find($a->getConnector());
				assert($connectorA instanceof Documents\Connectors\Connector);
				$connectorB = $this->connectorsConfigurationRepository->find($b->getConnector());
				assert($connectorB instanceof Documents\Connectors\Connector);

				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				$connectorComparison = ($connectorA->getName() ?? $connectorA->getIdentifier()) <=> ($connectorB->getName() ?? $connectorB->getIdentifier());

				if ($connectorComparison !== 0) {
					return $connectorComparison;
				}

				// If connectors are equal, compare by name or identifier
				return ($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier());
			},
		);

		$table = new Console\Helper\Table($io);
		if ($connector === null) {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.connector'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.type'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.defaultValue'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.value'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.isValid'),
			]);
		} else {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.type'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.defaultValue'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.value'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.isValid'),
			]);
		}

		foreach ($properties as $index => $property) {
			assert(
				$property instanceof Documents\Connectors\Properties\Dynamic
				|| $property instanceof Documents\Connectors\Properties\Variable,
			);

			$progressBar->advance();

			$state = false;

			if ($property instanceof Documents\Connectors\Properties\Dynamic) {
				$state = $this->connectorPropertiesStatesManager->readState($property);
			}

			if ($connector === null) {
				$propertyConnector = $this->connectorsConfigurationRepository->find($property->getConnector());
				assert($propertyConnector !== null);

				$table->addRow([
					$index + 1,
					$propertyConnector->getName() ?? $propertyConnector->getIdentifier(),
					$property->getName() ?? $property->getIdentifier(),
					$property instanceof Documents\Connectors\Properties\Dynamic ? 'dynamic' : 'variable',
					Utilities\Value::flattenValue($property->getDefault()),
					Utilities\Value::flattenValue(
						$state === false ? $property->getValue() : $state?->getGet()->getActualValue(),
					),
					$state !== false && $state !== null ? ($state->isValid() ? '<bg=green>Yes</>' : '<bg=red;fg=white>No</>') : 'N/A',
				]);
			} else {
				$table->addRow([
					$index + 1,
					$property->getName() ?? $property->getIdentifier(),
					$property instanceof Documents\Connectors\Properties\Dynamic ? 'dynamic' : 'variable',
					Utilities\Value::flattenValue($property->getDefault()),
					Utilities\Value::flattenValue(
						$state === false ? $property->getValue() : $state?->getGet()->getActualValue(),
					),
					$state !== false && $state !== null ? ($state->isValid() ? '<bg=green>Yes</>' : '<bg=red;fg=white>No</>') : 'N/A',
				]);
			}
		}

		$progressBar->finish();

		$io->newLine(2);

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listDevicesProperties(Style\SymfonyStyle $io, Output\OutputInterface $output): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === false) {
			return;
		}

		$device = $this->askWhichDevice($io, $connector);

		if ($device === false) {
			return;
		}

		$findPropertiesQuery = new Queries\Configuration\FindDeviceProperties();

		if ($device !== null) {
			$findPropertiesQuery->forDevice($device);

		} elseif ($connector !== null) {
			$findDevicesQuery = new Queries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

			$findPropertiesQuery->byDevices($devices);
		}

		$properties = $this->devicesPropertiesConfigurationRepository->findAllBy($findPropertiesQuery);

		$progressBar = new Console\Helper\ProgressBar($output, count($properties) * 2);
		$progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');

		$progressBar->start();

		usort(
			$properties,
			function (Documents\Devices\Properties\Property $a, Documents\Devices\Properties\Property $b) use ($progressBar): int {
				$progressBar->advance();

				$deviceA = $this->devicesConfigurationRepository->find($a->getDevice());
				assert($deviceA instanceof Documents\Devices\Device);
				$deviceB = $this->devicesConfigurationRepository->find($b->getDevice());
				assert($deviceB instanceof Documents\Devices\Device);

				$connectorA = $this->connectorsConfigurationRepository->find($deviceA->getConnector());
				assert($connectorA instanceof Documents\Connectors\Connector);
				$connectorB = $this->connectorsConfigurationRepository->find($deviceB->getConnector());
				assert($connectorB instanceof Documents\Connectors\Connector);

				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				$connectorComparison = ($connectorA->getName() ?? $connectorA->getIdentifier()) <=> ($connectorB->getName() ?? $connectorB->getIdentifier());

				if ($connectorComparison !== 0) {
					return $connectorComparison;
				}

				$deviceComparison = ($deviceA->getName() ?? $deviceA->getIdentifier()) <=> ($deviceB->getName() ?? $deviceB->getIdentifier());

				if ($deviceComparison !== 0) {
					return $deviceComparison;
				}

				return ($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier());
			},
		);

		$table = new Console\Helper\Table($io);
		if ($device === null) {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.connector'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.device'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.type'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.defaultValue'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.value'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.isValid'),
			]);
		} else {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.type'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.defaultValue'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.value'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.isValid'),
			]);
		}

		foreach ($properties as $index => $property) {
			assert(
				$property instanceof Documents\Devices\Properties\Dynamic
				|| $property instanceof Documents\Devices\Properties\Variable
				|| $property instanceof Documents\Devices\Properties\Mapped,
			);

			$progressBar->advance();

			$parent = $property instanceof Documents\Devices\Properties\Mapped
				? $this->devicesPropertiesConfigurationRepository->find($property->getParent())
				: null;

			$state = false;

			if (
				$property instanceof Documents\Devices\Properties\Dynamic
				|| (
					$property instanceof Documents\Devices\Properties\Mapped
					&& $parent instanceof Documents\Devices\Properties\Dynamic
				)
			) {
				$state = $this->devicePropertiesStatesManager->readState($property);
			}

			if ($device === null) {
				$propertyDevice = $this->devicesConfigurationRepository->find($property->getDevice());
				assert($propertyDevice !== null);

				$propertyConnector = $this->connectorsConfigurationRepository->find($propertyDevice->getConnector());
				assert($propertyConnector !== null);

				$table->addRow([
					$index + 1,
					$propertyConnector->getName() ?? $propertyConnector->getIdentifier(),
					$propertyDevice->getName() ?? $propertyDevice->getIdentifier(),
					$property->getName() ?? $property->getIdentifier(),
					$property instanceof Documents\Devices\Properties\Dynamic
						? 'dynamic'
						: ($property instanceof Documents\Devices\Properties\Variable ? 'variable' : 'mapped'),
					Utilities\Value::flattenValue($property->getDefault()),
					Utilities\Value::flattenValue(
						$state === false
							? ($property instanceof Documents\Devices\Properties\Variable ? $property->getValue() : 'N/A')
							: $state?->getGet()->getActualValue(),
					),
					$state !== false && $state !== null ? ($state->isValid() ? '<bg=green>Yes</>' : '<bg=red;fg=white>No</>') : 'N/A',
				]);
			} else {
				$table->addRow([
					$index + 1,
					$property->getName() ?? $property->getIdentifier(),
					$property instanceof Documents\Devices\Properties\Dynamic
						? 'dynamic'
						: ($property instanceof Documents\Devices\Properties\Variable ? 'variable' : 'mapped'),
					Utilities\Value::flattenValue($property->getDefault()),
					Utilities\Value::flattenValue(
						$state === false
							? ($property instanceof Documents\Devices\Properties\Variable ? $property->getValue() : 'N/A')
							: $state?->getGet()->getActualValue(),
					),
					$state !== false && $state !== null ? ($state->isValid() ? '<bg=green>Yes</>' : '<bg=red;fg=white>No</>') : 'N/A',
				]);
			}
		}

		$progressBar->finish();

		$io->newLine(2);

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listChannelsProperties(Style\SymfonyStyle $io, Output\OutputInterface $output): void
	{
			$connector = $this->askWhichConnector($io);

		if ($connector === false) {
			return;
		}

			$device = $this->askWhichDevice($io, $connector);

		if ($device === false) {
			return;
		}

			$channel = $device !== null ? $this->askWhichChannel($io, [$device]) : null;

		if ($channel === false) {
			return;
		}

			$properties = [];

		if ($channel !== null) {
			$findPropertiesQuery = new Queries\Configuration\FindChannelProperties();
			$findPropertiesQuery->forChannel($channel);

			$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findPropertiesQuery);

		} elseif ($device !== null) {
			$findChannelsQuery = new Queries\Configuration\FindChannels();
			$findChannelsQuery->forDevice($device);

			$deviceChannels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

			foreach ($deviceChannels as $deviceChannel) {
				$findPropertiesQuery = new Queries\Configuration\FindChannelProperties();
				$findPropertiesQuery->forChannel($deviceChannel);

				$channelProperties = $this->channelsPropertiesConfigurationRepository->findAllBy(
					$findPropertiesQuery,
				);

				$properties = array_merge($properties, $channelProperties);
			}
		} elseif ($connector !== null) {
			$findDevicesQuery = new Queries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

			$findChannelsQuery = new Queries\Configuration\FindChannels();
			$findChannelsQuery->byDevices($devices);

			$deviceChannels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

			foreach ($deviceChannels as $deviceChannel) {
				$findPropertiesQuery = new Queries\Configuration\FindChannelProperties();
				$findPropertiesQuery->forChannel($deviceChannel);

				$channelProperties = $this->channelsPropertiesConfigurationRepository->findAllBy(
					$findPropertiesQuery,
				);

				$properties = array_merge($properties, $channelProperties);
			}
		} else {
			$findChannelsQuery = new Queries\Configuration\FindChannels();

			$deviceChannels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

			foreach ($deviceChannels as $deviceChannel) {
				$findPropertiesQuery = new Queries\Configuration\FindChannelProperties();
				$findPropertiesQuery->forChannel($deviceChannel);

				$channelProperties = $this->channelsPropertiesConfigurationRepository->findAllBy(
					$findPropertiesQuery,
				);

				$properties = array_merge($properties, $channelProperties);
			}
		}

		if ($properties === []) {
			return;
		}

		$progressBar = new Console\Helper\ProgressBar($output, count($properties) * 2);
		$progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');

		$progressBar->start();

		usort(
			$properties,
			function (Documents\Channels\Properties\Property $a, Documents\Channels\Properties\Property $b) use ($progressBar): int {
				$progressBar->advance();

				$channelA = $this->channelsConfigurationRepository->find($a->getChannel());
				assert($channelA instanceof Documents\Channels\Channel);
				$channelB = $this->channelsConfigurationRepository->find($b->getChannel());
				assert($channelB instanceof Documents\Channels\Channel);

				$deviceA = $this->devicesConfigurationRepository->find($channelA->getDevice());
				assert($deviceA instanceof Documents\Devices\Device);
				$deviceB = $this->devicesConfigurationRepository->find($channelB->getDevice());
				assert($deviceB instanceof Documents\Devices\Device);

				$connectorA = $this->connectorsConfigurationRepository->find($deviceA->getConnector());
				assert($connectorA instanceof Documents\Connectors\Connector);
				$connectorB = $this->connectorsConfigurationRepository->find($deviceB->getConnector());
				assert($connectorB instanceof Documents\Connectors\Connector);

				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				$connectorComparison = ($connectorA->getName() ?? $connectorA->getIdentifier()) <=> ($connectorB->getName() ?? $connectorB->getIdentifier());

				if ($connectorComparison !== 0) {
					return $connectorComparison;
				}

				$deviceComparison = ($deviceA->getName() ?? $deviceA->getIdentifier()) <=> ($deviceB->getName() ?? $deviceB->getIdentifier());

				if ($deviceComparison !== 0) {
					return $deviceComparison;
				}

				$channelComparison = ($channelA->getName() ?? $channelA->getIdentifier()) <=> ($channelB->getName() ?? $channelB->getIdentifier());

				if ($channelComparison !== 0) {
					return $channelComparison;
				}

				return ($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier());
			},
		);

		$table = new Console\Helper\Table($io);
		if ($channel === null) {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.connector'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.device'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.channel'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.type'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.defaultValue'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.value'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.isValid'),
			]);
		} else {
			$table->setHeaders([
				'#',
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.name'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.type'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.defaultValue'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.value'),
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.data.isValid'),
			]);
		}

		foreach ($properties as $index => $property) {
			assert(
				$property instanceof Documents\Channels\Properties\Dynamic
				|| $property instanceof Documents\Channels\Properties\Variable
				|| $property instanceof Documents\Channels\Properties\Mapped,
			);

			$progressBar->advance();

			$parent = $property instanceof Documents\Channels\Properties\Mapped
				? $this->channelsPropertiesConfigurationRepository->find($property->getParent())
				: null;

			$state = false;

			if (
				$property instanceof Documents\Channels\Properties\Dynamic
				|| (
					$property instanceof Documents\Channels\Properties\Mapped
					&& $parent instanceof Documents\Channels\Properties\Dynamic
				)
			) {
				$state = $this->channelPropertiesStatesManager->readState($property);
			}

			if ($channel === null) {
				$propertyChannel = $this->channelsConfigurationRepository->find($property->getChannel());
				assert($propertyChannel !== null);

				$propertyDevice = $this->devicesConfigurationRepository->find($propertyChannel->getDevice());
				assert($propertyDevice !== null);

				$propertyConnector = $this->connectorsConfigurationRepository->find(
					$propertyDevice->getConnector(),
				);
				assert($propertyConnector !== null);

				$table->addRow([
					$index + 1,
					$propertyConnector->getName() ?? $propertyConnector->getIdentifier(),
					$propertyDevice->getName() ?? $propertyDevice->getIdentifier(),
					$propertyChannel->getName() ?? $propertyChannel->getIdentifier(),
					$property->getName() ?? $property->getIdentifier(),
					$property instanceof Documents\Channels\Properties\Dynamic
						? 'dynamic'
						: ($property instanceof Documents\Channels\Properties\Variable ? 'variable' : 'mapped'),
					Utilities\Value::flattenValue($property->getDefault()),
					Utilities\Value::flattenValue(
						$state === false
							? ($property instanceof Documents\Channels\Properties\Variable ? $property->getValue() : 'N/A')
							: $state?->getGet()->getActualValue(),
					),
					$state !== false && $state !== null ? ($state->isValid() ? '<bg=green>Yes</>' : '<bg=red;fg=white>No</>') : 'N/A',
				]);
			} else {
				$table->addRow([
					$index + 1,
					$property->getName() ?? $property->getIdentifier(),
					$property instanceof Documents\Channels\Properties\Dynamic
						? 'dynamic'
						: ($property instanceof Documents\Channels\Properties\Variable ? 'variable' : 'mapped'),
					Utilities\Value::flattenValue($property->getDefault()),
					Utilities\Value::flattenValue(
						$state === false
							? ($property instanceof Documents\Channels\Properties\Variable ? $property->getValue() : 'N/A')
							: $state?->getGet()->getActualValue(),
					),
					$state !== false && $state !== null ? ($state->isValid() ? '<bg=green>Yes</>' : '<bg=red;fg=white>No</>') : 'N/A',
				]);
			}
		}

		$progressBar->finish();

		$io->newLine(2);

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Documents\Connectors\Connector|false|null
	{
		$systemConnectors = [];

		$findConnectorsQuery = new Queries\Configuration\FindConnectors();

		foreach ($this->connectorsConfigurationRepository->findAllBy($findConnectorsQuery) as $connector) {
			$systemConnectors[] = $connector;
		}

		if (count($systemConnectors) === 0) {
			return false;
		}

		usort(
			$systemConnectors,
			static fn (Documents\Connectors\Connector $a, Documents\Connectors\Connector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$connectors = [];

		foreach ($systemConnectors as $systemConnector) {
			$connectors[$systemConnector->getIdentifier()] = $systemConnector->getName() ?? $systemConnector->getIdentifier();
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//devices-module.cmd.diagnostics.questions.select.connector'),
			array_merge(
				array_values($connectors),
				[
					'all' => (string) $this->translator->translate(
						'//devices-module.cmd.diagnostics.actions.select.allConnectors',
					),
				],
			),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connectors): Documents\Connectors\Connector|null {
				if ($answer === null) {
					throw new DevicesExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//devices-module.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (
					$answer === 'all'
					|| $answer === (string) $this->translator->translate(
						'//devices-module.cmd.diagnostics.actions.select.allConnectors',
					)
				) {
					return null;
				}

				if (array_key_exists($answer, array_values($connectors))) {
					$answer = array_values($connectors)[$answer];
				}

				$identifier = array_search($answer, $connectors, true);

				if ($identifier !== false) {
					$findConnectorQuery = new Queries\Configuration\FindConnectors();
					$findConnectorQuery->byIdentifier($identifier);

					$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

					if ($connector !== null) {
						return $connector;
					}
				}

				throw new DevicesExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$connector = $io->askQuestion($question);
		assert($connector instanceof Documents\Connectors\Connector || $connector === null);

		if ($connector !== null && !$connector->isEnabled()) {
			$io->warning(
				(string) $this->translator->translate('//devices-module.cmd.diagnostics.messages.connectorDisabled'),
			);

			return false;
		}

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Documents\Connectors\Connector|null $connector = null,
	): Documents\Devices\Device|false|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Configuration\FindDevices();

		if ($connector !== null) {
			$findDevicesQuery->forConnector($connector);
		}

		$systemDevices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);
		usort(
			$systemDevices,
			static fn (Documents\Devices\Device $a, Documents\Devices\Device $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($systemDevices as $device) {
			$devices[$device->getIdentifier()] = $device->getName() ?? $device->getIdentifier();
		}

		if (count($devices) === 0) {
			return false;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//devices-module.cmd.diagnostics.questions.select.device'),
			array_merge(
				array_values($devices),
				[
					'all' => (string) $this->translator->translate(
						'//devices-module.cmd.diagnostics.actions.select.allDevices',
					),
				],
			),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $devices): Documents\Devices\Device|null {
				if ($answer === null) {
					throw new DevicesExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//devices-module.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (
					$answer === 'all'
					|| $answer === (string) $this->translator->translate(
						'//devices-module.cmd.diagnostics.actions.select.allDevices',
					)
				) {
					return null;
				}

				if (array_key_exists($answer, array_values($devices))) {
					$answer = array_values($devices)[$answer];
				}

				$identifier = array_search($answer, $devices, true);

				if ($identifier !== false) {
					$findDeviceQuery = new Queries\Configuration\FindDevices();
					$findDeviceQuery->byIdentifier($identifier);

					if ($connector !== null) {
						$findDeviceQuery->forConnector($connector);
					}

					$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

					if ($device !== null) {
						return $device;
					}
				}

				throw new DevicesExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Documents\Devices\Device || $device === null);

		return $device;
	}

	/**
	 * @param array<Documents\Devices\Device> $devices
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichChannel(
		Style\SymfonyStyle $io,
		array $devices = [],
	): Documents\Channels\Channel|false|null
	{
		$channels = [];

		$findChannelsQuery = new Queries\Configuration\FindChannels();

		if ($devices !== []) {
			$findChannelsQuery->byDevices($devices);
		}

		$systemChannels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);
		usort(
			$systemChannels,
			static fn (Documents\Channels\Channel $a, Documents\Channels\Channel $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($systemChannels as $channel) {
			$channels[$channel->getIdentifier()] = $channel->getName() ?? $channel->getIdentifier();
		}

		if (count($channels) === 0) {
			return false;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//devices-module.cmd.diagnostics.questions.select.channel'),
			array_merge(
				array_values($channels),
				[
					'all' => (string) $this->translator->translate(
						'//devices-module.cmd.diagnostics.actions.select.allChannels',
					),
				],
			),
			count($channels) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($devices, $channels): Documents\Channels\Channel|null {
				if ($answer === null) {
					throw new DevicesExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//devices-module.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (
					$answer === 'all'
					|| $answer === (string) $this->translator->translate(
						'//devices-module.cmd.diagnostics.actions.select.allChannels',
					)
				) {
					return null;
				}

				if (array_key_exists($answer, array_values($channels))) {
					$answer = array_values($channels)[$answer];
				}

				$identifier = array_search($answer, $channels, true);

				if ($identifier !== false) {
					$findChannelQuery = new Queries\Configuration\FindChannels();
					$findChannelQuery->byIdentifier($identifier);

					if ($devices !== []) {
						$findChannelQuery->byDevices($devices);
					}

					$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

					if ($channel !== null) {
						return $channel;
					}
				}

				throw new DevicesExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$channel = $io->askQuestion($question);
		assert($channel instanceof Documents\Channels\Channel || $channel === null);

		return $channel;
	}

}
