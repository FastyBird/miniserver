<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * OpenAPI get devices factory information message
 */
readonly class GetDevicesFactoryInfos implements API\Messages\Message
{

	/**
	 * @param array<DeviceFactoryInfos> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceFactoryInfos::class),
		)]
		private array $result,
	)
	{
	}

	/**
	 * @return array<DeviceFactoryInfos>
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'result' => array_map(
				static fn (DeviceFactoryInfos $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
