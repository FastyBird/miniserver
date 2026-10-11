<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\MappedSuperclass()]
abstract class Channel extends DevicesDocuments\Channels\Channel
{

	/**
	 * @return array<string, string>
	 */
	abstract public function toDefinition(): array;

}
