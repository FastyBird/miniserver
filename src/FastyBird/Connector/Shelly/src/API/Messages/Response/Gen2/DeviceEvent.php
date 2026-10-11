<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use Exception;
use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * Generation 2 device event message
 */
final readonly class DeviceEvent implements API\Messages\Message
{

	/**
	 * @param array<int, ComponentEvent> $events
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: ComponentEvent::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $events = [],
	)
	{
	}

	/**
	 * @return array<ComponentEvent>
	 */
	public function getEvents(): array
	{
		return $this->events;
	}

	/**
	 * @throws Exception
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'events' => array_map(
				static fn (ComponentEvent $state): array => $state->toArray(),
				$this->getEvents(),
			),
		];
	}

}
