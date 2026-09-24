<?php declare(strict_types = 1);

/**
 * RestartConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           21.01.24
 */

namespace FastyBird\Module\Devices\Events;

use FastyBird\Core\Values\Types\Sources;
use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Event fired when connector should be restarted
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RestartConnector extends EventDispatcher\Event
{

	public function __construct(
		private readonly Sources\Source $source,
		private readonly string|null $reason = null,
		private readonly Throwable|null $exception = null,
	)
	{
	}

	public function getSource(): Sources\Source
	{
		return $this->source;
	}

	public function getReason(): string|null
	{
		return $this->reason;
	}

	public function getException(): Throwable|null
	{
		return $this->exception;
	}

}
