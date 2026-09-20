<?php declare(strict_types = 1);

/**
 * TemplateFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Tools!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           29.08.24
 */

namespace FastyBird\Core\Utilities\Tools;

use DateTimeInterface;
use FastyBird\Library\DateTimeFactory;
use FastyBird\Library\DoctrineTimestampable\Providers as DoctrineTimestampableProviders;

/**
 * Date provider for doctrine timestampable
 *
 * @package        FastyBird:Tools!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class DateTimeProvider implements DoctrineTimestampableProviders\DateProvider
{

	public function __construct(private DateTimeFactory\Clock $clock)
	{
	}

	public function getDate(): DateTimeInterface
	{
		return $this->clock->getNow();
	}

	public function getTimestamp(): int
	{
		return $this->clock->getNow()->getTimestamp();
	}

}
