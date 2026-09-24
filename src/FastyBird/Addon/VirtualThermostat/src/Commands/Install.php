<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Commands;

use Doctrine\DBAL;
use Exception;
use FastyBird\Addon\VirtualThermostat;
use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Exceptions as VirtualThermostatExceptions;
use FastyBird\Addon\VirtualThermostat\Queries;
use FastyBird\Addon\VirtualThermostat\Types as VirtualThermostatTypes;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Connector\Virtual\Exceptions as VirtualExceptions;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;
use FastyBird\Connector\Virtual\Types as VirtualTypes;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as DoctrineCrudExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Formats;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette\Localization;
use Nette\Utils;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use TypeError;
use ValueError;
use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_search;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function explode;
use function floatval;
use function implode;
use function in_array;
use function is_array;
use function is_float;
use function is_numeric;
use function sprintf;
use function str_starts_with;
use function strval;
use function usort;

/**
 * Connector thermostat devices management command
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:virtual-thermostat-addon:install';

	public function __construct(
		private readonly VirtualThermostat\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly ToolsHelpers\Database $databaseHelper,
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
			->setDescription('Thermostat device installer');
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 * @throws VirtualExceptions\InvalidArgument
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title((string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.title'));

		$io->note((string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.subtitle'));

		$this->askInstallAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createDevice(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.noConnectors'),
			);

			return;
		}

		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.provide.device.identifier',
			),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class) !== null
				) {
					throw new VirtualThermostatExceptions\Runtime(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.install.messages.identifier.device.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'virtual-thermostat-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.identifier.device.missing',
				),
			);

			return;
		}

		$name = $this->askDeviceName($io);

		$setPresets = [];

		$hvacModeProperty = $targetTempProperty = $presetModeProperty = null;

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Devices\Device::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\Devices\Device);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => VirtualTypes\DevicePropertyIdentifier::MODEL->value,
				'device' => $device,
				'dataType' => ValuesTypes\DataType::STRING,
				'value' => Entities\Devices\Device::TYPE,
			]));

			$configurationChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Configuration::class,
				'device' => $device,
				'identifier' => VirtualThermostatTypes\ChannelIdentifier::CONFIGURATION->value,
			]));
			assert($configurationChannel instanceof Entities\Channels\Configuration);

			$stateChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\State::class,
				'device' => $device,
				'identifier' => VirtualThermostatTypes\ChannelIdentifier::STATE->value,
			]));
			assert($stateChannel instanceof Entities\Channels\State);

			$modes = $this->askThermostatModes($io);

			$unit = $this->askThermostatUnits($io);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Variable::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::UNIT->value,
					'channel' => $configurationChannel,
					'dataType' => ValuesTypes\DataType::ENUM,
					'format' => [VirtualThermostatTypes\Unit::CELSIUS->value, VirtualThermostatTypes\Unit::FAHRENHEIT->value],
					'value' => $unit->value,
				]),
			);

			$hvacModeProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_MODE->value,
					'channel' => $stateChannel,
					'dataType' => ValuesTypes\DataType::ENUM,
					'format' => array_merge(
						[VirtualThermostatTypes\HvacMode::OFF->value],
						array_map(static fn (VirtualThermostatTypes\HvacMode $mode): string => $mode->value, $modes),
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => true,
					'queryable' => true,
					'default' => VirtualThermostatTypes\HvacMode::OFF->value,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_STATE->value,
					'channel' => $stateChannel,
					'dataType' => ValuesTypes\DataType::ENUM,
					'format' => array_merge(
						[VirtualThermostatTypes\HvacState::OFF->value],
						array_filter(
							array_map(static fn (VirtualThermostatTypes\HvacMode $mode): string|null => match ($mode) {
								VirtualThermostatTypes\HvacMode::HEAT => VirtualThermostatTypes\HvacState::HEATING->value,
								VirtualThermostatTypes\HvacMode::COOL => VirtualThermostatTypes\HvacState::COOLING->value,
								default => null,
							}, $modes),
							static fn (string|null $state): bool => $state !== null,
						),
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => false,
					'queryable' => true,
					'default' => VirtualThermostatTypes\HvacState::OFF->value,
				]),
			);

			$heaterActors = $coolerActors = $openingSensors = $roomTempSensors = $floorTempSensors = $roomHumSensors = [];

			$actorsChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Actors::class,
				'device' => $device,
				'identifier' => VirtualThermostatTypes\ChannelIdentifier::ACTORS->value,
			]));
			assert($actorsChannel instanceof Entities\Channels\Actors);

			$sensorsChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Sensors::class,
				'device' => $device,
				'identifier' => VirtualThermostatTypes\ChannelIdentifier::SENSORS->value,
			]));
			assert($sensorsChannel instanceof Entities\Channels\Sensors);

			if (in_array(VirtualThermostatTypes\HvacMode::HEAT, $modes, true)) {
				$io->info(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.messages.configureHeaterActors',
					),
				);

				do {
					$heater = $this->askActor(
						$io,
						array_map(
							static fn (DevicesEntities\Channels\Properties\Dynamic $actor): string => $actor->getId()->toString(),
							$heaterActors,
						),
						[
							ValuesTypes\DataType::BOOLEAN,
							ValuesTypes\DataType::SWITCH,
						],
					);

					if ($heater !== false) {
						$heaterActors[] = $heater;

						$this->createOrUpdateProperty(
							DevicesEntities\Channels\Properties\Mapped::class,
							Utils\ArrayHash::from([
								'parent' => $heater,
								'entity' => DevicesEntities\Channels\Properties\Mapped::class,
								'identifier' => $this->findChannelPropertyIdentifier(
									$actorsChannel,
									VirtualThermostatTypes\ChannelPropertyIdentifier::HEATER_ACTOR,
								),
								'channel' => $actorsChannel,
								'dataType' => ValuesTypes\DataType::BOOLEAN,
								'format' => null,
								'unit' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'settable' => true,
								'queryable' => $heater->isQueryable(),
							]),
						);

						$question = new Console\Question\ConfirmationQuestion(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.install.questions.addAnotherHeater',
							),
							false,
						);

						$continue = (bool) $io->askQuestion($question);
					} else {
						$continue = false;
					}
				} while ($continue);
			}

			if (in_array(VirtualThermostatTypes\HvacMode::COOL, $modes, true)) {
				$io->info(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.messages.configureCoolerActors',
					),
				);

				do {
					$cooler = $this->askActor(
						$io,
						array_map(
							static fn (DevicesEntities\Channels\Properties\Dynamic $actor): string => $actor->getId()->toString(),
							$coolerActors,
						),
						[
							ValuesTypes\DataType::BOOLEAN,
							ValuesTypes\DataType::SWITCH,
						],
					);

					if ($cooler !== false) {
						$coolerActors[] = $cooler;

						$this->createOrUpdateProperty(
							DevicesEntities\Channels\Properties\Mapped::class,
							Utils\ArrayHash::from([
								'parent' => $cooler,
								'entity' => DevicesEntities\Channels\Properties\Mapped::class,
								'identifier' => $this->findChannelPropertyIdentifier(
									$actorsChannel,
									VirtualThermostatTypes\ChannelPropertyIdentifier::COOLER_ACTOR,
								),
								'channel' => $actorsChannel,
								'dataType' => ValuesTypes\DataType::BOOLEAN,
								'format' => null,
								'unit' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'settable' => true,
								'queryable' => $cooler->isQueryable(),
							]),
						);

						$question = new Console\Question\ConfirmationQuestion(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.install.questions.addAnotherCooler',
							),
							false,
						);

						$continue = (bool) $io->askQuestion($question);
					} else {
						$continue = false;
					}
				} while ($continue);
			}

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.questions.useOpeningSensors',
				),
				false,
			);

			$useOpeningSensors = (bool) $io->askQuestion($question);

			if ($useOpeningSensors) {
				do {
					$opening = $this->askSensor(
						$io,
						array_map(
							static fn (DevicesEntities\Channels\Properties\Dynamic $sensor): string => $sensor->getId()->toString(),
							$openingSensors,
						),
						[
							ValuesTypes\DataType::BOOLEAN,
						],
					);

					if ($opening !== false) {
						$openingSensors[] = $opening;

						$this->createOrUpdateProperty(
							DevicesEntities\Channels\Properties\Mapped::class,
							Utils\ArrayHash::from([
								'parent' => $opening,
								'entity' => DevicesEntities\Channels\Properties\Mapped::class,
								'identifier' => $this->findChannelPropertyIdentifier(
									$sensorsChannel,
									VirtualThermostatTypes\ChannelPropertyIdentifier::OPENING_SENSOR,
								),
								'channel' => $sensorsChannel,
								'dataType' => ValuesTypes\DataType::BOOLEAN,
								'format' => null,
								'unit' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'settable' => false,
								'queryable' => $opening->isQueryable(),
							]),
						);

						$question = new Console\Question\ConfirmationQuestion(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.install.questions.addAnotherOpeningSensor',
							),
							false,
						);

						$continue = (bool) $io->askQuestion($question);
					} else {
						$continue = false;
					}
				} while ($continue);
			}

			$io->info(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.configureRoomTemperatureSensor',
				),
			);

			do {
				$sensor = $this->askSensor(
					$io,
					array_map(
						static fn (DevicesEntities\Channels\Properties\Dynamic $sensor): string => $sensor->getId()->toString(),
						$roomTempSensors,
					),
					[
						ValuesTypes\DataType::FLOAT,
						ValuesTypes\DataType::CHAR,
						ValuesTypes\DataType::UCHAR,
						ValuesTypes\DataType::SHORT,
						ValuesTypes\DataType::USHORT,
						ValuesTypes\DataType::INT,
						ValuesTypes\DataType::UINT,
					],
				);

				if ($sensor !== false) {
					$roomTempSensors[] = $sensor;

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Mapped::class,
						Utils\ArrayHash::from([
							'parent' => $sensor,
							'entity' => DevicesEntities\Channels\Properties\Mapped::class,
							'identifier' => $this->findChannelPropertyIdentifier(
								$sensorsChannel,
								VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR,
							),
							'channel' => $sensorsChannel,
							'dataType' => ValuesTypes\DataType::FLOAT,
							'format' => null,
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'settable' => false,
							'queryable' => $sensor->isQueryable(),
						]),
					);

					$question = new Console\Question\ConfirmationQuestion(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.install.questions.addAnotherRoomTemperatureSensor',
						),
						false,
					);

					$continue = (bool) $io->askQuestion($question);
				} else {
					$continue = false;
				}
			} while ($continue);

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.questions.useFloorTemperatureSensors',
				),
				false,
			);

			$useFloorTempSensor = (bool) $io->askQuestion($question);

			if ($useFloorTempSensor) {
				do {
					$sensor = $this->askSensor(
						$io,
						array_map(
							static fn (DevicesEntities\Channels\Properties\Dynamic $sensor): string => $sensor->getId()->toString(),
							$floorTempSensors,
						),
						[
							ValuesTypes\DataType::FLOAT,
							ValuesTypes\DataType::CHAR,
							ValuesTypes\DataType::UCHAR,
							ValuesTypes\DataType::SHORT,
							ValuesTypes\DataType::USHORT,
							ValuesTypes\DataType::INT,
							ValuesTypes\DataType::UINT,
						],
					);

					if ($sensor !== false) {
						$floorTempSensors[] = $sensor;

						$this->createOrUpdateProperty(
							DevicesEntities\Channels\Properties\Mapped::class,
							Utils\ArrayHash::from([
								'parent' => $sensor,
								'entity' => DevicesEntities\Channels\Properties\Mapped::class,
								'identifier' => $this->findChannelPropertyIdentifier(
									$sensorsChannel,
									VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR,
								),
								'channel' => $sensorsChannel,
								'dataType' => ValuesTypes\DataType::FLOAT,
								'format' => null,
								'unit' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'settable' => false,
								'queryable' => $sensor->isQueryable(),
							]),
						);

						$question = new Console\Question\ConfirmationQuestion(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.install.questions.addAnotherFloorTemperatureSensor',
							),
							false,
						);

						$continue = (bool) $io->askQuestion($question);
					} else {
						$continue = false;
					}
				} while ($continue);
			}

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.questions.useRoomHumiditySensors',
				),
				false,
			);

			$useRoomHumSensor = (bool) $io->askQuestion($question);

			if ($useRoomHumSensor) {
				do {
					$sensor = $this->askSensor(
						$io,
						array_map(
							static fn (DevicesEntities\Channels\Properties\Dynamic $sensor): string => $sensor->getId()->toString(),
							$roomHumSensors,
						),
						[
							ValuesTypes\DataType::UCHAR,
							ValuesTypes\DataType::USHORT,
							ValuesTypes\DataType::UINT,
							ValuesTypes\DataType::FLOAT,
						],
					);

					if ($sensor !== false) {
						$roomHumSensors[] = $sensor;

						$this->createOrUpdateProperty(
							DevicesEntities\Channels\Properties\Mapped::class,
							Utils\ArrayHash::from([
								'parent' => $sensor,
								'entity' => DevicesEntities\Channels\Properties\Mapped::class,
								'identifier' => $this->findChannelPropertyIdentifier(
									$sensorsChannel,
									VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR,
								),
								'channel' => $sensorsChannel,
								'dataType' => ValuesTypes\DataType::UINT,
								'format' => null,
								'unit' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'settable' => false,
								'queryable' => $sensor->isQueryable(),
							]),
						);

						$question = new Console\Question\ConfirmationQuestion(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.install.questions.addAnotherRoomHumiditySensor',
							),
							false,
						);

						$continue = (bool) $io->askQuestion($question);
					} else {
						$continue = false;
					}
				} while ($continue);
			}

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_TEMPERATURE->value,
					'channel' => $stateChannel,
					'dataType' => ValuesTypes\DataType::FLOAT,
					'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_ROOM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Device::PRECISION,
					'settable' => false,
					'queryable' => true,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Variable::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::LOW_TARGET_TEMPERATURE_TOLERANCE->value,
					'channel' => $configurationChannel,
					'dataType' => ValuesTypes\DataType::FLOAT,
					'format' => null,
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Device::PRECISION,
					'value' => Entities\Devices\Device::COLD_TOLERANCE,
					'default' => Entities\Devices\Device::COLD_TOLERANCE,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Variable::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::HIGH_TARGET_TEMPERATURE_TOLERANCE->value,
					'channel' => $configurationChannel,
					'dataType' => ValuesTypes\DataType::FLOAT,
					'format' => null,
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Device::PRECISION,
					'value' => Entities\Devices\Device::HOT_TOLERANCE,
					'default' => Entities\Devices\Device::HOT_TOLERANCE,
				]),
			);

			if ($useOpeningSensors) {
				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_OPENINGS_STATE->value,
						'channel' => $stateChannel,
						'dataType' => ValuesTypes\DataType::ENUM,
						'format' => [
							VirtualThermostatTypes\OpeningStatePayload::OPENED->value,
							VirtualThermostatTypes\OpeningStatePayload::CLOSED->value,
						],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'settable' => false,
						'queryable' => true,
					]),
				);
			}

			if ($useFloorTempSensor) {
				$maxFloorTemp = $this->askMaxFloorTemperature($io, $unit);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE->value,
						'channel' => $configurationChannel,
						'dataType' => ValuesTypes\DataType::FLOAT,
						'format' => [0, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Device::PRECISION,
						'value' => $maxFloorTemp,
						'default' => Entities\Devices\Device::MAXIMUM_SET_FLOOR_TEMPERATURE,
					]),
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_FLOOR_TEMPERATURE->value,
						'channel' => $stateChannel,
						'dataType' => ValuesTypes\DataType::FLOAT,
						'format' => [0, Entities\Devices\Device::MAXIMUM_FLOOR_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Device::PRECISION,
						'settable' => false,
						'queryable' => true,
					]),
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_OVERHEATING->value,
						'channel' => $stateChannel,
						'dataType' => ValuesTypes\DataType::BOOLEAN,
						'format' => null,
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'settable' => false,
						'queryable' => true,
					]),
				);
			}

			if ($useRoomHumSensor) {
				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_HUMIDITY->value,
						'channel' => $stateChannel,
						'dataType' => ValuesTypes\DataType::UCHAR,
						'format' => [0, 100],
						'unit' => '%',
						'invalid' => null,
						'scale' => null,
						'step' => 1,
						'settable' => false,
						'queryable' => true,
					]),
				);
			}

			$presets = $this->askPresets($io);

			$presetModeProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::PRESET_MODE->value,
					'channel' => $stateChannel,
					'dataType' => ValuesTypes\DataType::ENUM,
					'format' => array_map(
						static fn (VirtualThermostatTypes\Preset $preset): string => $preset->value,
						$presets,
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => false,
					'queryable' => true,
					'default' => VirtualThermostatTypes\Preset::MANUAL->value,
				]),
			);

			foreach ($presets as $preset) {
				$io->info(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.messages.preset.' . $preset->value,
					),
				);

				$presetChannel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Preset::class,
					'device' => $device,
					'identifier' => 'preset_' . $preset->value,
				]));
				assert($presetChannel instanceof Entities\Channels\Preset);

				$setPresets[$preset->value] = [
					'value' => $this->askTargetTemperature($io, $preset, $unit),
					'property' => $this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Dynamic::class,
						Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
							'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_TEMPERATURE->value,
							'channel' => $presetChannel,
							'dataType' => ValuesTypes\DataType::FLOAT,
							'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => Entities\Devices\Device::PRECISION,
							'settable' => true,
							'queryable' => true,
							'default' => Entities\Devices\Device::TARGET_TEMPERATURE,
						]),
					),
				];

				if (in_array(VirtualThermostatTypes\HvacMode::AUTO, $modes, true)) {
					$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
						$io,
						$preset,
						$unit,
					);

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Variable::class,
						Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Variable::class,
							'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE->value,
							'channel' => $presetChannel,
							'dataType' => ValuesTypes\DataType::FLOAT,
							'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => Entities\Devices\Device::PRECISION,
							'default' => null,
							'value' => $heatingThresholdTemp,
						]),
					);

					$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
						$io,
						$preset,
						$unit,
					);

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Variable::class,
						Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Variable::class,
							'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE->value,
							'channel' => $presetChannel,
							'dataType' => ValuesTypes\DataType::FLOAT,
							'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => Entities\Devices\Device::PRECISION,
							'default' => null,
							'value' => $coolingThresholdTemp,
						]),
					);
				}
			}

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.create.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.create.device.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$hvacModeState = $this->channelsPropertiesConfigurationRepository->find(
			$hvacModeProperty->getId(),
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);
		assert($hvacModeState !== null);

		$this->channelPropertiesStatesManager->set(
			$hvacModeState,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => VirtualThermostatTypes\HvacMode::OFF->value,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
			]),
			Sources\Addon::VIRTUAL_THERMOSTAT,
		);

		$presetModeState = $this->channelsPropertiesConfigurationRepository->find(
			$presetModeProperty->getId(),
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);
		assert($presetModeState !== null);

		$this->channelPropertiesStatesManager->set(
			$presetModeState,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => VirtualThermostatTypes\Preset::MANUAL->value,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
			]),
			Sources\Addon::VIRTUAL_THERMOSTAT,
		);

		foreach ($setPresets as $data) {
			$presetConfiguration = $this->channelsPropertiesConfigurationRepository->find(
				$data['property']->getId(),
				DevicesDocuments\Channels\Properties\Dynamic::class,
			);
			assert($presetConfiguration !== null);

			$this->channelPropertiesStatesManager->set(
				$presetConfiguration,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $data['value'],
					DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
				]),
				Sources\Addon::VIRTUAL_THERMOSTAT,
			);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 * @throws VirtualExceptions\InvalidArgument
	 */
	private function editDevice(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichDevice($io);

		if ($device === null) {
			$io->warning(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.messages.noDevices'),
			);

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.questions.create.device'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createDevice($io);
			}

			return;
		}

		$findDevicePropertyQuery = new VirtualQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(VirtualTypes\DevicePropertyIdentifier::MODEL);

		$deviceModelProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findChannelQuery = new Queries\Entities\FindConfigurationChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(VirtualThermostatTypes\ChannelIdentifier::CONFIGURATION);

		$configurationChannel = $this->channelsRepository->findOneBy(
			$findChannelQuery,
			Entities\Channels\Configuration::class,
		);

		$findChannelQuery = new Queries\Entities\FindStateChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(VirtualThermostatTypes\ChannelIdentifier::STATE);

		$stateChannel = $this->channelsRepository->findOneBy(
			$findChannelQuery,
			Entities\Channels\State::class,
		);

		$unitProperty = $maxFloorTempProperty = null;

		if ($configurationChannel !== null) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($configurationChannel);
			$findChannelPropertyQuery->byIdentifier(VirtualThermostatTypes\ChannelPropertyIdentifier::UNIT->value);

			$unitProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($configurationChannel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE->value,
			);

			$maxFloorTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);
		}

		$hvacModeProperty = $hvacStateProperty = $presetModeProperty = null;
		$currentRoomTempProperty = $currentFloorTempProperty = $floorOverheatingProperty = null;
		$currentRoomHumProperty = null;
		$currentOpeningsStateProperty = null;

		if ($stateChannel !== null) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($stateChannel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::PRESET_MODE->value,
			);

			$presetModeProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($stateChannel);
			$findChannelPropertyQuery->byIdentifier(VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_MODE->value);

			$hvacModeProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($stateChannel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_STATE->value,
			);

			$hvacStateProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($stateChannel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_TEMPERATURE->value,
			);

			$currentRoomTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($stateChannel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_FLOOR_TEMPERATURE->value,
			);

			$currentFloorTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($stateChannel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_OVERHEATING->value,
			);

			$floorOverheatingProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($stateChannel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_HUMIDITY->value,
			);

			$currentRoomHumProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($stateChannel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_OPENINGS_STATE->value,
			);

			$currentOpeningsStateProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);
		}

		$name = $this->askDeviceName($io, $device);

		$modes = $this->askThermostatModes(
			$io,
			$hvacModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic ? $hvacModeProperty : null,
		);

		$unit = $this->askThermostatUnits(
			$io,
			$unitProperty instanceof DevicesEntities\Channels\Properties\Variable ? $unitProperty : null,
		);

		$maxFloorTemp = null;

		if ($device->hasFloorTemperatureSensors()) {
			$maxFloorTemp = $this->askMaxFloorTemperature($io, $unit, $device);
		}

		$presets = $this->askPresets(
			$io,
			$presetModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic ? $presetModeProperty : null,
		);

		$setPresets = [];

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));
			assert($device instanceof Entities\Devices\Device);

			if (
				$deviceModelProperty !== null
				&& !$deviceModelProperty instanceof DevicesEntities\Devices\Properties\Variable
			) {
				$this->devicesPropertiesManager->delete($deviceModelProperty);

				$deviceModelProperty = null;
			}

			if ($deviceModelProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => VirtualTypes\DevicePropertyIdentifier::MODEL->value,
					'device' => $device,
					'dataType' => ValuesTypes\DataType::STRING,
					'value' => Entities\Devices\Device::TYPE,
				]));
			} else {
				$this->devicesPropertiesManager->update($deviceModelProperty, Utils\ArrayHash::from([
					'dataType' => ValuesTypes\DataType::STRING,
					'format' => null,
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'default' => null,
					'value' => Entities\Devices\Device::TYPE,
				]));
			}

			if ($configurationChannel === null) {
				$configurationChannel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Configuration::class,
					'device' => $device,
					'identifier' => VirtualThermostatTypes\ChannelIdentifier::CONFIGURATION->value,
				]));
				assert($configurationChannel instanceof Entities\Channels\Configuration);
			}

			if ($stateChannel === null) {
				$stateChannel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\State::class,
					'device' => $device,
					'identifier' => VirtualThermostatTypes\ChannelIdentifier::STATE->value,
				]));
				assert($stateChannel instanceof Entities\Channels\Configuration);
			}

			$hvacModeProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_MODE->value,
					'channel' => $stateChannel,
					'dataType' => ValuesTypes\DataType::ENUM,
					'format' => array_merge(
						[VirtualThermostatTypes\HvacMode::OFF->value],
						array_map(static fn (VirtualThermostatTypes\HvacMode $mode): string => $mode->value, $modes),
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => true,
					'queryable' => true,
					'default' => VirtualThermostatTypes\HvacMode::OFF->value,
				]),
				$hvacModeProperty,
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_STATE->value,
					'channel' => $stateChannel,
					'dataType' => ValuesTypes\DataType::ENUM,
					'format' => array_merge(
						[VirtualThermostatTypes\HvacState::OFF->value],
						array_filter(
							array_map(static fn (VirtualThermostatTypes\HvacMode $mode): string|null => match ($mode) {
								VirtualThermostatTypes\HvacMode::HEAT => VirtualThermostatTypes\HvacState::HEATING->value,
								VirtualThermostatTypes\HvacMode::COOL => VirtualThermostatTypes\HvacState::COOLING->value,
								default => null,
							}, $modes),
							static fn (string|null $state): bool => $state !== null,
						),
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => false,
					'queryable' => true,
					'default' => VirtualThermostatTypes\HvacState::OFF->value,
				]),
				$hvacStateProperty,
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_TEMPERATURE->value,
					'channel' => $stateChannel,
					'dataType' => ValuesTypes\DataType::FLOAT,
					'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_ROOM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Device::PRECISION,
					'settable' => false,
					'queryable' => true,
				]),
				$currentRoomTempProperty,
			);

			if ($device->hasOpeningsSensors()) {
				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_OPENINGS_STATE->value,
						'channel' => $stateChannel,
						'dataType' => ValuesTypes\DataType::ENUM,
						'format' => [
							VirtualThermostatTypes\OpeningStatePayload::OPENED->value,
							VirtualThermostatTypes\OpeningStatePayload::CLOSED->value,
						],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'settable' => false,
						'queryable' => true,
					]),
					$currentOpeningsStateProperty,
				);
			} else {
				if ($currentOpeningsStateProperty !== null) {
					$this->channelsPropertiesManager->delete($currentOpeningsStateProperty);
				}
			}

			if ($device->hasFloorTemperatureSensors()) {
				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE->value,
						'channel' => $configurationChannel,
						'dataType' => ValuesTypes\DataType::FLOAT,
						'format' => [0, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Device::PRECISION,
						'value' => $maxFloorTemp,
						'default' => Entities\Devices\Device::MAXIMUM_SET_FLOOR_TEMPERATURE,
					]),
					$maxFloorTempProperty,
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_FLOOR_TEMPERATURE->value,
						'channel' => $stateChannel,
						'dataType' => ValuesTypes\DataType::FLOAT,
						'format' => [0, Entities\Devices\Device::MAXIMUM_FLOOR_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Device::PRECISION,
						'settable' => false,
						'queryable' => true,
					]),
					$currentFloorTempProperty,
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_OVERHEATING->value,
						'channel' => $stateChannel,
						'dataType' => ValuesTypes\DataType::BOOLEAN,
						'format' => null,
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'settable' => false,
						'queryable' => true,
					]),
					$floorOverheatingProperty,
				);
			} else {
				if ($maxFloorTempProperty !== null) {
					$this->channelsPropertiesManager->delete($maxFloorTempProperty);
				}

				if ($currentFloorTempProperty !== null) {
					$this->channelsPropertiesManager->delete($currentFloorTempProperty);
				}

				if ($floorOverheatingProperty !== null) {
					$this->channelsPropertiesManager->delete($floorOverheatingProperty);
				}
			}

			if ($device->hasRoomHumiditySensors()) {
				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_HUMIDITY->value,
						'channel' => $stateChannel,
						'dataType' => ValuesTypes\DataType::UCHAR,
						'format' => [0, 100],
						'unit' => '%',
						'invalid' => null,
						'scale' => null,
						'step' => 1,
						'settable' => false,
						'queryable' => true,
					]),
					$currentRoomHumProperty,
				);
			} else {
				if ($currentRoomHumProperty !== null) {
					$this->channelsPropertiesManager->delete($currentRoomHumProperty);
				}
			}

			$presetModeProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::PRESET_MODE->value,
					'channel' => $stateChannel,
					'dataType' => ValuesTypes\DataType::ENUM,
					'format' => array_map(
						static fn (VirtualThermostatTypes\Preset $preset): string => $preset->value,
						$presets,
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => false,
					'queryable' => true,
					'default' => VirtualThermostatTypes\Preset::MANUAL->value,
				]),
				$presetModeProperty,
			);

			foreach (VirtualThermostatTypes\Preset::cases() as $preset) {
				$findPresetChannelQuery = new Queries\Entities\FindPresetChannels();
				$findPresetChannelQuery->forDevice($device);
				$findPresetChannelQuery->byIdentifier(
					VirtualThermostatTypes\ChannelIdentifier::from('preset_' . $preset->value),
				);

				$presetChannel = $this->channelsRepository->findOneBy(
					$findPresetChannelQuery,
					Entities\Channels\Preset::class,
				);

				if (in_array($preset, $presets, true)) {
					if ($presetChannel === null) {
						$presetChannel = $this->channelsManager->create(Utils\ArrayHash::from([
							'entity' => Entities\Channels\Preset::class,
							'device' => $device,
							'identifier' => 'preset_' . $preset->value,
						]));
						assert($presetChannel instanceof Entities\Channels\Preset);

						$setPresets[$preset->value] = [
							'value' => $this->askTargetTemperature(
								$io,
								$preset,
								$unit,
							),
							'property' => $this->createOrUpdateProperty(
								DevicesEntities\Channels\Properties\Dynamic::class,
								Utils\ArrayHash::from([
									'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
									'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_TEMPERATURE->value,
									'channel' => $presetChannel,
									'dataType' => ValuesTypes\DataType::FLOAT,
									'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
									'unit' => null,
									'invalid' => null,
									'scale' => null,
									'step' => Entities\Devices\Device::PRECISION,
									'settable' => true,
									'queryable' => true,
									'default' => Entities\Devices\Device::TARGET_TEMPERATURE,
								]),
							),
						];

						if (in_array(VirtualThermostatTypes\HvacMode::AUTO, $modes, true)) {
							$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
								$io,
								$preset,
								$unit,
							);

							$this->createOrUpdateProperty(
								DevicesEntities\Channels\Properties\Variable::class,
								Utils\ArrayHash::from([
									'entity' => DevicesEntities\Channels\Properties\Variable::class,
									'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE->value,
									'channel' => $presetChannel,
									'dataType' => ValuesTypes\DataType::FLOAT,
									'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
									'unit' => null,
									'invalid' => null,
									'scale' => null,
									'step' => Entities\Devices\Device::PRECISION,
									'default' => null,
									'value' => $heatingThresholdTemp,
								]),
							);

							$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
								$io,
								$preset,
								$unit,
							);

							$this->createOrUpdateProperty(
								DevicesEntities\Channels\Properties\Variable::class,
								Utils\ArrayHash::from([
									'entity' => DevicesEntities\Channels\Properties\Variable::class,
									'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE->value,
									'channel' => $presetChannel,
									'dataType' => ValuesTypes\DataType::FLOAT,
									'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
									'unit' => null,
									'invalid' => null,
									'scale' => null,
									'step' => Entities\Devices\Device::PRECISION,
									'default' => null,
									'value' => $coolingThresholdTemp,
								]),
							);
						}
					}
				} else {
					if ($presetChannel !== null) {
						$this->channelsManager->delete($presetChannel);
					}
				}
			}

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.update.device.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}

		assert($hvacModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic);

		$hvacModeState = $this->channelsPropertiesConfigurationRepository->find(
			$hvacModeProperty->getId(),
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);
		assert($hvacModeState !== null);

		$this->channelPropertiesStatesManager->set(
			$hvacModeState,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => VirtualThermostatTypes\HvacMode::OFF->value,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
			]),
			Sources\Addon::VIRTUAL_THERMOSTAT,
		);

		assert($presetModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic);

		$presetModeState = $this->channelsPropertiesConfigurationRepository->find(
			$presetModeProperty->getId(),
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);
		assert($presetModeState !== null);

		$this->channelPropertiesStatesManager->set(
			$presetModeState,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => VirtualThermostatTypes\Preset::MANUAL->value,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
			]),
			Sources\Addon::VIRTUAL_THERMOSTAT,
		);

		foreach ($setPresets as $data) {
			$presetConfiguration = $this->channelsPropertiesConfigurationRepository->find(
				$data['property']->getId(),
				DevicesDocuments\Channels\Properties\Dynamic::class,
			);
			assert($presetConfiguration !== null);

			$this->channelPropertiesStatesManager->set(
				$presetConfiguration,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $data['value'],
					DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
				]),
				Sources\Addon::VIRTUAL_THERMOSTAT,
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function deleteDevice(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichDevice($io);

		if ($device === null) {
			$io->info(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.messages.noDevices'),
			);

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.messages.remove.device.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.questions.continue'),
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

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'initialize-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.remove.device.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function manageDevice(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichDevice($io);

		if ($device === null) {
			$io->info(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.messages.noDevices'),
			);

			return;
		}

		$this->askManageDeviceAction($io, $device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listDevices(Style\SymfonyStyle $io): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();

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
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.data.name'),
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.data.hvacModes'),
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.data.presets'),
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.data.connector'),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				implode(', ', array_filter(
					array_map(function (VirtualThermostatTypes\HvacMode $item): string|null {
						if ($item === VirtualThermostatTypes\HvacMode::OFF) {
							return null;
						}

						return (string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.install.answers.mode.' . $item->value,
						);
					}, $device->getHvacModes()),
					static fn (string|null $item): bool => $item !== null,
				)),
				implode(
					', ',
					array_map(fn (VirtualThermostatTypes\Preset $item): string => (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.preset.' . $item->value,
					), $device->getPresetModes()),
				),
				$device->getConnector()->getName() ?? $device->getConnector()->getIdentifier(),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createActor(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$findChannelQuery = new Queries\Entities\FindActorChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(VirtualThermostatTypes\ChannelIdentifier::ACTORS);

		$actorsChannel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Actors::class);
		assert($actorsChannel instanceof Entities\Channels\Actors);

		$actorType = $this->askActorType($io, $device);

		$name = $this->askActorName($io);

		$heater = $this->askActor(
			$io,
			array_map(
				static fn (DevicesEntities\Channels\Properties\Dynamic $actor): string => $actor->getId()->toString(),
				array_filter(
					$device->getActors(),
					// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					static fn (DevicesEntities\Channels\Properties\Property $actor): bool => $actor instanceof DevicesEntities\Channels\Properties\Dynamic,
				),
			),
			[
				ValuesTypes\DataType::BOOLEAN,
				ValuesTypes\DataType::SWITCH,
			],
		);

		if ($heater === false) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'parent' => $heater,
				'entity' => DevicesEntities\Channels\Properties\Mapped::class,
				'identifier' => $this->findChannelPropertyIdentifier(
					$actorsChannel,
					$actorType,
				),
				'name' => $name,
				'channel' => $actorsChannel,
				'dataType' => ValuesTypes\DataType::BOOLEAN,
				'format' => null,
				'unit' => null,
				'invalid' => null,
				'scale' => null,
				'step' => null,
				'settable' => true,
				'queryable' => $heater->isQueryable(),
			]));
			assert($property instanceof DevicesEntities\Channels\Properties\Mapped);

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.create.actor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.create.actor.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function editActor(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$property = $this->askWhichActor($io, $device);

		if ($property === null) {
			$io->warning(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.messages.noActors'),
			);

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.questions.create.actor'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createActor($io, $device);
			}

			return;
		}

		$parent = $property->getParent();
		assert($parent instanceof DevicesEntities\Channels\Properties\Dynamic);

		$name = $this->askActorName($io, $property);

		$parent = $this->askActor(
			$io,
			[],
			[
				ValuesTypes\DataType::BOOLEAN,
				ValuesTypes\DataType::SWITCH,
			],
			$parent,
		);

		if ($parent === false) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$property = $this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
				'name' => $name,
				'parent' => $parent,
			]));
			assert($property instanceof DevicesEntities\Channels\Properties\Mapped);

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.update.actor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.update.actor.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}
	}

	private function listActors(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.data.name'),
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.data.type'),
		]);

		$actors = $device->getActors();
		usort(
			$actors,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);
		$actors = array_filter(
			$actors,
			static fn (DevicesEntities\Channels\Properties\Property $property): bool =>
				str_starts_with(
					$property->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::HEATER_ACTOR->value,
				)
				|| str_starts_with(
					$property->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::COOLER_ACTOR->value,
				),
		);

		foreach ($actors as $index => $property) {
			$type = 'N/A';

			if (str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::HEATER_ACTOR->value,
			)) {
				$type = (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.data.' . VirtualThermostatTypes\ChannelPropertyIdentifier::HEATER_ACTOR->value,
				);
			} elseif (str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::COOLER_ACTOR->value,
			)) {
				$type = (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.data.' . VirtualThermostatTypes\ChannelPropertyIdentifier::COOLER_ACTOR->value,
				);
			}

			$table->addRow([
				$index + 1,
				$property->getName() ?? $property->getIdentifier(),
				$type,
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function deleteActor(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$property = $this->askWhichActor($io, $device);

		if ($property === null) {
			$io->warning(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.messages.noActors'),
			);

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.messages.remove.actor.confirm',
				['name' => $property->getName() ?? $property->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->channelsPropertiesManager->delete($property);

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.remove.actor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.remove.actor.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	private function createSensor(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$findChannelQuery = new Queries\Entities\FindSensorChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(VirtualThermostatTypes\ChannelIdentifier::SENSORS);

		$sensorsChannel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Sensors::class);
		assert($sensorsChannel instanceof Entities\Channels\Sensors);

		$sensorType = $this->askSensorType($io);

		if ($sensorType === VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR) {
			$dataTypes = [
				ValuesTypes\DataType::FLOAT,
				ValuesTypes\DataType::CHAR,
				ValuesTypes\DataType::UCHAR,
				ValuesTypes\DataType::SHORT,
				ValuesTypes\DataType::USHORT,
				ValuesTypes\DataType::INT,
				ValuesTypes\DataType::UINT,
			];
			$dataType = ValuesTypes\DataType::FLOAT;

		} elseif ($sensorType === VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR) {
			$dataTypes = [
				ValuesTypes\DataType::FLOAT,
				ValuesTypes\DataType::CHAR,
				ValuesTypes\DataType::UCHAR,
				ValuesTypes\DataType::SHORT,
				ValuesTypes\DataType::USHORT,
				ValuesTypes\DataType::INT,
				ValuesTypes\DataType::UINT,
			];
			$dataType = ValuesTypes\DataType::FLOAT;

		} elseif ($sensorType === VirtualThermostatTypes\ChannelPropertyIdentifier::OPENING_SENSOR) {
			$dataTypes = [
				ValuesTypes\DataType::BOOLEAN,
			];
			$dataType = ValuesTypes\DataType::BOOLEAN;

		} elseif ($sensorType === VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR) {
			$dataTypes = [
				ValuesTypes\DataType::UCHAR,
				ValuesTypes\DataType::USHORT,
				ValuesTypes\DataType::UINT,
				ValuesTypes\DataType::FLOAT,
			];
			$dataType = ValuesTypes\DataType::UINT;

		} else {
			// Log caught exception
			$this->logger->error(
				'Invalid sensor type selected',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.create.sensor.error',
				),
			);

			return;
		}

		$name = $this->askSensorName($io);

		$sensor = $this->askSensor(
			$io,
			array_map(
				static fn (DevicesEntities\Channels\Properties\Dynamic $sensor): string => $sensor->getId()->toString(),
				array_filter(
					$device->getSensors(),
					// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					static fn (DevicesEntities\Channels\Properties\Property $sensor): bool => $sensor instanceof DevicesEntities\Channels\Properties\Dynamic,
				),
			),
			$dataTypes,
		);

		if ($sensor === false) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'parent' => $sensor,
				'entity' => DevicesEntities\Channels\Properties\Mapped::class,
				'identifier' => $this->findChannelPropertyIdentifier(
					$sensorsChannel,
					$sensorType,
				),
				'name' => $name,
				'channel' => $sensorsChannel,
				'dataType' => $dataType,
				'format' => null,
				'unit' => null,
				'invalid' => null,
				'scale' => null,
				'step' => null,
				'settable' => false,
				'queryable' => $sensor->isQueryable(),
			]));
			assert($property instanceof DevicesEntities\Channels\Properties\Mapped);

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.create.sensor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.create.sensor.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 */
	private function editSensor(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$property = $this->askWhichSensor($io, $device);

		if ($property === null) {
			$io->warning(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.messages.noSensors'),
			);

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.questions.create.sensor'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createSensor($io, $device);
			}

			return;
		}

		$parent = $property->getParent();
		assert($parent instanceof DevicesEntities\Channels\Properties\Dynamic);

		$name = $this->askSensorName($io, $property);

		if (
			str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
			)
		) {
			$dataTypes = [
				ValuesTypes\DataType::FLOAT,
				ValuesTypes\DataType::CHAR,
				ValuesTypes\DataType::UCHAR,
				ValuesTypes\DataType::SHORT,
				ValuesTypes\DataType::USHORT,
				ValuesTypes\DataType::INT,
				ValuesTypes\DataType::UINT,
			];

		} elseif (
			str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
			)
		) {
			$dataTypes = [
				ValuesTypes\DataType::FLOAT,
				ValuesTypes\DataType::CHAR,
				ValuesTypes\DataType::UCHAR,
				ValuesTypes\DataType::SHORT,
				ValuesTypes\DataType::USHORT,
				ValuesTypes\DataType::INT,
				ValuesTypes\DataType::UINT,
			];

		} elseif (
			str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::OPENING_SENSOR->value,
			)
		) {
			$dataTypes = [
				ValuesTypes\DataType::BOOLEAN,
			];

		} elseif (
			str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
			)
		) {
			$dataTypes = [
				ValuesTypes\DataType::UCHAR,
				ValuesTypes\DataType::USHORT,
				ValuesTypes\DataType::UINT,
				ValuesTypes\DataType::FLOAT,
			];

		} else {
			// Log caught exception
			$this->logger->error(
				'Invalid sensor type selected',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.edit.sensor.error',
				),
			);

			return;
		}

		$parent = $this->askSensor(
			$io,
			[],
			$dataTypes,
			$parent,
		);

		if ($parent === false) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$property = $this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
				'parent' => $parent,
				'name' => $name,
			]));
			assert($property instanceof DevicesEntities\Channels\Properties\Mapped);

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.update.sensor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.update.sensor.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}
	}

	private function listSensors(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.data.name'),
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.data.type'),
		]);

		$sensors = $device->getSensors();
		usort(
			$sensors,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);
		$sensors = array_filter(
			$sensors,
			static fn (DevicesEntities\Channels\Properties\Property $property): bool =>
				str_starts_with(
					$property->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
				)
				|| str_starts_with(
					$property->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
				)
				|| str_starts_with(
					$property->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::OPENING_SENSOR->value,
				)
				|| str_starts_with(
					$property->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
				),
		);

		foreach ($sensors as $index => $property) {
			$type = 'N/A';

			if (str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
			)) {
				$type = (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.data.' . VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
				);
			} elseif (str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
			)) {
				$type = (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.data.'
					. VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
				);
			} elseif (str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::OPENING_SENSOR->value,
			)) {
				$type = (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.data.' . VirtualThermostatTypes\ChannelPropertyIdentifier::OPENING_SENSOR->value,
				);
			} elseif (str_starts_with(
				$property->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
			)) {
				$type = (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.data.' . VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
				);
			}

			$table->addRow([
				$index + 1,
				$property->getName() ?? $property->getIdentifier(),
				$type,
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function deleteSensor(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$property = $this->askWhichSensor($io, $device);

		if ($property === null) {
			$io->warning(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.messages.noSensors'),
			);

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.messages.remove.sensor.confirm',
				['name' => $property->getName() ?? $property->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->channelsPropertiesManager->delete($property);

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.remove.sensor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.remove.sensor.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function editPreset(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$preset = $this->askWhichPreset($io, $device);

		if ($preset === null) {
			$io->warning(
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.messages.noPresets'),
			);

			return;
		}

		$findChannelQuery = new Queries\Entities\FindConfigurationChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(VirtualThermostatTypes\ChannelIdentifier::CONFIGURATION);

		$configuration = $this->channelsRepository->findOneBy(
			$findChannelQuery,
			Entities\Channels\Configuration::class,
		);
		assert($configuration instanceof Entities\Channels\Configuration);

		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelVariableProperties();
		$findChannelPropertyQuery->forChannel($configuration);
		$findChannelPropertyQuery->byIdentifier(VirtualThermostatTypes\ChannelPropertyIdentifier::UNIT->value);

		$unitProperty = $this->channelsPropertiesRepository->findOneBy(
			$findChannelPropertyQuery,
			DevicesEntities\Channels\Properties\Variable::class,
		);
		assert($unitProperty instanceof DevicesEntities\Channels\Properties\Variable);

		$findChannelQuery = new Queries\Entities\FindPresetChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->endWithIdentifier($preset->value);

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Preset::class);

		$targetTempProperty = $heatingThresholdTempProperty = $coolingThresholdTempProperty = null;

		if ($channel !== null) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_TEMPERATURE->value,
			);

			$targetTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE->value,
			);

			$heatingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(
				VirtualThermostatTypes\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE->value,
			);

			$coolingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);
		}

		$targetTemp = $this->askTargetTemperature(
			$io,
			$preset,
			VirtualThermostatTypes\Unit::from(Utilities\Value::toString($unitProperty->getValue(), true)),
			$device,
		);

		$heatingThresholdTemp = $coolingThresholdTemp = null;

		if (in_array(VirtualThermostatTypes\HvacMode::AUTO, $device->getHvacModes(), true)) {
			$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
				$io,
				$preset,
				VirtualThermostatTypes\Unit::from(Utilities\Value::toString($unitProperty->getValue(), true)),
				$device,
			);

			$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
				$io,
				$preset,
				VirtualThermostatTypes\Unit::from(Utilities\Value::toString($unitProperty->getValue(), true)),
				$device,
			);
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			if ($channel === null) {
				$channel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Preset::class,
					'device' => $device,
					'identifier' => 'preset_' . $preset->value,
				]));
				assert($channel instanceof Entities\Channels\Preset);
			}

			$targetTempProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::TARGET_ROOM_TEMPERATURE->value,
					'channel' => $channel,
					'dataType' => ValuesTypes\DataType::FLOAT,
					'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Device::PRECISION,
					'settable' => true,
					'queryable' => true,
					'default' => Entities\Devices\Device::TARGET_TEMPERATURE,
				]),
				$targetTempProperty,
			);

			if (in_array(VirtualThermostatTypes\HvacMode::AUTO, $device->getHvacModes(), true)) {
				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE->value,
						'channel' => $channel,
						'dataType' => ValuesTypes\DataType::FLOAT,
						'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Device::PRECISION,
						'default' => null,
						'value' => $heatingThresholdTemp,
					]),
					$heatingThresholdTempProperty,
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => VirtualThermostatTypes\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE->value,
						'channel' => $channel,
						'dataType' => ValuesTypes\DataType::FLOAT,
						'format' => [Entities\Devices\Device::MINIMUM_TEMPERATURE, Entities\Devices\Device::MAXIMUM_SET_ROOM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Device::PRECISION,
						'default' => null,
						'value' => $coolingThresholdTemp,
					]),
					$coolingThresholdTempProperty,
				);
			}

			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'install-cmd',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.update.device.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}

		assert($targetTempProperty instanceof DevicesEntities\Channels\Properties\Dynamic);

		$targetTempConfiguration = $this->channelsPropertiesConfigurationRepository->find(
			$targetTempProperty->getId(),
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);
		assert($targetTempConfiguration !== null);

		$this->channelPropertiesStatesManager->set(
			$targetTempConfiguration,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => $targetTemp,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
			]),
			Sources\Addon::VIRTUAL_THERMOSTAT,
		);
	}

	private function askDeviceName(Style\SymfonyStyle $io, Entities\Devices\Device|null $device = null): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.provide.device.name',
			),
			$device?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @return array<VirtualThermostatTypes\HvacMode>
	 *
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askThermostatModes(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Dynamic|null $property = null,
	): array
	{
		if (
			$property !== null
			&& (
				$property->getIdentifier() !== VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_MODE->value
				|| !$property->getFormat() instanceof Formats\StringEnum
			)
		) {
			throw new VirtualThermostatExceptions\InvalidArgument('Provided property is not valid');
		}

		$format = $property?->getFormat();
		assert($format === null || $format instanceof Formats\StringEnum);

		$default = array_filter(
			array_unique(array_map(static fn ($item): int|null => match ($item) {
					VirtualThermostatTypes\HvacMode::HEAT->value => 0,
					VirtualThermostatTypes\HvacMode::COOL->value => 1,
					VirtualThermostatTypes\HvacMode::AUTO->value => 2,
					default => null,
			}, $format?->toArray() ?? [VirtualThermostatTypes\HvacMode::HEAT->value])),
			static fn (int|null $item): bool => $item !== null,
		);

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.questions.select.mode'),
			[
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.mode.' . VirtualThermostatTypes\HvacMode::HEAT->value,
				),
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.mode.' . VirtualThermostatTypes\HvacMode::COOL->value,
				),
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.mode.' . VirtualThermostatTypes\HvacMode::AUTO->value,
				),
			],
			implode(',', $default),
		);
		$question->setMultiselect(true);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer): array {
			if ($answer === null) {
				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			$modes = [];

			foreach (explode(',', strval($answer)) as $item) {
				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.mode.' . VirtualThermostatTypes\HvacMode::HEAT->value,
					)
					|| $item === '0'
				) {
					$modes[] = VirtualThermostatTypes\HvacMode::HEAT;
				}

				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.mode.' . VirtualThermostatTypes\HvacMode::COOL->value,
					)
					|| $item === '1'
				) {
					$modes[] = VirtualThermostatTypes\HvacMode::COOL;
				}

				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.mode.' . VirtualThermostatTypes\HvacMode::AUTO->value,
					)
					|| $item === '2'
				) {
					$modes[] = VirtualThermostatTypes\HvacMode::AUTO;
				}
			}

			if ($modes !== []) {
				return $modes;
			}

			throw new VirtualThermostatExceptions\Runtime(
				sprintf(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
					),
					$answer,
				),
			);
		});

		$modes = $io->askQuestion($question);
		assert(is_array($modes));

		if (in_array(VirtualThermostatTypes\HvacMode::AUTO, $modes, true)) {
			$modes[] = VirtualThermostatTypes\HvacMode::COOL;
			$modes[] = VirtualThermostatTypes\HvacMode::HEAT;

			$modes = array_unique($modes);
		}

		return $modes;
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askThermostatUnits(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Variable|null $property = null,
	): VirtualThermostatTypes\Unit
	{
		if (
			$property !== null
			&& (
				$property->getIdentifier() !== VirtualThermostatTypes\ChannelPropertyIdentifier::UNIT->value
				|| !$property->getFormat() instanceof Formats\StringEnum
			)
		) {
			throw new VirtualThermostatExceptions\InvalidArgument('Provided property is not valid');
		}

		$default = match ($property?->getValue() ?? VirtualThermostatTypes\Unit::CELSIUS->value) {
			VirtualThermostatTypes\Unit::CELSIUS->value => 0,
			VirtualThermostatTypes\Unit::FAHRENHEIT->value => 1,
			default => 0,
		};

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.provide.device.unit',
			),
			[
				0 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.unit.celsius',
				),
				1 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.unit.fahrenheit',
				),
			],
			$default,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): VirtualThermostatTypes\Unit {
			if ($answer === null) {
				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			if (
				$answer === (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.unit.celsius',
				)
				|| $answer === '0'
			) {
				return VirtualThermostatTypes\Unit::CELSIUS;
			}

			if (
				$answer === (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.unit.fahrenheit',
				)
				|| $answer === '1'
			) {
				return VirtualThermostatTypes\Unit::FAHRENHEIT;
			}

			throw new VirtualThermostatExceptions\Runtime(
				sprintf(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
					),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof VirtualThermostatTypes\Unit);

		return $answer;
	}

	/**
	 * @return array<VirtualThermostatTypes\Preset>
	 *
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askPresets(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Dynamic|null $property = null,
	): array
	{
		if (
			$property !== null
			&& (
				$property->getIdentifier() !== VirtualThermostatTypes\ChannelPropertyIdentifier::PRESET_MODE->value
				|| !$property->getFormat() instanceof Formats\StringEnum
			)
		) {
			throw new VirtualThermostatExceptions\InvalidArgument('Provided property is not valid');
		}

		$format = $property?->getFormat();
		assert($format === null || $format instanceof Formats\StringEnum);

		$default = array_filter(
			array_unique(array_map(static fn ($item): int|null => match ($item) {
				VirtualThermostatTypes\Preset::AWAY->value => 0,
				VirtualThermostatTypes\Preset::ECO->value => 1,
				VirtualThermostatTypes\Preset::HOME->value => 2,
				VirtualThermostatTypes\Preset::COMFORT->value => 3,
				VirtualThermostatTypes\Preset::SLEEP->value => 4,
				VirtualThermostatTypes\Preset::ANTI_FREEZE->value => 5,
				default => null,
			}, $format?->toArray() ?? [
				VirtualThermostatTypes\Preset::AWAY->value,
				VirtualThermostatTypes\Preset::ECO->value,
				VirtualThermostatTypes\Preset::HOME->value,
				VirtualThermostatTypes\Preset::COMFORT->value,
				VirtualThermostatTypes\Preset::SLEEP->value,
				VirtualThermostatTypes\Preset::ANTI_FREEZE->value,
			])),
			static fn (int|null $item): bool => $item !== null,
		);

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.questions.select.preset'),
			[
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::AWAY->value,
				),
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::ECO->value,
				),
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::HOME->value,
				),
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::COMFORT->value,
				),
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::SLEEP->value,
				),
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::ANTI_FREEZE->value,
				),
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.answers.preset.none',
				),
			],
			$default !== [] ? implode(',', $default) : '6',
		);
		$question->setMultiselect(true);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer): array {
			if ($answer === null) {
				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			$presets = [];

			foreach (explode(',', strval($answer)) as $item) {
				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::AWAY->value,
					)
					|| $item === '0'
				) {
					$presets[] = VirtualThermostatTypes\Preset::AWAY;
				}

				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::ECO->value,
					)
					|| $item === '1'
				) {
					$presets[] = VirtualThermostatTypes\Preset::ECO;
				}

				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::HOME->value,
					)
					|| $item === '2'
				) {
					$presets[] = VirtualThermostatTypes\Preset::HOME;
				}

				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::COMFORT->value,
					)
					|| $item === '3'
				) {
					$presets[] = VirtualThermostatTypes\Preset::COMFORT;
				}

				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::SLEEP->value,
					)
					|| $item === '4'
				) {
					$presets[] = VirtualThermostatTypes\Preset::SLEEP;
				}

				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.preset.' . VirtualThermostatTypes\Preset::ANTI_FREEZE->value,
					)
					|| $item === '5'
				) {
					$presets[] = VirtualThermostatTypes\Preset::ANTI_FREEZE;
				}

				if (
					$item === (string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.install.answers.preset.none',
					)
					|| $item === '6'
				) {
					return [];
				}
			}

			if ($presets !== []) {
				return $presets;
			}

			throw new VirtualThermostatExceptions\Runtime(
				sprintf(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
					),
					$answer,
				),
			);
		});

		$presets = $io->askQuestion($question);
		assert(is_array($presets));

		return array_merge([VirtualThermostatTypes\Preset::MANUAL], $presets);
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askActorType(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
	): VirtualThermostatTypes\ChannelPropertyIdentifier
	{
		$types = [];

		if (in_array(VirtualThermostatTypes\HvacMode::HEAT, $device->getHvacModes(), true)) {
			$types[VirtualThermostatTypes\ChannelPropertyIdentifier::HEATER_ACTOR->value] = (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.answers.actor.heater',
			);
		}

		if (in_array(VirtualThermostatTypes\HvacMode::COOL, $device->getHvacModes(), true)) {
			$types[VirtualThermostatTypes\ChannelPropertyIdentifier::COOLER_ACTOR->value] = (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.answers.actor.cooler',
			);
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.questions.select.actorType'),
			array_values($types),
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($types): VirtualThermostatTypes\ChannelPropertyIdentifier {
				if ($answer === null) {
					throw new VirtualThermostatExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($types))) {
					$answer = array_values($types)[$answer];
				}

				$type = array_search($answer, $types, true);

				if ($type !== false) {
					return VirtualThermostatTypes\ChannelPropertyIdentifier::from($type);
				}

				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$type = $io->askQuestion($question);
		assert($type instanceof VirtualThermostatTypes\ChannelPropertyIdentifier);

		return $type;
	}

	private function askActorName(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Property|null $property = null,
	): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.provide.actor.name',
			),
			$property?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @param array<string> $ignoredIds
	 * @param array<ValuesTypes\DataType>|null $allowedDataTypes
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function askActor(
		Style\SymfonyStyle $io,
		array $ignoredIds = [],
		array|null $allowedDataTypes = null,
		DevicesEntities\Channels\Properties\Dynamic|null $property = null,
	): DevicesEntities\Channels\Properties\Dynamic|false
	{
		$parent = $this->askProperty(
			$io,
			$ignoredIds,
			$allowedDataTypes,
			DevicesEntities\Channels\Properties\Dynamic::class,
			$property,
			true,
		);

		if ($parent === null) {
			return false;
		}

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.property.notSupported',
				),
			);

			return $this->askActor($io, $ignoredIds, $allowedDataTypes, $property);
		}

		return $parent;
	}

	private function askSensorType(
		Style\SymfonyStyle $io,
	): VirtualThermostatTypes\ChannelPropertyIdentifier
	{
		$types = [
			VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value => (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.answers.sensor.roomTemperature',
			),
			VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value => (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.answers.sensor.floorTemperature',
			),
			VirtualThermostatTypes\ChannelPropertyIdentifier::OPENING_SENSOR->value => (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.answers.sensor.opening',
			),
			VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value => (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.answers.sensor.roomHumidity',
			),
		];

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.questions.select.sensorType'),
			array_values($types),
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($types): VirtualThermostatTypes\ChannelPropertyIdentifier {
				if ($answer === null) {
					throw new VirtualThermostatExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($types))) {
					$answer = array_values($types)[$answer];
				}

				$type = array_search($answer, $types, true);

				if ($type !== false) {
					return VirtualThermostatTypes\ChannelPropertyIdentifier::from($type);
				}

				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$type = $io->askQuestion($question);
		assert($type instanceof VirtualThermostatTypes\ChannelPropertyIdentifier);

		return $type;
	}

	private function askSensorName(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Property|null $property = null,
	): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.provide.sensor.name',
			),
			$property?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @param array<string> $ignoredIds
	 * @param array<ValuesTypes\DataType>|null $allowedDataTypes
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function askSensor(
		Style\SymfonyStyle $io,
		array $ignoredIds = [],
		array|null $allowedDataTypes = null,
		DevicesEntities\Channels\Properties\Dynamic|null $property = null,
	): DevicesEntities\Channels\Properties\Dynamic|false
	{
		$parent = $this->askProperty(
			$io,
			$ignoredIds,
			$allowedDataTypes,
			DevicesEntities\Channels\Properties\Dynamic::class,
			$property,
		);

		if ($parent === null) {
			return false;
		}

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$io->error(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.property.notSupported',
				),
			);

			return $this->askSensor($io, $ignoredIds, $allowedDataTypes, $property);
		}

		return $parent;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askTargetTemperature(
		Style\SymfonyStyle $io,
		VirtualThermostatTypes\Preset $thermostatMode,
		VirtualThermostatTypes\Unit $unit,
		Entities\Devices\Device|null $device = null,
	): float
	{
		try {
			$property = $device?->getTargetTemp($thermostatMode);
		} catch (VirtualThermostatExceptions\InvalidState) {
			$property = null;
		}

		$targetTemp = Entities\Devices\Device::TARGET_TEMPERATURE;

		if ($property !== null) {
			$propertyConfiguration = $this->channelsPropertiesConfigurationRepository->find(
				$property->getId(),
				DevicesDocuments\Channels\Properties\Dynamic::class,
			);
			assert($propertyConfiguration !== null);

			$state = $this->channelPropertiesStatesManager->read(
				$propertyConfiguration,
				Sources\Addon::VIRTUAL_THERMOSTAT,
			);

			if ($state instanceof DevicesDocuments\States\Channels\Properties\Property) {
				$targetTemp = $state->getGet()->getActualValue();
			}

			assert(is_numeric($targetTemp) || $targetTemp === null);
		}

		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.provide.targetTemperature.' . $thermostatMode->value,
				['unit' => $unit->value],
			),
			$targetTemp,
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new VirtualThermostatExceptions\Runtime(
				sprintf(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
					),
					$answer,
				),
			);
		});

		$targetTemp = $io->askQuestion($question);
		assert(is_float($targetTemp));

		return $targetTemp;
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askMaxFloorTemperature(
		Style\SymfonyStyle $io,
		VirtualThermostatTypes\Unit $unit,
		Entities\Devices\Device|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.provide.maximumFloorTemperature',
				['unit' => $unit->value],
			),
			$device?->getMaximumFloorTemp() ?? Entities\Devices\Device::MAXIMUM_SET_FLOOR_TEMPERATURE,
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new VirtualThermostatExceptions\Runtime(
				sprintf(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
					),
					$answer,
				),
			);
		});

		$maximumFloorTemperature = $io->askQuestion($question);
		assert(is_float($maximumFloorTemperature));

		return $maximumFloorTemperature;
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askHeatingThresholdTemperature(
		Style\SymfonyStyle $io,
		VirtualThermostatTypes\Preset $thermostatMode,
		VirtualThermostatTypes\Unit $unit,
		Entities\Devices\Device|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.provide.heatingThresholdTemperature',
				['unit' => $unit->value],
			),
			$device?->getHeatingThresholdTemp($thermostatMode),
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new VirtualThermostatExceptions\Runtime(
				sprintf(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
					),
					$answer,
				),
			);
		});

		$maximumFloorTemperature = $io->askQuestion($question);
		assert(is_float($maximumFloorTemperature));

		return $maximumFloorTemperature;
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askCoolingThresholdTemperature(
		Style\SymfonyStyle $io,
		VirtualThermostatTypes\Preset $thermostatMode,
		VirtualThermostatTypes\Unit $unit,
		Entities\Devices\Device|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.provide.coolingThresholdTemperature',
				['unit' => $unit->value],
			),
			$device?->getCoolingThresholdTemp($thermostatMode),
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new VirtualThermostatExceptions\Runtime(
				sprintf(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
					),
					$answer,
				),
			);
		});

		$maximumFloorTemperature = $io->askQuestion($question);
		assert(is_float($maximumFloorTemperature));

		return $maximumFloorTemperature;
	}

	/**
	 * @param array<string> $ignoredIds
	 * @param array<ValuesTypes\DataType>|null $allowedDataTypes
	 * @param class-string<DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable> $onlyType
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function askProperty(
		Style\SymfonyStyle $io,
		array $ignoredIds = [],
		array|null $allowedDataTypes = null,
		string|null $onlyType = null,
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable|null $connectedProperty = null,
		bool|null $settable = null,
	): DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable|null
	{
		$devices = [];

		$connectedChannel = $connectedProperty?->getChannel();
		$connectedDevice = $connectedProperty?->getChannel()->getDevice();

		$findDevicesQuery = new DevicesQueries\Entities\FindDevices();

		$systemDevices = $this->devicesRepository->findAllBy($findDevicesQuery);
		usort(
			$systemDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => (
				(
					($a->getConnector()->getName() ?? $a->getConnector()->getIdentifier())
					<=> ($b->getConnector()->getName() ?? $b->getConnector()->getIdentifier())
				) * 100 +
				(($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier()))
			),
		);

		foreach ($systemDevices as $device) {
			if ($device instanceof Entities\Devices\Device) {
				continue;
			}

			$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			$hasProperty = false;

			foreach ($channels as $channel) {
				if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Dynamic::class) {
					$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					if ($settable === true) {
						$findChannelPropertiesQuery->settable(true);
					}

					if ($allowedDataTypes === null) {
						if (
							$this->channelsPropertiesRepository->getResultSet(
								$findChannelPropertiesQuery,
								DevicesEntities\Channels\Properties\Dynamic::class,
							)->count() > 0
						) {
							$hasProperty = true;

							break;
						}
					} else {
						$properties = $this->channelsPropertiesRepository->findAllBy(
							$findChannelPropertiesQuery,
							DevicesEntities\Channels\Properties\Dynamic::class,
						);
						$properties = array_filter(
							$properties,
							static fn (DevicesEntities\Channels\Properties\Dynamic $property): bool => in_array(
								$property->getDataType(),
								$allowedDataTypes,
								true,
							),
						);

						if ($properties !== []) {
							$hasProperty = true;

							break;
						}
					}
				}

				if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Variable::class) {
					$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelVariableProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					if ($settable === true) {
						$findChannelPropertiesQuery->settable(true);
					}

					if ($allowedDataTypes === null) {
						if (
							$this->channelsPropertiesRepository->getResultSet(
								$findChannelPropertiesQuery,
								DevicesEntities\Channels\Properties\Variable::class,
							)->count() > 0
						) {
							$hasProperty = true;

							break;
						}
					} else {
						$properties = $this->channelsPropertiesRepository->findAllBy(
							$findChannelPropertiesQuery,
							DevicesEntities\Channels\Properties\Variable::class,
						);
						$properties = array_filter(
							$properties,
							static fn (DevicesEntities\Channels\Properties\Variable $property): bool => in_array(
								$property->getDataType(),
								$allowedDataTypes,
								true,
							),
						);

						if ($properties !== []) {
							$hasProperty = true;

							break;
						}
					}
				}
			}

			if (!$hasProperty) {
				continue;
			}

			$devices[$device->getId()->toString()] = '[' . ($device->getConnector()->getName() ?? $device->getConnector()->getIdentifier()) . '] '
				. ($device->getName() ?? $device->getIdentifier());
		}

		if (count($devices) === 0) {
			$io->warning(
				(string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.messages.noHardwareDevices',
				),
			);

			return null;
		}

		$default = count($devices) === 1 ? 0 : null;

		if ($connectedDevice !== null) {
			foreach (array_values(array_flip($devices)) as $index => $value) {
				if ($value === $connectedDevice->getId()->toString()) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.select.mappedDevice',
			),
			array_values($devices),
			$default,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($devices): DevicesEntities\Devices\Device {
			if ($answer === null) {
				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
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
				$device = $this->devicesRepository->find(Uuid\Uuid::fromString($identifier));

				if ($device !== null) {
					return $device;
				}
			}

			throw new VirtualThermostatExceptions\Runtime(
				sprintf(
					(string) $this->translator->translate(
						'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
					),
					$answer,
				),
			);
		});

		$device = $io->askQuestion($question);
		assert($device instanceof DevicesEntities\Devices\Device);

		$channels = [];

		$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);
		$findChannelsQuery->withProperties();

		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery);
		usort(
			$deviceChannels,
			static fn (DevicesEntities\Channels\Channel $a, DevicesEntities\Channels\Channel $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($deviceChannels as $channel) {
			$hasProperty = false;

			if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Dynamic::class) {
				$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				if ($settable === true) {
					$findChannelPropertiesQuery->settable(true);
				}

				if ($allowedDataTypes === null) {
					if (
						$this->channelsPropertiesRepository->getResultSet(
							$findChannelPropertiesQuery,
							DevicesEntities\Channels\Properties\Dynamic::class,
						)->count() > 0
					) {
						$hasProperty = true;
					}
				} else {
					$properties = $this->channelsPropertiesRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Dynamic::class,
					);
					$properties = array_filter(
						$properties,
						static fn (DevicesEntities\Channels\Properties\Dynamic $property): bool => in_array(
							$property->getDataType(),
							$allowedDataTypes,
							true,
						),
					);

					if ($properties !== []) {
						$hasProperty = true;
					}
				}
			}

			if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Variable::class) {
				$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelVariableProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				if ($settable === true) {
					$findChannelPropertiesQuery->settable(true);
				}

				if ($allowedDataTypes === null) {
					if (
						$this->channelsPropertiesRepository->getResultSet(
							$findChannelPropertiesQuery,
							DevicesEntities\Channels\Properties\Variable::class,
						)->count() > 0
					) {
						$hasProperty = true;
					}
				} else {
					$properties = $this->channelsPropertiesRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Variable::class,
					);
					$properties = array_filter(
						$properties,
						static fn (DevicesEntities\Channels\Properties\Variable $property): bool => in_array(
							$property->getDataType(),
							$allowedDataTypes,
							true,
						),
					);

					if ($properties !== []) {
						$hasProperty = true;
					}
				}
			}

			if (!$hasProperty) {
				continue;
			}

			$channels[$channel->getId()->toString()] = $channel->getName() ?? $channel->getIdentifier();
		}

		$default = count($channels) === 1 ? 0 : null;

		if ($connectedChannel !== null) {
			foreach (array_values(array_flip($channels)) as $index => $value) {
				if ($value === $connectedChannel->getId()->toString()) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.select.mappedDeviceChannel',
			),
			array_values($channels),
			$default,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|null $answer) use ($channels): DevicesEntities\Channels\Channel {
				if ($answer === null) {
					throw new VirtualThermostatExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($channels))) {
					$answer = array_values($channels)[$answer];
				}

				$identifier = array_search($answer, $channels, true);

				if ($identifier !== false) {
					$channel = $this->channelsRepository->find(Uuid\Uuid::fromString($identifier));

					if ($channel !== null) {
						return $channel;
					}
				}

				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$channel = $io->askQuestion($question);
		assert($channel instanceof DevicesEntities\Channels\Channel);

		$properties = [];

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		if ($settable === true) {
			$findChannelPropertiesQuery->settable(true);
		}

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery);
		usort(
			$channelProperties,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($channelProperties as $property) {
			if (
				!$property instanceof DevicesEntities\Channels\Properties\Dynamic
				&& !$property instanceof DevicesEntities\Channels\Properties\Variable
				|| in_array($property->getId()->toString(), $ignoredIds, true)
				|| (
					$onlyType !== null
					&& !$property instanceof $onlyType
				)
				|| (
					$allowedDataTypes !== null
					&& !in_array($property->getDataType(), $allowedDataTypes, true)
				)
			) {
				continue;
			}

			$properties[$property->getId()->toString()] = $property->getName() ?? $property->getIdentifier();
		}

		$default = count($properties) === 1 ? 0 : null;

		if ($connectedProperty !== null) {
			foreach (array_values(array_flip($properties)) as $index => $value) {
				if ($value === $connectedProperty->getId()->toString()) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.select.mappedChannelProperty',
			),
			array_values($properties),
			$default,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			function (string|null $answer) use ($properties): DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable {
				if ($answer === null) {
					throw new VirtualThermostatExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($properties))) {
					$answer = array_values($properties)[$answer];
				}

				$identifier = array_search($answer, $properties, true);

				if ($identifier !== false) {
					$property = $this->channelsPropertiesRepository->find(Uuid\Uuid::fromString($identifier));

					if ($property !== null) {
						assert(
							$property instanceof DevicesEntities\Channels\Properties\Dynamic
							|| $property instanceof DevicesEntities\Channels\Properties\Variable,
						);

						return $property;
					}
				}

				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$property = $io->askQuestion($question);
		assert(
			$property instanceof DevicesEntities\Channels\Properties\Dynamic || $property instanceof DevicesEntities\Channels\Properties\Variable,
		);

		return $property;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 * @throws VirtualExceptions\InvalidArgument
	 */
	private function askInstallAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.create.device',
				),
				1 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.update.device',
				),
				2 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.remove.device',
				),
				3 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.manage.device',
				),
				4 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.list.devices',
				),
				5 => (string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.create.device',
			)
			|| $whatToDo === '0'
		) {
			$this->createDevice($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.update.device',
			)
			|| $whatToDo === '1'
		) {
			$this->editDevice($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.remove.device',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteDevice($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.manage.device',
			)
			|| $whatToDo === '3'
		) {
			$this->manageDevice($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.list.devices',
			)
			|| $whatToDo === '4'
		) {
			$this->listDevices($io);

			$this->askInstallAction($io);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askManageDeviceAction(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
	): void
	{
		$device = $this->devicesRepository->find($device->getId(), Entities\Devices\Device::class);
		assert($device instanceof Entities\Devices\Device);

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.create.actor',
				),
				1 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.update.actor',
				),
				2 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.list.actors',
				),
				3 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.remove.actor',
				),
				4 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.create.sensor',
				),
				5 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.update.sensor',
				),
				6 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.list.sensors',
				),
				7 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.remove.sensor',
				),
				8 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.update.preset',
				),
				9 => (string) $this->translator->translate(
					'//virtual-thermostat-addon.cmd.install.actions.nothing',
				),
			],
			9,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.create.actor',
			)
			|| $whatToDo === '0'
		) {
			$this->createActor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.update.actor',
			)
			|| $whatToDo === '1'
		) {
			$this->editActor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.list.actors',
			)
			|| $whatToDo === '2'
		) {
			$this->listActors($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.remove.actor',
			)
			|| $whatToDo === '3'
		) {
			$this->deleteActor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.create.sensor',
			)
			|| $whatToDo === '4'
		) {
			$this->createSensor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.update.sensor',
			)
			|| $whatToDo === '5'
		) {
			$this->editSensor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.list.sensors',
			)
			|| $whatToDo === '6'
		) {
			$this->listSensors($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.remove.sensor',
			)
			|| $whatToDo === '7'
		) {
			$this->deleteSensor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.actions.update.preset',
			)
			|| $whatToDo === '8'
		) {
			$this->editPreset($io, $device);

			$this->askManageDeviceAction($io, $device);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): VirtualEntities\Connectors\Connector|null
	{
		$connectors = [];

		$findConnectorsQuery = new VirtualQueries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			VirtualEntities\Connectors\Connector::class,
		);
		usort(
			$systemConnectors,
			static fn (VirtualEntities\Connectors\Connector $a, VirtualEntities\Connectors\Connector $b): int => (
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
				'//virtual-thermostat-addon.cmd.install.questions.select.item.connector',
			),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connectors): VirtualEntities\Connectors\Connector {
				if ($answer === null) {
					throw new VirtualThermostatExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
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
					$findConnectorQuery = new VirtualQueries\Entities\FindConnectors();
					$findConnectorQuery->byIdentifier($identifier);

					$connector = $this->connectorsRepository->findOneBy(
						$findConnectorQuery,
						VirtualEntities\Connectors\Connector::class,
					);

					if ($connector !== null) {
						return $connector;
					}
				}

				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$connector = $io->askQuestion($question);
		assert($connector instanceof VirtualEntities\Connectors\Connector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
	): Entities\Devices\Device|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindDevices();

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\Devices\Device::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\Devices\Device $a, Entities\Devices\Device $b): int => (
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
				'//virtual-thermostat-addon.cmd.install.questions.select.item.device',
			),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($devices): Entities\Devices\Device {
				if ($answer === null) {
					throw new VirtualThermostatExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
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
					$findDeviceQuery = new Queries\Entities\FindDevices();
					$findDeviceQuery->byIdentifier($identifier);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\Devices\Device::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\Devices\Device);

		return $device;
	}

	/**
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askWhichPreset(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
	): VirtualThermostatTypes\Preset|null
	{
		$allowedValues = $device->getPresetModes();

		if ($allowedValues === []) {
			return null;
		}

		$presets = [];

		foreach (VirtualThermostatTypes\Preset::cases() as $preset) {
			if (
				!in_array($preset, $allowedValues, true)
				|| $preset === VirtualThermostatTypes\Preset::AUTO
			) {
				continue;
			}

			$presets[$preset->value] = (string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.answers.preset.' . $preset->value,
			);
		}

		if (count($presets) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.select.presetToUpdate',
			),
			array_values($presets),
			count($presets) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($presets): VirtualThermostatTypes\Preset {
				if ($answer === null) {
					throw new VirtualThermostatExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($presets))) {
					$answer = array_values($presets)[$answer];
				}

				$preset = array_search($answer, $presets, true);

				if ($preset !== false) {
					return VirtualThermostatTypes\Preset::from($preset);
				}

				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$preset = $io->askQuestion($question);
		assert($preset instanceof VirtualThermostatTypes\Preset);

		return $preset;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function askWhichActor(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
	): DevicesEntities\Channels\Properties\Mapped|null
	{
		$actors = [];

		$findChannelsQuery = new Queries\Entities\FindActorChannels();
		$findChannelsQuery->forDevice($device);

		$channel = $this->channelsRepository->findOneBy($findChannelsQuery, Entities\Channels\Actors::class);

		if ($channel === null) {
			return null;
		}

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelMappedProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$channelActors = $this->channelsPropertiesRepository->findAllBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Mapped::class,
		);
		usort(
			$channelActors,
			static fn (DevicesEntities\Channels\Properties\Mapped $a, DevicesEntities\Channels\Properties\Mapped $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($channelActors as $channelActor) {
			$actors[$channelActor->getIdentifier()] = sprintf(
				'%s [%s: %s, %s: %s, %s: %s]',
				($channelActor->getName() ?? $channelActor->getIdentifier()),
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.answers.device'),
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				($channelActor->getParent()->getChannel()->getDevice()->getName() ?? $channelActor->getParent()->getChannel()->getDevice()->getIdentifier()),
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.answers.channel'),
				($channelActor->getParent()->getChannel()->getName() ?? $channelActor->getParent()->getChannel()->getIdentifier()),
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.answers.property'),
				($channelActor->getParent()->getName() ?? $channelActor->getParent()->getIdentifier()),
			);
		}

		if (count($actors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.select.actorToUpdate',
			),
			array_values($actors),
			count($actors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($channel, $actors): DevicesEntities\Channels\Properties\Mapped {
				if ($answer === null) {
					throw new VirtualThermostatExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($actors))) {
					$answer = array_values($actors)[$answer];
				}

				$identifier = array_search($answer, $actors, true);

				if ($identifier !== false) {
					$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelMappedProperties();
					$findChannelPropertiesQuery->byIdentifier($identifier);
					$findChannelPropertiesQuery->forChannel($channel);

					$actor = $this->channelsPropertiesRepository->findOneBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Mapped::class,
					);

					if ($actor !== null) {
						return $actor;
					}
				}

				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$actor = $io->askQuestion($question);
		assert($actor instanceof DevicesEntities\Channels\Properties\Mapped);

		return $actor;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function askWhichSensor(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
	): DevicesEntities\Channels\Properties\Mapped|null
	{
		$sensors = [];

		$findChannelsQuery = new Queries\Entities\FindSensorChannels();
		$findChannelsQuery->forDevice($device);

		$channel = $this->channelsRepository->findOneBy($findChannelsQuery, Entities\Channels\Sensors::class);

		if ($channel === null) {
			return null;
		}

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelMappedProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$channelSensors = $this->channelsPropertiesRepository->findAllBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Mapped::class,
		);
		usort(
			$channelSensors,
			static fn (DevicesEntities\Channels\Properties\Mapped $a, DevicesEntities\Channels\Properties\Mapped $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($channelSensors as $channelSensor) {
			$sensors[$channelSensor->getIdentifier()] = sprintf(
				'%s [%s: %s, %s: %s, %s: %s]',
				($channelSensor->getName() ?? $channelSensor->getIdentifier()),
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.answers.device'),
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				($channelSensor->getParent()->getChannel()->getDevice()->getName() ?? $channelSensor->getParent()->getChannel()->getDevice()->getIdentifier()),
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.answers.channel'),
				($channelSensor->getParent()->getChannel()->getName() ?? $channelSensor->getParent()->getChannel()->getIdentifier()),
				(string) $this->translator->translate('//virtual-thermostat-addon.cmd.install.answers.property'),
				($channelSensor->getParent()->getName() ?? $channelSensor->getParent()->getIdentifier()),
			);
		}

		if (count($sensors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//virtual-thermostat-addon.cmd.install.questions.select.sensorToUpdate',
			),
			array_values($sensors),
			count($sensors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//virtual-thermostat-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($channel, $sensors): DevicesEntities\Channels\Properties\Mapped {
				if ($answer === null) {
					throw new VirtualThermostatExceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($sensors))) {
					$answer = array_values($sensors)[$answer];
				}

				$identifier = array_search($answer, $sensors, true);

				if ($identifier !== false) {
					$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelMappedProperties();
					$findChannelPropertiesQuery->byIdentifier($identifier);
					$findChannelPropertiesQuery->forChannel($channel);

					$sensor = $this->channelsPropertiesRepository->findOneBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Mapped::class,
					);

					if ($sensor !== null) {
						return $sensor;
					}
				}

				throw new VirtualThermostatExceptions\Runtime(
					sprintf(
						(string) $this->translator->translate(
							'//virtual-thermostat-addon.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$sensor = $io->askQuestion($question);
		assert($sensor instanceof DevicesEntities\Channels\Properties\Mapped);

		return $sensor;
	}

	/**
	 * @template T as DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Mapped
	 *
	 * @param class-string<T> $propertyType
	 *
	 * @return T
	 *
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function createOrUpdateProperty(
		string $propertyType,
		Utils\ArrayHash $data,
		DevicesEntities\Channels\Properties\Property|null $property = null,
	): DevicesEntities\Channels\Properties\Property
	{
		if ($property !== null && !$property instanceof $propertyType) {
			$this->channelsPropertiesManager->delete($property);

			$property = null;
		}

		$property = $property === null
			? $this->channelsPropertiesManager->create($data)
			: $this->channelsPropertiesManager->update($property, $data);

		assert($property instanceof $propertyType);

		return $property;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Query
	 */
	private function findChannelPropertyIdentifier(
		DevicesEntities\Channels\Channel $channel,
		VirtualThermostatTypes\ChannelPropertyIdentifier $prefix,
	): string
	{
		$identifierPattern = $prefix->value . '_%d';

		for ($i = 1; $i <= 100; $i++) {
			$identifier = sprintf($identifierPattern, $i);

			$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier($identifier);

			if ($this->channelsPropertiesRepository->getResultSet($findChannelPropertiesQuery)->isEmpty()) {
				return $identifier;
			}
		}

		throw new VirtualThermostatExceptions\InvalidState('Channel property identifier could not be created');
	}

}
