<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * OpenAPI get user device children message
 */
final readonly class GetUserDeviceChildren implements API\Messages\Message
{

	/**
	 * @param array<UserDeviceChild> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceChild::class),
		)]
		private array $result,
	)
	{
	}

	/**
	 * @return array<UserDeviceChild>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'result' => array_map(static fn (UserDeviceChild $status): array => $status->toArray(), $this->getResult()),
		];
	}

}
