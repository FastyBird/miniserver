<?php declare(strict_types = 1);

/**
 * Discovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Connector\Shelly\ValueObjects;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\Datagram;
use React\Dns;
use React\EventLoop;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_map;
use function array_merge;
use function assert;
use function explode;
use function is_array;
use function is_bool;
use function is_string;
use function preg_match;
use function React\Async\async;
use function React\Async\await;
use function serialize;
use function strval;

/**
 * Devices discovery client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery
{

	use Nette\SmartObject;

	private const MDNS_ADDRESS = '224.0.0.251';

	private const MDNS_PORT = 5_353;

	private const MDNS_SEARCH_TIMEOUT = 30;

	private const PROCESS_RESULTS_TIMER = 0.1;

	private const MATCH_NAME = '/(?i)^(?P<type>shelly.+)-(?P<id>[0-9A-Fa-f]+)._(http|shelly)._tcp.local$/';

	private const MATCH_DOMAIN = '/(?i)^(?P<type>[0-9A-Za-z]+)-(?P<id>[0-9A-Fa-f]+).local$/';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS = '/^((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS_PORT = '/^(?P<address>((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5]))(:(?P<port>[0-9]{1,5}))?$/';

	/** @var array<string, ValueObjects\MdnsResult>  */
	private array $searchResult = [];

	/** @var array<string, ValueObjects\MdnsResult>  */
	private array $processedItems = [];

	private Dns\Protocol\Parser $parser;

	private Dns\Protocol\BinaryDumper $dumper;

	private Datagram\SocketInterface|null $server = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\Gen1HttpApiFactory $gen1HttpApiFactory,
		private readonly API\Gen2HttpApiFactory $gen2HttpApiFactory,
		private readonly Services\MulticastFactory $multicastFactory,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Loader $loader,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Queue\Queue $queue,
		private readonly Shelly\Logger $logger,
		private readonly ObjectMapper\Processing\Processor $objectMapper,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->parser = new Dns\Protocol\Parser();
		$this->dumper = new Dns\Protocol\BinaryDumper();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function discover(): void
	{
		$mode = $this->connectorHelper->getClientMode($this->connector);

		if ($mode === Types\ClientMode::CLOUD) {
			$this->discoverCloudDevices();

		} elseif ($mode === Types\ClientMode::LOCAL) {
			$this->discoverLocalDevices();
		}
	}

	public function disconnect(): void
	{
		$this->server?->close();
	}

	private function discoverLocalDevices(): void
	{
		try {
			$this->server = $this->multicastFactory->create(self::MDNS_ADDRESS, self::MDNS_PORT);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Invalid mDNS question response received',
				[
					'source' => Sources\Connector::SHELLY->value,
					'type' => 'discovery-client',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return;
		}

		$this->server->on('message', function ($message, string|null $sender = null): void {
			try {
				$response = $this->parser->parseMessage($message);

			} catch (InvalidArgumentException) {
				$this->logger->warning(
					'Invalid mDNS question response received',
					[
						'source' => Sources\Connector::SHELLY->value,
						'type' => 'discovery-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				return;
			}

			if ($response->tc) {
				$this->logger->warning(
					'The server set the truncated bit although we issued a TCP request',
					[
						'source' => Sources\Connector::SHELLY->value,
						'type' => 'discovery-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				return;
			}

			$serviceIpAddress = null;
			$serviceDomain = null;
			$serviceName = null;
			$serviceData = [];

			foreach ($response->answers as $answer) {
				if (
					$answer->type === Dns\Model\Message::TYPE_A
					&& preg_match(self::MATCH_DOMAIN, $answer->name) === 1
					&& is_string($answer->data)
					&& preg_match(self::MATCH_IP_ADDRESS, $answer->data) === 1
					&& $serviceIpAddress === null
				) {
					$serviceIpAddress = $answer->data;
				}

				if (
					$answer->type === Dns\Model\Message::TYPE_SRV
					&& preg_match(self::MATCH_NAME, $answer->name) === 1
					&& is_array($answer->data)
					&& array_key_exists('target', $answer->data)
					&& $serviceDomain === null
				) {
					$serviceDomain = $answer->data['target'];
				}

				if (
					$answer->type === Dns\Model\Message::TYPE_PTR
					&& is_string($answer->data)
					&& preg_match(self::MATCH_NAME, $answer->data) === 1
					&& $serviceName === null
				) {
					$serviceName = $answer->data;
				}

				if (
					$answer->type === Dns\Model\Message::TYPE_TXT
					&& preg_match(self::MATCH_NAME, $answer->name) === 1
					&& is_array($answer->data)
				) {
					foreach ($answer->data as $dataRow) {
						[$key, $value] = explode('=', $dataRow) + [null, null];

						$serviceData[$key] = $value;
					}
				}
			}

			if (
				$serviceIpAddress === null
				&& $sender !== null
				&& preg_match(self::MATCH_IP_ADDRESS_PORT, $sender, $matches) === 1
				&& array_key_exists('port', $matches)
			) {
				$serviceIpAddress = $matches['address'];
			}

			if ($serviceIpAddress !== null && $serviceName !== null) {
				try {
					$options = new ObjectMapper\Processing\Options();
					$options->setAllowUnknownFields();

					$serviceResult = $this->objectMapper->process(
						[
							'address' => $serviceIpAddress,
							'name' => $serviceName,
							'domain' => $serviceDomain,
							'data' => $serviceData,
						],
						ValueObjects\MdnsResult::class,
						$options,
					);
				} catch (ObjectMapper\Exception\InvalidData $ex) {
					$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
						new ObjectMapper\Printers\TypeToStringConverter(),
					);

					$this->logger->error(
						'Could not map mDNS result to message: ' . $errorPrinter->printError($ex),
						[
							'source' => Sources\Connector::SHELLY->value,
							'type' => 'discovery-client',
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
						],
					);

					return;
				}

				if (!array_key_exists(serialize($serviceResult), $this->searchResult)) {
					$this->searchResult[serialize($serviceResult)] = $serviceResult;
				}
			}
		});

		$this->eventLoop->futureTick(function (): void {
			$query = new Dns\Query\Query(
				'_http._tcp.local',
				Dns\Model\Message::TYPE_PTR,
				Dns\Model\Message::CLASS_IN,
			);

			$request = $this->dumper->toBinary(Dns\Model\Message::createRequestForQuery($query));

			$this->server?->send($request, self::MDNS_ADDRESS . ':' . self::MDNS_PORT);
		});

		// Processing handler
		$this->eventLoop->addPeriodicTimer(
			self::PROCESS_RESULTS_TIMER,
			async(function (): void {
				foreach ($this->searchResult as $item) {
					if (array_key_exists(serialize($item), $this->processedItems)) {
						continue;
					}

					$this->processedItems[serialize($item)] = $item;

					if (preg_match(self::MATCH_NAME, $item->getName(), $matches) === 1) {
						$generation = Types\DeviceGeneration::UNKNOWN;

						if (array_key_exists('gen', $item->getData()) && strval($item->getData()['gen']) === '2') {
							$generation = Types\DeviceGeneration::GENERATION_2;
						} elseif (
							array_key_exists('arch', $item->getData())
							&& strval(
								$item->getData()['arch'],
							) === 'esp8266'
						) {
							$generation = Types\DeviceGeneration::GENERATION_1;
						}

						$this->handleFoundLocalDevice(
							$generation,
							Utils\Strings::lower($matches['id']),
							Utils\Strings::lower($matches['type']),
							$item->getAddress(),
							$item->getDomain(),
						);
					}
				}
			}),
		);

		// Searching timeout
		$this->eventLoop->addTimer(
			self::MDNS_SEARCH_TIMEOUT,
			async(function (): void {
				$this->server?->close();

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						Sources\Connector::SHELLY,
						'Devices discovery finished',
					),
				);
			}),
		);
	}

	private function discoverCloudDevices(): void
	{
		// TODO: Implement cloud discovery
	}

	private function handleFoundLocalDevice(
		Types\DeviceGeneration $generation,
		string $id,
		string $type,
		string $ipAddress,
		string|null $domain,
	): void
	{
		$gen1HttpApi = $this->gen1HttpApiFactory->create();
		$gen2HttpApi = $this->gen2HttpApiFactory->create();

		$identifier = $id . '-' . $type;

		if ($generation === Types\DeviceGeneration::UNKNOWN) {
			try {
				$deviceInformation = await($gen2HttpApi->getDeviceInformation($ipAddress));
				$generation = Types\DeviceGeneration::GENERATION_2;
			} catch (Throwable) {
				try {
					$deviceInformation = await($gen1HttpApi->getDeviceInformation($ipAddress));
					$generation = Types\DeviceGeneration::GENERATION_1;
				} catch (Throwable) {
					return;
				}
			}
		} else {
			try {
				if ($generation === Types\DeviceGeneration::GENERATION_1) {
					$deviceInformation = await($gen1HttpApi->getDeviceInformation($ipAddress));
				} elseif ($generation === Types\DeviceGeneration::GENERATION_2) {
					$deviceInformation = await($gen2HttpApi->getDeviceInformation($ipAddress));
				} else {
					return;
				}
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not load device basic information',
					[
						'source' => Sources\Connector::SHELLY->value,
						'type' => 'discovery-client',
						'exception' => Logging\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'identifier' => $identifier,
							'ip_address' => $ipAddress,
							'domain' => $domain,
							'generation' => $generation->value,
						],
					],
				);

				return;
			}
		}

		$deviceDescription = $deviceConfiguration = $deviceStatus = null;

		try {
			if ($generation === Types\DeviceGeneration::GENERATION_1) {
				$deviceDescription = await($gen1HttpApi->getDeviceDescription($ipAddress, null, null));
			} else {
				$deviceConfiguration = await($gen2HttpApi->getDeviceConfiguration($ipAddress, null, null));
				$deviceStatus = await($gen2HttpApi->getDeviceState($ipAddress, null, null));
			}
		} catch (Throwable $ex) {
			if (
				$ex instanceof Exceptions\HttpApiCall
				&& $ex->getCode() === StatusCodeInterface::STATUS_UNAUTHORIZED
			) {
				$this->logger->error(
					'Device is password protected and could not be accessed',
					[
						'source' => Sources\Connector::SHELLY->value,
						'type' => 'discovery-client',
						'exception' => Logging\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'identifier' => $identifier,
							'ip_address' => $ipAddress,
							'domain' => $domain,
							'generation' => $generation->value,
						],
					],
				);
			} else {
				$this->logger->error(
					'Could not load device description or configuration',
					[
						'source' => Sources\Connector::SHELLY->value,
						'type' => 'discovery-client',
						'exception' => Logging\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'identifier' => $identifier,
							'ip_address' => $ipAddress,
							'domain' => $domain,
							'generation' => $generation->value,
						],
					],
				);
			}

			return;
		}

		try {
			if (
				$generation === Types\DeviceGeneration::GENERATION_1
				&& $deviceDescription !== null
			) {
				$message = $this->messageBuilder->create(
					Queue\Messages\StoreLocalDevice::class,
					[
						'connector' => $this->connector->getId(),
						'identifier' => $identifier,
						'serial_number' => $id,
						'generation' => $generation->value,
						'ip_address' => $ipAddress,
						'domain' => $domain,
						'model' => $deviceInformation->getModel(),
						'mac_address' => $deviceInformation->getMacAddress(),
						'auth_enabled' => $deviceInformation->hasAuthentication(),
						'firmware_version' => $deviceInformation->getFirmware(),
						'channels' => array_map(
							static fn (API\Messages\Response\Gen1\DeviceBlockDescription $block): array => [
								'identifier' => $block->getIdentifier() . '_' . $block->getDescription(),
								'name' => DevicesUtilities\Name::createName($block->getDescription()),
								'properties' => array_map(
									static fn (API\Messages\Response\Gen1\BlockSensorDescription $sensor): array => [
										'identifier' => (
											$sensor->getIdentifier()
											. '_'
											. $sensor->getType()->value
											. '_'
											. $sensor->getDescription()
										),
										'name' => DevicesUtilities\Name::createName($sensor->getDescription()),
										'data_type' => $sensor->getDataType()->value,
										'unit' => $sensor->getUnit(),
										'format' => $sensor->getFormat(),
										'invalid' => $sensor->getInvalid(),
										'queryable' => $sensor->isQueryable(),
										'settable' => $sensor->isSettable(),
									],
									$block->getSensors(),
								),
							],
							$deviceDescription->getBlocks(),
						),
					],
				);
			} elseif (
				$generation === Types\DeviceGeneration::GENERATION_2
				&& $deviceConfiguration !== null
			) {
				$message = $this->messageBuilder->create(
					Queue\Messages\StoreLocalDevice::class,
					[
						'connector' => $this->connector->getId(),
						'identifier' => $identifier,
						'serial_number' => $id,
						'generation' => $generation->value,
						'ip_address' => $ipAddress,
						'domain' => $domain,
						'model' => $deviceInformation->getModel(),
						'mac_address' => $deviceInformation->getMacAddress(),
						'auth_enabled' => $deviceInformation->hasAuthentication(),
						'firmware_version' => $deviceInformation->getFirmware(),
						'channels' => array_map(
							function ($component) use ($deviceStatus): array {
								$channel = [
									'identifier' => $component->getType()->value . '_' . $component->getId(),
									'name' => $component->getName() ?? DevicesUtilities\Name::createName(
										strval($component->getType()->value),
									),
									'properties' => [],
								];

								$gen2metadata = $this->loader->loadGen2Components();

								if ($gen2metadata->offsetExists($component->getType()->value)) {
									$componentMetadata = $gen2metadata->offsetGet(
										$component->getType()->value,
									);
									assert($componentMetadata instanceof Utils\ArrayHash);

									if ($component instanceof API\Messages\Response\Gen2\DeviceInputConfiguration) {
										$inputType = $component->getInputType()->value;

										if ($componentMetadata->offsetExists($inputType)) {
											$channel['properties'][] = array_merge(
												[
													'identifier' => (
														$component->getType()->value
														. '_'
														. $component->getId()
														. '_'
														. $inputType
													),
												],
												(array) Utils\Json::decode(
													Utils\Json::encode(
														(array) $componentMetadata->offsetGet($inputType),
													),
													forceArrays: true,
												),
											);
										}
									} else {
										foreach ($componentMetadata as $type => $configuration) {
											assert(
												$configuration instanceof Utils\ArrayHash
												&& $configuration->offsetExists('optional')
												&& is_bool($configuration->offsetGet('optional')),
											);

											if (!$configuration->offsetGet('optional')) {
												$channel['properties'][] = array_merge(
													[
														'identifier' => (
															$component->getType()->value
															. '_'
															. $component->getId()
															. '_'
															. $type
														),
													],
													(array) Utils\Json::decode(
														Utils\Json::encode((array) $configuration),
														forceArrays: true,
													),
												);
											} else {
												$status = $deviceStatus?->findComponent(
													$component->getType(),
													$component->getId(),
												);

												$status = $status?->toArray();

												if (
													$status === null
													|| (
														array_key_exists($type, $status)
														&& $status[$type] !== Shelly\Constants::VALUE_NOT_AVAILABLE
													)
												) {
													$channel['properties'][] = array_merge(
														[
															'identifier' => (
																$component->getType()->value
																. '_'
																. $component->getId()
																. '_'
																. $type
															),
														],
														(array) Utils\Json::decode(
															Utils\Json::encode((array) $configuration),
															forceArrays: true,
														),
													);
												}
											}
										}
									}
								}

								return $channel;
							},
							array_merge(
								$deviceConfiguration->getSwitches(),
								$deviceConfiguration->getCovers(),
								$deviceConfiguration->getLights(),
								$deviceConfiguration->getInputs(),
								$deviceConfiguration->getTemperature(),
								$deviceConfiguration->getHumidity(),
								$deviceConfiguration->getDevicePower(),
								$deviceConfiguration->getScripts(),
								$deviceConfiguration->getSmoke(),
								$deviceConfiguration->getVoltmeters(),
							),
						),
					],
				);
			} else {
				return;
			}

			$this->queue->append($message);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Could not create discovered device',
				[
					'source' => Sources\Connector::SHELLY->value,
					'type' => 'discovery-client',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'identifier' => $identifier,
						'ip_address' => $ipAddress,
						'domain' => $domain,
						'generation' => $generation->value,
					],
				],
			);
		}
	}

}
