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
use FastyBird\Addon\VirtualThermostat\Exceptions;
use FastyBird\Addon\VirtualThermostat\Helpers;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Documents as VirtualDocuments;
use FastyBird\Connector\Virtual\Drivers as VirtualDrivers;
use FastyBird\Connector\Virtual\Exceptions as VirtualExceptions;
use FastyBird\Connector\Virtual\Helpers as VirtualHelpers;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;
use FastyBird\Connector\Virtual\Queue as VirtualQueue;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Core\Types\Metadata as MetadataTypes;
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

	private Types\Preset|null $presetMode;

	private Types\HvacMode|null $hvacMode;

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
		private readonly DateTimeFactory\Clock $clock,
	)
	{
		$this->presetMode = Types\Preset::MANUAL;
		$this->hvacMode = Types\HvacMode::OFF;
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
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
				new Exceptions\InvalidState('Thermostat has not configured all required actors or sensors'),
			);
		}

		foreach ($this->deviceHelper->getActors($this->device) as $actor) {
			$state = $this->channelPropertiesStatesManager->read(
				$actor,
				MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
					Types\ChannelPropertyIdentifier::HEATER_ACTOR->value,
				)
			) {
				$this->heaters[$actor->getId()->toString()] = is_bool($actualValue)
					? $actualValue
					: null;
			} elseif (
				str_starts_with(
					$actor->getIdentifier(),
					Types\ChannelPropertyIdentifier::COOLER_ACTOR->value,
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
				MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
					Types\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
				)
			) {
				$this->currentTemperature[$sensor->getId()->toString()] = is_numeric($actualValue)
					? floatval($actualValue)
					: null;
			} elseif (
				$this->hasFloorTemperatureSensors
				&& str_starts_with(
					$sensor->getIdentifier(),
					Types\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
				)
			) {
				$this->currentFloorTemperature[$sensor->getId()->toString()] = is_numeric($actualValue)
					? floatval($actualValue)
					: null;
			} elseif (
				$this->hasOpeningsSensors
				&& str_starts_with(
					$sensor->getIdentifier(),
					Types\ChannelPropertyIdentifier::OPENING_SENSOR->value,
				)
			) {
				$this->openingsState[$sensor->getId()->toString()] = is_bool($actualValue)
					? $actualValue
					: null;
			} elseif (
				$this->hasHumiditySensors
				&& str_starts_with(
					$sensor->getIdentifier(),
					Types\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
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
					MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
				);

				if (
					$state instanceof DevicesDocuments\States\Channels\Properties\Property
					&& is_numeric($state->getGet()->getActualValue())
				) {
					$this->targetTemperature[$mode->value] = floatval($state->getGet()->getActualValue());
				} else {
					$this->targetTemperature[$mode->value] = floatval(
						ToolsUtilities\Value::flattenValue(
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
									ToolsUtilities\Value::flattenValue(
										$property->getDefault() ?? VirtualThermostat\Entities\Devices\Device::TARGET_TEMPERATURE,
									),
								),
								'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
							],
						),
					);
				}

				$this->channelPropertiesStatesManager->setValidState(
					$property,
					true,
					MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
				);
			}
		}

		if ($this->deviceHelper->getPresetMode($this->device) !== null) {
			$property = $this->deviceHelper->getPresetMode($this->device);

			$state = $this->channelPropertiesStatesManager->read(
				$property,
				MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
			);

			if (
				$state instanceof DevicesDocuments\States\Channels\Properties\Property
				&& Types\Preset::tryFrom(
					ToolsUtilities\Value::toString($state->getGet()->getActualValue()) ?? '',
				) !== null
			) {
				$this->presetMode = Types\Preset::from(
					ToolsUtilities\Value::toString($state->getGet()->getActualValue(), true),
				);
			} else {
				$this->presetMode = Types\Preset::from(
					ToolsUtilities\Value::toString($property->getDefault() ?? Types\Preset::MANUAL->value, true),
				);

				$this->queue->append(
					$this->messageBuilder->create(
						VirtualQueue\Messages\StoreChannelPropertyState::class,
						[
							'connector' => $this->device->getConnector(),
							'device' => $this->device->getId(),
							'channel' => $property->getChannel(),
							'property' => $property->getId(),
							'value' => ToolsUtilities\Value::toString(
								$property->getDefault() ?? Types\Preset::MANUAL->value,
								true,
							),
							'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
						],
					),
				);
			}

			$this->channelPropertiesStatesManager->setValidState(
				$property,
				true,
				MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
			);
		}

		if ($this->deviceHelper->getHvacMode($this->device) !== null) {
			$property = $this->deviceHelper->getHvacMode($this->device);

			$state = $this->channelPropertiesStatesManager->read(
				$property,
				MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
			);

			if (
				$state instanceof DevicesDocuments\States\Channels\Properties\Property
				&& Types\HvacMode::tryFrom(
					ToolsUtilities\Value::toString($state->getGet()->getActualValue()) ?? '',
				) !== null
			) {
				$this->hvacMode = Types\HvacMode::from(
					ToolsUtilities\Value::toString($state->getGet()->getActualValue(), true),
				);
			} else {
				$this->hvacMode = Types\HvacMode::from(
					ToolsUtilities\Value::toString($property->getDefault() ?? Types\HvacMode::OFF->value, true),
				);

				$this->queue->append(
					$this->messageBuilder->create(
						VirtualQueue\Messages\StoreChannelPropertyState::class,
						[
							'connector' => $this->device->getConnector(),
							'device' => $this->device->getId(),
							'channel' => $property->getChannel(),
							'property' => $property->getId(),
							'value' => ToolsUtilities\Value::toString(
								$property->getDefault() ?? Types\HvacMode::OFF->value,
								true,
							),
							'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
						],
					),
				);
			}

			$this->channelPropertiesStatesManager->setValidState(
				$property,
				true,
				MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function process(): Promise\PromiseInterface
	{
		if ($this->connected === false) {
			return Promise\reject(new Exceptions\InvalidState('Thermostat device is not connected'));
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

			return Promise\reject(new Exceptions\InvalidState('Target temperature boundaries are wrongly configured'));
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
					'property' => Types\ChannelPropertyIdentifier::CURRENT_ROOM_TEMPERATURE->value,
					'value' => array_sum($measuredTemp) / count($measuredTemp),
					'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
						'property' => Types\ChannelPropertyIdentifier::CURRENT_FLOOR_TEMPERATURE->value,
						'value' => array_sum($measuredFloorTemp) / count($measuredFloorTemp),
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
						'property' => Types\ChannelPropertyIdentifier::FLOOR_OVERHEATING->value,
						'value' => $this->isFloorOverHeating(),
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
						'property' => Types\ChannelPropertyIdentifier::CURRENT_ROOM_HUMIDITY->value,
						'value' => $measuredHum !== [] ? array_sum($measuredHum) / count($measuredHum) : null,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
						'property' => Types\ChannelPropertyIdentifier::CURRENT_OPENINGS_STATE->value,
						'value' => $this->isOpeningsClosed() ? Types\OpeningStatePayload::CLOSED->value : Types\OpeningStatePayload::OPENED->value,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
					],
				),
			);
		}

		if (!$this->isOpeningsClosed()) {
			$this->setActorState(false, false);

			return Promise\resolve(true);
		}

		if ($this->hvacMode === Types\HvacMode::OFF) {
			$this->setActorState(false, false);

			return Promise\resolve(true);
		}

		if ($this->isFloorOverHeating()) {
			$this->setActorState(false, $this->isCooling());

			return Promise\resolve(true);
		}

		if ($this->hvacMode === Types\HvacMode::HEAT) {
			if (!$this->deviceHelper->hasHeaters($this->device)) {
				$this->setActorState(false, false);

				$this->connected = false;

				return Promise\reject(new Exceptions\InvalidState('Thermostat has not configured any heater actor'));
			}

			if ($maxCurrentTemp >= $targetTempHigh) {
				$this->setActorState(false, false);
			} elseif ($minCurrentTemp <= $targetTempLow) {
				$this->setActorState(true, false);
			}
		} elseif ($this->hvacMode === Types\HvacMode::COOL) {
			if (!$this->deviceHelper->hasCoolers($this->device)) {
				$this->setActorState(false, false);

				$this->connected = false;

				return Promise\reject(new Exceptions\InvalidState('Thermostat has not configured any cooler actor'));
			}

			if ($maxCurrentTemp >= $targetTempHigh) {
				$this->setActorState(false, true);
			} elseif ($minCurrentTemp <= $targetTempLow) {
				$this->setActorState(false, false);
			}
		} elseif ($this->hvacMode === Types\HvacMode::AUTO) {
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
					new Exceptions\InvalidState('Heating and cooling threshold temperatures are wrongly configured'),
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function writeState(
		DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Channels\Properties\Dynamic $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $expectedValue,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->connected === false) {
			$deferred->reject(new Exceptions\InvalidArgument('Thermostat device is not connected'));

		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			$findChannelQuery = new VirtualQueries\Configuration\FindChannels();
			$findChannelQuery->byId($property->getChannel());

			$channel = $this->channelsConfigurationRepository->findOneBy(
				$findChannelQuery,
				VirtualDocuments\Channels\Channel::class,
			);

			if ($channel === null) {
				$deferred->reject(
					new Exceptions\InvalidArgument('Channel for provided property could not be found'),
				);

			} elseif ($channel->getIdentifier() === Types\ChannelIdentifier::STATE->value) {
				if ($property->getIdentifier() === Types\ChannelPropertyIdentifier::PRESET_MODE->value) {
					if (
						is_string($expectedValue)
						&& Types\Preset::tryFrom($expectedValue) !== null
					) {
						$this->presetMode = Types\Preset::from($expectedValue);

						$this->queue->append(
							$this->messageBuilder->create(
								VirtualQueue\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $this->device->getConnector(),
									'device' => $this->device->getId(),
									'channel' => $this->deviceHelper->getState($this->device)->getId(),
									'property' => $property->getId(),
									'value' => $expectedValue,
									'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
						$deferred->reject(new Exceptions\InvalidArgument('Provided value is not valid'));
					}
				} elseif ($property->getIdentifier() === Types\ChannelPropertyIdentifier::HVAC_MODE->value) {
					if (
						is_string($expectedValue)
						&& Types\HvacMode::tryFrom($expectedValue) !== null
					) {
						$this->hvacMode = Types\HvacMode::from($expectedValue);

						$this->queue->append(
							$this->messageBuilder->create(
								VirtualQueue\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $this->device->getConnector(),
									'device' => $this->device->getId(),
									'channel' => $this->deviceHelper->getState($this->device)->getId(),
									'property' => $property->getId(),
									'value' => $expectedValue,
									'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
						$deferred->reject(new Exceptions\InvalidArgument('Provided value is not valid'));
					}
				} else {
					$deferred->reject(new Exceptions\InvalidArgument(sprintf(
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
					Types\Preset::tryFrom($matches['preset']) !== null
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
								'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
					$deferred->reject(new Exceptions\InvalidArgument('Provided value is not valid'));
				}
			} else {
				$deferred->reject(new Exceptions\InvalidArgument('Provided property is unsupported'));
			}
		} else {
			$deferred->reject(new Exceptions\InvalidArgument('Provided property type is unsupported'));
		}

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function notifyState(
		DevicesDocuments\Devices\Properties\Mapped|DevicesDocuments\Channels\Properties\Mapped $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $actualValue,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->connected === false) {
			$deferred->reject(new Exceptions\InvalidArgument('Thermostat device is not connected'));

		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
			$findChannelQuery = new VirtualQueries\Configuration\FindChannels();
			$findChannelQuery->byId($property->getChannel());

			$channel = $this->channelsConfigurationRepository->findOneBy(
				$findChannelQuery,
				VirtualDocuments\Channels\Channel::class,
			);

			if ($channel === null) {
				$deferred->reject(new Exceptions\InvalidArgument('Channel for provided property could not be found'));

			} elseif ($channel->getIdentifier() === Types\ChannelIdentifier::ACTORS->value) {
				if (
					str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::HEATER_ACTOR->value,
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
						Types\ChannelPropertyIdentifier::COOLER_ACTOR->value,
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
					$deferred->reject(new Exceptions\InvalidArgument(sprintf(
						'Provided actor type: %s is unsupported',
						$property->getIdentifier(),
					)));
				}
			} elseif ($channel->getIdentifier() === Types\ChannelIdentifier::SENSORS->value) {
				if (
					str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR->value,
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
						Types\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR->value,
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
							new Exceptions\InvalidArgument('Thermostat does not support floor temperature sensors'),
						);
					}
				} elseif (
					str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::OPENING_SENSOR->value,
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
							new Exceptions\InvalidArgument('Thermostat does not support openings sensors'),
						);
					}
				} elseif (
					str_starts_with(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR->value,
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
							new Exceptions\InvalidArgument('Thermostat does not support humidity sensors sensors'),
						);
					}
				} else {
					$deferred->reject(new Exceptions\InvalidArgument(sprintf(
						'Provided sensor type: %s is unsupported',
						$property->getIdentifier(),
					)));
				}
			} else {
				$deferred->reject(new Exceptions\InvalidArgument('Provided property channel is unsupported'));
			}
		} else {
			$deferred->reject(new Exceptions\InvalidArgument('Provided property type is unsupported'));
		}

		return $deferred->promise();
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
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

		$state = Types\HvacState::OFF;

		if ($heaters && !$coolers) {
			$state = Types\HvacState::HEATING;
		} elseif (!$heaters && $coolers) {
			$state = Types\HvacState::COOLING;
		}

		$this->queue->append(
			$this->messageBuilder->create(
				VirtualQueue\Messages\StoreChannelPropertyState::class,
				[
					'connector' => $this->device->getConnector(),
					'device' => $this->device->getId(),
					'channel' => $this->deviceHelper->getState($this->device)->getId(),
					'property' => Types\ChannelPropertyIdentifier::HVAC_STATE->value,
					'value' => $state->value,
					'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
				],
			),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
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
					'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
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
				Types\ChannelPropertyIdentifier::HEATER_ACTOR->value,
			)) {
				continue;
			}

			if ($actor->getDataType() === MetadataTypes\DataType::BOOLEAN) {
				$state = boolval($state);
			} elseif ($actor->getDataType() === MetadataTypes\DataType::SWITCH) {
				$state = $state === true ? MetadataTypes\Payloads\Switcher::ON : MetadataTypes\Payloads\Switcher::OFF;
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
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
					],
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws VirtualExceptions\Runtime
	 */
	private function setCoolerState(bool $state): void
	{
		foreach ($this->deviceHelper->getActors($this->device) as $actor) {
			assert($actor instanceof DevicesDocuments\Channels\Properties\Mapped);

			if (!str_starts_with(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::COOLER_ACTOR->value,
			)) {
				continue;
			}

			if ($actor->getDataType() === MetadataTypes\DataType::BOOLEAN) {
				$state = boolval($state);
			} elseif ($actor->getDataType() === MetadataTypes\DataType::SWITCH) {
				$state = $state === true ? MetadataTypes\Payloads\Switcher::ON : MetadataTypes\Payloads\Switcher::OFF;
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
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT,
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
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
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
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
				'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
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
