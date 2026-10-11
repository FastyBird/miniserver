<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\States;

use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Types;

/**
 * Device capability state base message interface
 */
interface State extends API\Messages\Message
{

	public function getType(): Types\Capability;

	/**
	 * @return array<string, int|float|string|bool|Types\Payloads\Payload|null>
	 */
	public function getState(): array;

}
