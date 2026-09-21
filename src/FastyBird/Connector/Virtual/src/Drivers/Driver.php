<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Drivers
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Connector\Virtual\Drivers;

use DateTimeInterface;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use React\Promise;

/**
 * Device service interface
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Drivers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Driver
{

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function connect(): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function disconnect(): Promise\PromiseInterface;

	public function isConnected(): bool;

	public function isConnecting(): bool;

	public function getLastConnectAttempt(): DateTimeInterface|null;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function process(): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function writeState(
		DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Channels\Properties\Dynamic $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $expectedValue,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function notifyState(
		DevicesDocuments\Devices\Properties\Mapped|DevicesDocuments\Channels\Properties\Mapped $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $actualValue,
	): Promise\PromiseInterface;

}
