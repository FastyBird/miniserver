<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Drivers
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Drivers;

use DateTimeInterface;
use FastyBird\Addon\VirtualThermostat;
use FastyBird\Addon\VirtualThermostat\Exceptions as VirtualThermostatExceptions;
use FastyBird\Addon\VirtualThermostat\Helpers;
use FastyBird\Addon\VirtualThermostat\Types as VirtualThermostatTypes;
use FastyBird\Connector\Virtual\Documents as VirtualDocuments;
use FastyBird\Connector\Virtual\Drivers as VirtualDrivers;
use FastyBird\Connector\Virtual\Exceptions as VirtualExceptions;
use FastyBird\Connector\Virtual\Helpers as VirtualHelpers;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;
use FastyBird\Connector\Virtual\Queue as VirtualQueue;
use FastyBird\Core\Clock;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use React\Promise;
use Throwable;
use TypeError;
use ValueError;
use function array_filter;
use function array_key_exists;
use function array_sum;
use function assert;
use function boolval;
use function count;
use function floatval;
use function in_array;
use function intval;
use function is_bool;
use function is_numeric;
use function is_string;
use function max;
use function min;
use function preg_match;
use function sprintf;
use function str_starts_with;

/**
 * Thermostat service
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Drivers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Thermostat implements VirtualDrivers\Driver
{

	private const PROCESSING_DEBOUNCE_DELAY = 150.0;

	/** @var array<string, bool|null> */
	private array $heaters = [];

	/** @var array<string, bool|null> */
	private array $coolers = [];

	/** @var array<string, float|null> */
	private array $targetTemperature = [];

	/** @var array<string, float|null> */
	private array $currentTemperature = [];

	/** @var array<string, float|null> */
	private array $currentFloorTemperature = [];

	/** @var array<string, int|null> */
	private array $currentHumidity = [];

	/** @var array<string, bool|null> */
	private array $openingsState = [];

	private VirtualThermostatTypes\Preset|null $presetMode;

	private VirtualThermostatTypes\HvacMode|null $hvacMode;

	private bool $hasFloorTemperatureSensors = false;

	private bool $hasHumiditySensors = false;

	private bool $hasOpeningsSensors = false;

	private bool $connected = false;

	private DateTimeInterface|null $connectedAt = null;

	private DateTimeInterface|null $lastProcessedTime = null;

	public function __construct(
		private readonly DevicesDocuments\Devices\Device $device,
		private readonly Helpers\Device $deviceHelper,
		private readonly VirtualQueue\Queue $queue,
		private readonly VirtualHelpers\MessageBuilder $messageBuilder,
		private readonly VirtualThermostat\Logger $logger,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Clock\Clock $clock,
	)
	{
		$this->presetMode = VirtualThermostatTypes\Preset::MANUAL;
		$this->hvacMode = VirtualThermostatTypes\HvacMode::OFF;
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
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
	 * @throws VirtualExceptions\Runtime
	 */
	public function connect(): Promise\PromiseInterface
	{
		if (
			!$this->deviceHelper->hasRoomTemperatureSensors($this->device)
			|| (
				!$this->deviceHelper->hasHeaters($this->device)
				&& !$this->deviceHelper->hasCoolers($this->device)
			)
		) {
			return Promise\reject(
				new VirtualThermostatExceptions\InvalidState(
					'Thermostat has not configured all required actors or sensors',
				),
			);
		}

		foreach ($this->deviceHelper->getActors($this->device) as $actor) {
			$state = $this->channelPropertiesStatesManager->read(
				$actor,
				Sources\Addon::VIRTUAL_THERMOSTAT,
			);

			if (
				!$state instanceof DevicesDocuments\States\Channels\Properties\Property
				|| !$state->isValid()
			) {
				continue;
			}

			$actualValue = $actor instanceof DevicesDocuments\Channels\Properties\Dynamic
				? $state->getGet()->getActualValue()
				: $state->getRead()->getExpectedValue() ?? $state->getRead()->getActualValue();

			if (
				str_starts_with(
					$actor->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::HEATER_ACTOR->value,
				)
			) {
				$this->heaters[$actor->getId()->toString()] = is_bool($actualValue)
					? $actualValue
					: null;
			} elseif (
				str_starts_with(
					$actor->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::COOLER_ACTOR->value,
				)
			) {
				$this->coolers[$actor->getId()->toString()] = is_bool($actualValue)
					? $actualValue
					: null;
			}
		}

		$this->hasFloorTemperatureSensors = $this->deviceHelper->hasFloorTemperatureSensors($this->device);
		$this->hasHumiditySensors = $this->deviceHelper->hasRoomHumiditySensors($this->device);
		$this->hasOpeningsSensors = $this->deviceHelper->hasOpeningsSensors($this->device);

		foreach ($this->deviceHelper->getSensors($this->device) as $sensor) {
			$state = $this->channelPropertiesStatesManager->read(
				$sensor,
				Sources\Addon::VIRTUAL_THERMOSTAT,
			);

			if (
				!$state instanceof DevicesDocuments\States\Channels\Properties\Property
				|| !$state->isValid()
			) {
				continue;
			}

			$actualValue = $sensor instanceof DevicesDocuments\Channels\Properties\Dynamic
				? $state->getGet()->getActualValue()
				: $state->getRead()->getExpectedValue() ?? $state->getRead()->getActualValue();

			if (
				str_starts_with(
					$sensor->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
				)
			) {
				$this->currentTemperature[$sensor->getId()->toString()] = is_numeric($actualValue)
					? floatval($actualValue)
					: null;
			} elseif (
				$this->hasFloorTemperatureSensors
				&& str_starts_with(
					$sensor->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
				)
			) {
				$this->currentFloorTemperature[$sensor->getId()->toString()] = is_numeric($actualValue)
					? floatval($actualValue)
					: null;
			} elseif (
				$this->hasOpeningsSensors
				&& str_starts_with(
					$sensor->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::OPENING_SENSOR->value,
				)
			) {
				$this->openingsState[$sensor->getId()->toString()] = is_bool($actualValue)
					? $actualValue
					: null;
			} elseif (
				$this->hasHumiditySensors
				&& str_starts_with(
					$sensor->getIdentifier(),
					VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
				)
			) {
				$this->currentHumidity[$sensor->getId()->toString()] = is_numeric($actualValue)
					? intval($actualValue)
					: null;
			}
		}

		foreach ($this->deviceHelper->getPresetModes($this->device) as $mode) {
			$property = $this->deviceHelper->getTargetTemp($this->device, $mode);

			if ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				$state = $this->channelPropertiesStatesManager->read(
					$property,
					Sources\Addon::VIRTUAL_THERMOSTAT,
				);

				if (
					$state instanceof DevicesDocuments\States\Channels\Properties\Property
					&& is_numeric($state->getGet()->getActualValue())
				) {
					$this->targetTemperature[$mode->value] = floatval($state->getGet()->getActualValue());
				} else {
					$this->targetTemperature[$mode->value] = floatval(
						Utilities\Value::flattenValue(
							$property->getDefault() ?? VirtualThermostat\Entities\Devices\Device::TARGET_TEMPERATURE,
						),
					);

					$this->queue->append(
						$this->messageBuilder->create(
							VirtualQueue\Messages\StoreChannelPropertyState::class,
							[
								'connector' => $this->device->getConnector(),
								'device' => $this->device->getId(),
								'channel' => $property->getChannel(),
								'property' => $property->getId(),
								'value' => floatval(
									Utilities\Value::flattenValue(
										$property->getDefault() ?? VirtualThermostat\Entities\Devices\Device::TARGET_TEMPERATURE,
									),
								),
								'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
							],
						),
					);
				}

				$this->channelPropertiesStatesManager->setValidState(
					$property,
					true,
					Sources\Addon::VIRTUAL_THERMOSTAT,
				);
			}
		}

		if ($this->deviceHelper->getPresetMode($this->device) !== null) {
			$property = $this->deviceHelper->getPresetMode($this->device);

			$state = $this->channelPropertiesStatesManager->read(
				$property,
				Sources\Addon::VIRTUAL_THERMOSTAT,
			);

			if (
				$state instanceof DevicesDocuments\States\Channels\Properties\Property
				&& VirtualThermostatTypes\Preset::tryFrom(
					Utilities\Value::toString($state->getGet()->getActualValue()) ?? '',
				) !== null
			) {
				$this->presetMode = VirtualThermostatTypes\Preset::from(
					Utilities\Value::toString($state->getGet()->getActualValue(), true),
				);
			} else {
				$this->presetMode = VirtualThermostatTypes\Preset::from(
					Utilities\Value::toString(
						$property->getDefault() ?? VirtualThermostatTypes\Preset::MANUAL->value,
						true,
					),
				);

				$this->queue->append(
					$this->messageBuilder->create(
						VirtualQueue\Messages\StoreChannelPropertyState::class,
						[
							'connector' => $this->device->getConnector(),
							'device' => $this->device->getId(),
							'channel' => $property->getChannel(),
							'property' => $property->getId(),
							'value' => Utilities\Value::toString(
								$property->getDefault() ?? VirtualThermostatTypes\Preset::MANUAL->value,
								true,
							),
							'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
						],
					),
				);
			}

			$this->channelPropertiesStatesManager->setValidState(
				$property,
				true,
				Sources\Addon::VIRTUAL_THERMOSTAT,
			);
		}

		if ($this->deviceHelper->getHvacMode($this->device) !== null) {
			$property = $this->deviceHelper->getHvacMode($this->device);

			$state = $this->channelPropertiesStatesManager->read(
				$property,
				Sources\Addon::VIRTUAL_THERMOSTAT,
			);

			if (
				$state instanceof DevicesDocuments\States\Channels\Properties\Property
				&& VirtualThermostatTypes\HvacMode::tryFrom(
					Utilities\Value::toString($state->getGet()->getActualValue()) ?? '',
				) !== null
			) {
				$this->hvacMode = VirtualThermostatTypes\HvacMode::from(
					Utilities\Value::toString($state->getGet()->getActualValue(), true),
				);
			} else {
				$this->hvacMode = VirtualThermostatTypes\HvacMode::from(
					Utilities\Value::toString(
						$property->getDefault() ?? VirtualThermostatTypes\HvacMode::OFF->value,
						true,
					),
				);

				$this->queue->append(
					$this->messageBuilder->create(
						VirtualQueue\Messages\StoreChannelPropertyState::class,
						[
							'connector' => $this->device->getConnector(),
							'device' => $this->device->getId(),
							'channel' => $property->getChannel(),
							'property' => $property->getId(),
							'value' => Utilities\Value::toString(
								$property->getDefault() ?? VirtualThermostatTypes\HvacMode::OFF->value,
								true,
							),
							'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
						],
					),
				);
			}

			$this->channelPropertiesStatesManager->setValidState(
				$property,
				true,
				Sources\Addon::VIRTUAL_THERMOSTAT,
			);
		}

		$this->connected = true;
		$this->connectedAt = $this->clock->getNow();

		return Promise\resolve(true);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function disconnect(): Promise\PromiseInterface
	{
		$this->setActorState(false, false);

		$this->currentTemperature = [];
		$this->currentFloorTemperature = [];

		$this->connected = false;
		$this->connectedAt = null;

		return Promise\resolve(true);
	}

	public function isConnected(): bool
	{
		return $this->connected && $this->connectedAt !== null;
	}

	public function isConnecting(): bool
	{
		return false;
	}

	public function getLastConnectAttempt(): DateTimeInterface|null
	{
		return $this->connectedAt;
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function process(): Promise\PromiseInterface
	{
		if ($this->connected === false) {
			return Promise\reject(new VirtualThermostatExceptions\InvalidState('Thermostat device is not connected'));
		}

		$this->lastProcessedTime = $this->clock->getNow();

		if ($this->hvacMode === null || $this->presetMode === null) {
			$this->stop('Thermostat mode is not configured');

			return Promise\resolve(false);
		}

		if (
			!array_key_exists($this->presetMode->value, $this->targetTemperature)
			|| $this->targetTemperature[$this->presetMode->value] === null
		) {
			$this->stop('Target temperature is not configured');

			return Promise\resolve(false);
		}

		$targetTemp = $this->targetTemperature[$this->presetMode->value];

		$targetTempLow = $targetTemp - ($this->deviceHelper->getLowTargetTempTolerance($this->device) ?? 0);
		$targetTempHigh = $targetTemp + ($this->deviceHelper->getHighTargetTempTolerance($this->device) ?? 0);

		if ($targetTempLow > $targetTempHigh) {
			$this->setActorState(false, false);

			$this->connected = false;

			return Promise\reject(
				new VirtualThermostatExceptions\InvalidState('Target temperature boundaries are wrongly configured'),
			);
		}

		$measuredTemp = array_filter(
			$this->currentTemperature,
			static fn (float|null $temp): bool => $temp !== null,
		);

		if ($measuredTemp === []) {
			$this->stop('Thermostat temperature sensors has invalid values');

			return Promise\resolve(false);
		}

		$minCurrentTemp = min($measuredTemp);
		$maxCurrentTemp = max($measuredTemp);

		$this->queue->append(
			$this->messageBuilder->create(
				VirtualQueue\Messages\StoreChannelPropertyState::class,
				[
					'connector' => $this->device->getConnector(),
					'device' => $this->device->getId(),
					'channel' => $this->deviceHelper->getState($this->device)->getId(),
					'property' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_TEMPERATURE->value,
					'value' => array_sum($measuredTemp) / count($measuredTemp),
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
				],
			),
		);

		if ($this->hasFloorTemperatureSensors) {
			$measuredFloorTemp = array_filter(
				$this->currentFloorTemperature,
				static fn (float|null $temp): bool => $temp !== null,
			);

			if ($measuredFloorTemp === []) {
				$this->stop('Thermostat floor temperature sensors has invalid values');

				return Promise\resolve(false);
			}

			$this->queue->append(
				$this->messageBuilder->create(
					VirtualQueue\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector(),
						'device' => $this->device->getId(),
						'channel' => $this->deviceHelper->getState($this->device)->getId(),
						'property' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_FLOOR_TEMPERATURE->value,
						'value' => array_sum($measuredFloorTemp) / count($measuredFloorTemp),
						'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
					],
				),
			);

			$this->queue->append(
				$this->messageBuilder->create(
					VirtualQueue\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector(),
						'device' => $this->device->getId(),
						'channel' => $this->deviceHelper->getState($this->device)->getId(),
						'property' => VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_OVERHEATING->value,
						'value' => $this->isFloorOverHeating(),
						'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
					],
				),
			);
		}

		if ($this->hasHumiditySensors) {
			$measuredHum = array_filter(
				$this->currentHumidity,
				static fn (int|null $hum): bool => $hum !== null,
			);

			$this->queue->append(
				$this->messageBuilder->create(
					VirtualQueue\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector(),
						'device' => $this->device->getId(),
						'channel' => $this->deviceHelper->getState($this->device)->getId(),
						'property' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_ROOM_HUMIDITY->value,
						'value' => $measuredHum !== [] ? array_sum($measuredHum) / count($measuredHum) : null,
						'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
					],
				),
			);
		}

		if ($this->hasOpeningsSensors) {
			$this->queue->append(
				$this->messageBuilder->create(
					VirtualQueue\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector(),
						'device' => $this->device->getId(),
						'channel' => $this->deviceHelper->getState($this->device)->getId(),
						'property' => VirtualThermostatTypes\ChannelPropertyIdentifier::CURRENT_OPENINGS_STATE->value,
						'value' => $this->isOpeningsClosed()
							? VirtualThermostatTypes\OpeningStatePayload::CLOSED->value
							: VirtualThermostatTypes\OpeningStatePayload::OPENED->value,
						'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
					],
				),
			);
		}

		if (!$this->isOpeningsClosed()) {
			$this->setActorState(false, false);

			return Promise\resolve(true);
		}

		if ($this->hvacMode === VirtualThermostatTypes\HvacMode::OFF) {
			$this->setActorState(false, false);

			return Promise\resolve(true);
		}

		if ($this->isFloorOverHeating()) {
			$this->setActorState(false, $this->isCooling());

			return Promise\resolve(true);
		}

		if ($this->hvacMode === VirtualThermostatTypes\HvacMode::HEAT) {
			if (!$this->deviceHelper->hasHeaters($this->device)) {
				$this->setActorState(false, false);

				$this->connected = false;

				return Promise\reject(
					new VirtualThermostatExceptions\InvalidState('Thermostat has not configured any heater actor'),
				);
			}

			if ($maxCurrentTemp >= $targetTempHigh) {
				$this->setActorState(false, false);
			} elseif ($minCurrentTemp <= $targetTempLow) {
				$this->setActorState(true, false);
			}
		} elseif ($this->hvacMode === VirtualThermostatTypes\HvacMode::COOL) {
			if (!$this->deviceHelper->hasCoolers($this->device)) {
				$this->setActorState(false, false);

				$this->connected = false;

				return Promise\reject(
					new VirtualThermostatExceptions\InvalidState('Thermostat has not configured any cooler actor'),
				);
			}

			if ($maxCurrentTemp >= $targetTempHigh) {
				$this->setActorState(false, true);
			} elseif ($minCurrentTemp <= $targetTempLow) {
				$this->setActorState(false, false);
			}
		} elseif ($this->hvacMode === VirtualThermostatTypes\HvacMode::AUTO) {
			$heatingThresholdTemp = $this->deviceHelper->getHeatingThresholdTemp($this->device, $this->presetMode);
			$coolingThresholdTemp = $this->deviceHelper->getCoolingThresholdTemp($this->device, $this->presetMode);

			if (
				$heatingThresholdTemp === null
				|| $coolingThresholdTemp === null
				|| $heatingThresholdTemp >= $coolingThresholdTemp
				|| $heatingThresholdTemp > $targetTemp
				|| $coolingThresholdTemp < $targetTemp
			) {
				$this->connected = false;

				return Promise\reject(
					new VirtualThermostatExceptions\InvalidState(
						'Heating and cooling threshold temperatures are wrongly configured',
					),
				);
			}

			if ($minCurrentTemp <= $heatingThresholdTemp) {
				$this->setActorState(true, false);
			} elseif ($maxCurrentTemp >= $coolingThresholdTemp) {
				$this->setActorState(false, true);
			} elseif (
				$this->isHeating()
				&& !$this->isCooling()
				&& $maxCurrentTemp >= $targetTempHigh
			) {
				$this->setActorState(false, false);
			} elseif (
				!$this->isHeating()
				&& $this->isCooling()
				&& $minCurrentTemp <= $targetTempLow
			) {
				$this->setActorState(false, false);
			} elseif ($this->isHeating() && $this->isCooling()) {
				$this->setActorState(false, false);
			}
		}

		return Promise\resolve(true);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function writeState(
		DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Channels\Properties\Dynamic $property,
		bool|float|int|string|DateTimeInterface|Payloads\Payload|null $expectedValue,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->connected === false) {
			$deferred->reject(new VirtualThermostatExceptions\InvalidArgument('Thermostat device is not connected'));

		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			$findChannelQuery = new VirtualQueries\Configuration\FindChannels();
			$findChannelQuery->byId($property->getChannel());

			$channel = $this->channelsConfigurationRepository->findOneBy(
				$findChannelQuery,
				VirtualDocuments\Channels\Channel::class,
			);

			if ($channel === null) {
				$deferred->reject(
					new VirtualThermostatExceptions\InvalidArgument('Channel for provided property could not be found'),
				);

			} elseif ($channel->getIdentifier() === VirtualThermostatTypes\ChannelIdentifier::STATE->value) {
				if ($property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::PRESET_MODE->value) {
					if (
						is_string($expectedValue)
						&& VirtualThermostatTypes\Preset::tryFrom($expectedValue) !== null
					) {
						$this->presetMode = VirtualThermostatTypes\Preset::from($expectedValue);

						$this->queue->append(
							$this->messageBuilder->create(
								VirtualQueue\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $this->device->getConnector(),
									'device' => $this->device->getId(),
									'channel' => $this->deviceHelper->getState($this->device)->getId(),
									'property' => $property->getId(),
									'value' => $expectedValue,
									'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
								],
							),
						);

						$this->process()
							->then(static function () use ($deferred): void {
								$deferred->resolve(true);
							})
							->catch(static function (Throwable $ex) use ($deferred): void {
								$deferred->reject($ex);
							});

					} else {
						$deferred->reject(
							new VirtualThermostatExceptions\InvalidArgument('Provided value is not valid'),
						);
					}
				} elseif ($property->getIdentifier() === VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_MODE->value) {
					if (
						is_string($expectedValue)
						&& VirtualThermostatTypes\HvacMode::tryFrom($expectedValue) !== null
					) {
						$this->hvacMode = VirtualThermostatTypes\HvacMode::from($expectedValue);

						$this->queue->append(
							$this->messageBuilder->create(
								VirtualQueue\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $this->device->getConnector(),
									'device' => $this->device->getId(),
									'channel' => $this->deviceHelper->getState($this->device)->getId(),
									'property' => $property->getId(),
									'value' => $expectedValue,
									'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
								],
							),
						);

						$this->process()
							->then(static function () use ($deferred): void {
								$deferred->resolve(true);
							})
							->catch(static function (Throwable $ex) use ($deferred): void {
								$deferred->reject($ex);
							});

					} else {
						$deferred->reject(
							new VirtualThermostatExceptions\InvalidArgument('Provided value is not valid'),
						);
					}
				} else {
					$deferred->reject(new VirtualThermostatExceptions\InvalidArgument(sprintf(
						'Provided property: %s is unsupported',
						$property->getIdentifier(),
					)));
				}
			} elseif (
				preg_match(
					VirtualThermostat\Constants::PRESET_CHANNEL_PATTERN,
					$channel->getIdentifier(),
					$matches,
				) === 1
				&& array_key_exists('preset', $matches)
			) {
				if (
					VirtualThermostatTypes\Preset::tryFrom($matches['preset']) !== null
					&& is_numeric($expectedValue)
				) {
					$this->targetTemperature[$matches['preset']] = floatval($expectedValue);

					$this->queue->append(
						$this->messageBuilder->create(
							VirtualQueue\Messages\StoreChannelPropertyState::class,
							[
								'connector' => $this->device->getConnector(),
								'device' => $this->device->getId(),
								'channel' => $channel->getId(),
								'property' => $property->getId(),
								'value' => $expectedValue,
								'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
							],
						),
					);

					if ($matches['preset'] === $this->presetMode?->value) {
						$this->process()
							->then(static function () use ($deferred): void {
								$deferred->resolve(true);
							})
							->catch(static function (Throwable $ex) use ($deferred): void {
								$deferred->reject($ex);
							});
					} else {
						$deferred->resolve(true);
					}
				} else {
					$deferred->reject(new VirtualThermostatExceptions\InvalidArgument('Provided value is not valid'));
				}
			} else {
				$deferred->reject(new VirtualThermostatExceptions\InvalidArgument('Provided property is unsupported'));
			}
		} else {
			$deferred->reject(new VirtualThermostatExceptions\InvalidArgument('Provided property type is unsupported'));
		}

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function notifyState(
		DevicesDocuments\Devices\Properties\Mapped|DevicesDocuments\Channels\Properties\Mapped $property,
		bool|float|int|string|DateTimeInterface|Payloads\Payload|null $actualValue,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->connected === false) {
			$deferred->reject(new VirtualThermostatExceptions\InvalidArgument('Thermostat device is not connected'));

		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
			$findChannelQuery = new VirtualQueries\Configuration\FindChannels();
			$findChannelQuery->byId($property->getChannel());

			$channel = $this->channelsConfigurationRepository->findOneBy(
				$findChannelQuery,
				VirtualDocuments\Channels\Channel::class,
			);

			if ($channel === null) {
				$deferred->reject(
					new VirtualThermostatExceptions\InvalidArgument('Channel for provided property could not be found'),
				);

			} elseif ($channel->getIdentifier() === VirtualThermostatTypes\ChannelIdentifier::ACTORS->value) {
				if (
					str_starts_with(
						$property->getIdentifier(),
						VirtualThermostatTypes\ChannelPropertyIdentifier::HEATER_ACTOR->value,
					)
					&& (is_bool($actualValue) || $actualValue === null)
				) {
					$this->heaters[$property->getId()->toString()] = $actualValue;

					if (
						$this->lastProcessedTime instanceof DateTimeInterface
						&& (
							$this->clock->getNow()->getTimestamp() - $this->lastProcessedTime->getTimestamp()
							< self::PROCESSING_DEBOUNCE_DELAY
						)
					) {
						$deferred->resolve(true);
					} else {
						$this->process()
							->then(static function () use ($deferred): void {
								$deferred->resolve(true);
							})
							->catch(static function (Throwable $ex) use ($deferred): void {
								$deferred->reject($ex);
							});
					}
				} elseif (
					str_starts_with(
						$property->getIdentifier(),
						VirtualThermostatTypes\ChannelPropertyIdentifier::COOLER_ACTOR->value,
					)
					&& (is_bool($actualValue) || $actualValue === null)
				) {
					$this->coolers[$property->getId()->toString()] = $actualValue;

					if (
						$this->lastProcessedTime instanceof DateTimeInterface
						&& (
							$this->clock->getNow()->getTimestamp() - $this->lastProcessedTime->getTimestamp()
							< self::PROCESSING_DEBOUNCE_DELAY
						)
					) {
						$deferred->resolve(true);
					} else {
						$this->process()
							->then(static function () use ($deferred): void {
								$deferred->resolve(true);
							})
							->catch(static function (Throwable $ex) use ($deferred): void {
								$deferred->reject($ex);
							});
					}
				} else {
					$deferred->reject(new VirtualThermostatExceptions\InvalidArgument(sprintf(
						'Provided actor type: %s is unsupported',
						$property->getIdentifier(),
					)));
				}
			} elseif ($channel->getIdentifier() === VirtualThermostatTypes\ChannelIdentifier::SENSORS->value) {
				if (
					str_starts_with(
						$property->getIdentifier(),
						VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
					)
					&& (is_numeric($actualValue) || $actualValue === null)
				) {
					$this->currentTemperature[$property->getId()->toString()] = floatval($actualValue);

					if (
						$this->lastProcessedTime instanceof DateTimeInterface
						&& (
							$this->clock->getNow()->getTimestamp() - $this->lastProcessedTime->getTimestamp()
							< self::PROCESSING_DEBOUNCE_DELAY
						)
					) {
						$deferred->resolve(true);
					} else {
						$this->process()
							->then(static function () use ($deferred): void {
								$deferred->resolve(true);
							})
							->catch(static function (Throwable $ex) use ($deferred): void {
								$deferred->reject($ex);
							});
					}
				} elseif (
					str_starts_with(
						$property->getIdentifier(),
						VirtualThermostatTypes\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
					)
					&& (is_numeric($actualValue) || $actualValue === null)
				) {
					if ($this->hasFloorTemperatureSensors) {
						$this->currentFloorTemperature[$property->getId()->toString()] = floatval($actualValue);

						if (
							$this->lastProcessedTime instanceof DateTimeInterface
							&& (
								$this->clock->getNow()->getTimestamp() - $this->lastProcessedTime->getTimestamp()
								< self::PROCESSING_DEBOUNCE_DELAY
							)
						) {
							$deferred->resolve(true);
						} else {
							$this->process()
								->then(static function () use ($deferred): void {
									$deferred->resolve(true);
								})
								->catch(static function (Throwable $ex) use ($deferred): void {
									$deferred->reject($ex);
								});
						}
					} else {
						$deferred->reject(
							new VirtualThermostatExceptions\InvalidArgument(
								'Thermostat does not support floor temperature sensors',
							),
						);
					}
				} elseif (
					str_starts_with(
						$property->getIdentifier(),
						VirtualThermostatTypes\ChannelPropertyIdentifier::OPENING_SENSOR->value,
					)
					&& (is_bool($actualValue) || $actualValue === null)
				) {
					if ($this->hasOpeningsSensors) {
						$this->openingsState[$property->getId()->toString()] = $actualValue;

						if (
							$this->lastProcessedTime instanceof DateTimeInterface
							&& (
								$this->clock->getNow()->getTimestamp() - $this->lastProcessedTime->getTimestamp()
								< self::PROCESSING_DEBOUNCE_DELAY
							)
						) {
							$deferred->resolve(true);
						} else {
							$this->process()
								->then(static function () use ($deferred): void {
									$deferred->resolve(true);
								})
								->catch(static function (Throwable $ex) use ($deferred): void {
									$deferred->reject($ex);
								});
						}
					} else {
						$deferred->reject(
							new VirtualThermostatExceptions\InvalidArgument(
								'Thermostat does not support openings sensors',
							),
						);
					}
				} elseif (
					str_starts_with(
						$property->getIdentifier(),
						VirtualThermostatTypes\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
					)
					&& (is_numeric($actualValue) || $actualValue === null)
				) {
					if ($this->hasHumiditySensors) {
						$this->currentHumidity[$property->getId()->toString()] = intval($actualValue);

						if (
							$this->lastProcessedTime instanceof DateTimeInterface
							&& (
								$this->clock->getNow()->getTimestamp() - $this->lastProcessedTime->getTimestamp()
								< self::PROCESSING_DEBOUNCE_DELAY
							)
						) {
							$deferred->resolve(true);
						} else {
							$this->process()
								->then(static function () use ($deferred): void {
									$deferred->resolve(true);
								})
								->catch(static function (Throwable $ex) use ($deferred): void {
									$deferred->reject($ex);
								});
						}
					} else {
						$deferred->reject(
							new VirtualThermostatExceptions\InvalidArgument(
								'Thermostat does not support humidity sensors sensors',
							),
						);
					}
				} else {
					$deferred->reject(new VirtualThermostatExceptions\InvalidArgument(sprintf(
						'Provided sensor type: %s is unsupported',
						$property->getIdentifier(),
					)));
				}
			} else {
				$deferred->reject(
					new VirtualThermostatExceptions\InvalidArgument('Provided property channel is unsupported'),
				);
			}
		} else {
			$deferred->reject(new VirtualThermostatExceptions\InvalidArgument('Provided property type is unsupported'));
		}

		return $deferred->promise();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function setActorState(bool $heaters, bool $coolers): void
	{
		if (!$this->deviceHelper->hasHeaters($this->device)) {
			$heaters = false;
		}

		if (!$this->deviceHelper->hasCoolers($this->device)) {
			$coolers = false;
		}

		$this->setHeaterState($heaters);
		$this->setCoolerState($coolers);

		$state = VirtualThermostatTypes\HvacState::OFF;

		if ($heaters && !$coolers) {
			$state = VirtualThermostatTypes\HvacState::HEATING;
		} elseif (!$heaters && $coolers) {
			$state = VirtualThermostatTypes\HvacState::COOLING;
		}

		$this->queue->append(
			$this->messageBuilder->create(
				VirtualQueue\Messages\StoreChannelPropertyState::class,
				[
					'connector' => $this->device->getConnector(),
					'device' => $this->device->getId(),
					'channel' => $this->deviceHelper->getState($this->device)->getId(),
					'property' => VirtualThermostatTypes\ChannelPropertyIdentifier::HVAC_STATE->value,
					'value' => $state->value,
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
				],
			),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function setHeaterState(bool $state): void
	{
		if ($state && $this->isFloorOverHeating()) {
			$this->setHeaterState(false);

			$this->logger->warning(
				'Floor is overheating. Turning off heaters actors',
				[
					'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
					'type' => 'thermostat-driver',
					'connector' => [
						'id' => $this->device->getConnector()->toString(),
					],
					'device' => [
						'id' => $this->device->getId()->toString(),
					],
				],
			);

			return;
		}

		foreach ($this->deviceHelper->getActors($this->device) as $actor) {
			assert($actor instanceof DevicesDocuments\Channels\Properties\Mapped);

			if (!str_starts_with(
				$actor->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::HEATER_ACTOR->value,
			)) {
				continue;
			}

			if ($actor->getDataType() === ValuesTypes\DataType::BOOLEAN) {
				$state = boolval($state);
			} elseif ($actor->getDataType() === ValuesTypes\DataType::SWITCH) {
				$state = $state === true ? Payloads\Switcher::ON : Payloads\Switcher::OFF;
			}

			$this->queue->append(
				$this->messageBuilder->create(
					VirtualQueue\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector(),
						'device' => $this->device->getId(),
						'channel' => $actor->getChannel(),
						'property' => $actor->getId(),
						'value' => $state,
						'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
					],
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualExceptions\Runtime
	 */
	private function setCoolerState(bool $state): void
	{
		foreach ($this->deviceHelper->getActors($this->device) as $actor) {
			assert($actor instanceof DevicesDocuments\Channels\Properties\Mapped);

			if (!str_starts_with(
				$actor->getIdentifier(),
				VirtualThermostatTypes\ChannelPropertyIdentifier::COOLER_ACTOR->value,
			)) {
				continue;
			}

			if ($actor->getDataType() === ValuesTypes\DataType::BOOLEAN) {
				$state = boolval($state);
			} elseif ($actor->getDataType() === ValuesTypes\DataType::SWITCH) {
				$state = $state === true ? Payloads\Switcher::ON : Payloads\Switcher::OFF;
			}

			$this->queue->append(
				$this->messageBuilder->create(
					VirtualQueue\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector(),
						'device' => $this->device->getId(),
						'channel' => $actor->getChannel(),
						'property' => $actor->getId(),
						'value' => $state,
						'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
					],
				),
			);
		}
	}

	private function isHeating(): bool
	{
		return in_array(true, $this->heaters, true);
	}

	private function isCooling(): bool
	{
		return in_array(true, $this->coolers, true);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function isFloorOverHeating(): bool
	{
		if ($this->hasFloorTemperatureSensors) {
			$measuredFloorTemps = array_filter(
				$this->currentFloorTemperature,
				static fn (float|null $temp): bool => $temp !== null,
			);

			if ($measuredFloorTemps === []) {
				$this->logger->warning(
					'Floor sensors are not provided values. Floor could not be protected',
					[
						'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
						'type' => 'thermostat-driver',
						'connector' => [
							'id' => $this->device->getConnector()->toString(),
						],
						'device' => [
							'id' => $this->device->getId()->toString(),
						],
					],
				);

				return true;
			}

			$maxFloorCurrentTemp = max($measuredFloorTemps);

			if ($maxFloorCurrentTemp >= $this->deviceHelper->getMaximumFloorTemp($this->device)) {
				return true;
			}
		}

		return false;
	}

	private function isOpeningsClosed(): bool
	{
		if ($this->hasOpeningsSensors) {
			return !in_array(true, $this->openingsState, true);
		}

		return true;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function stop(string $reason): void
	{
		$this->setActorState(false, false);

		$this->connected = false;

		$this->logger->warning(
			$reason,
			[
				'source' => Sources\Addon::VIRTUAL_THERMOSTAT->value,
				'type' => 'thermostat-driver',
				'connector' => [
					'id' => $this->device->getConnector()->toString(),
				],
				'device' => [
					'id' => $this->device->getId()->toString(),
				],
			],
		);
	}

}
