<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Helpers\Channels;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use TypeError;
use ValueError;
use function array_key_exists;
use function preg_match;

/**
 * Channel helper
 */
final class Channel
{

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getCapability(Documents\Channels\Channel $channel): Types\Capability
	{
		preg_match(NsPanel\Entities\Channels\Channel::CHANNEL_IDENTIFIER, $channel->getIdentifier(), $matches);

		if (!array_key_exists('capability', $matches)) {
			throw new Exceptions\InvalidState('Device channel has invalid identifier');
		}

		if (Types\Capability::tryFrom($matches['capability']) === null) {
			throw new Exceptions\InvalidState('Device channel has invalid identifier');
		}

		return Types\Capability::from($matches['capability']);
	}

	public function getName(Documents\Channels\Channel $channel): string|null
	{
		preg_match(NsPanel\Entities\Channels\Channel::CHANNEL_IDENTIFIER, $channel->getIdentifier(), $matches);

		if (!array_key_exists('name', $matches)) {
			return null;
		}

		return $matches['name'];
	}

}
