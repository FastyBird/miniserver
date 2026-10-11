<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Report third-party device online state to NS Panel event payload request definition
 */
final readonly class ReportDeviceOnlineEventPayload implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		private bool $online,
	)
	{
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'online' => $this->isOnline(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->online = $this->isOnline();

		return $json;
	}

}
