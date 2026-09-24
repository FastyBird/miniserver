<?php declare(strict_types = 1);

/**
 * StoreThirdPartyDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Store NS Panel third-party device details message
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class StoreThirdPartyDevice implements Message
{

	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $connector,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $gateway,
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $device,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('gateway_identifier')]
		private string $gatewayIdentifier,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getGateway(): Uuid\UuidInterface
	{
		return $this->gateway;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getGatewayIdentifier(): string
	{
		return $this->gatewayIdentifier;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'gateway' => $this->getGateway()->toString(),
			'device' => $this->getDevice()->toString(),
			'gateway_identifier' => $this->getGatewayIdentifier(),
		];
	}

}
