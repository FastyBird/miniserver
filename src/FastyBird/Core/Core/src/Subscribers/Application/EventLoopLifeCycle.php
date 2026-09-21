<?php declare(strict_types = 1);

/**
 * EventLoopLifeCycle.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           11.09.24
 */

namespace FastyBird\Core\Subscribers\Application;

use FastyBird\Core\EventLoop\Application as EventLoop;
use FastyBird\Core\Events;
use Nette;
use Symfony\Component\EventDispatcher;

/**
 * Event loop events
 *
 * @package        FastyBird:Application!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EventLoopLifeCycle implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(private readonly EventLoop\Status $eventLoopStatus)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\EventLoopStarted::class => 'loopStarted',
			Events\EventLoopStopped::class => 'loopStopped',
			Events\EventLoopStopping::class => 'loopStopped',
		];
	}

	public function loopStarted(): void
	{
		$this->eventLoopStatus->setStatus(true);
	}

	public function loopStopped(): void
	{
		$this->eventLoopStatus->setStatus(false);
	}

}
