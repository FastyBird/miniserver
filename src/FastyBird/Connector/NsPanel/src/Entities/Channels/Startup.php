<?php declare(strict_types = 1);

/**
 * Startup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.10.24
 */

namespace FastyBird\Connector\NsPanel\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use Ramsey\Uuid;
use function array_key_exists;
use function preg_match;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Startup extends Channel
{

	public const TYPE = 'ns-panel-connector-startup';

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __construct(
		Entities\Devices\Device $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $identifier, $matches);

		if (!array_key_exists('capability', $matches)) {
			throw new Exceptions\InvalidState('Provided identifier is invalid');
		}

		if (
			Types\Capability::tryFrom($matches['capability']) === null
			|| $matches['capability'] !== Types\Capability::STARTUP->value
		) {
			throw new Exceptions\InvalidState('Provided identifier is invalid');
		}

		parent::__construct($device, $identifier, $name, $id);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getCapability(): Types\Capability
	{
		return Types\Capability::STARTUP;
	}

}
