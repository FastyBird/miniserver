<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * OpenAPI get user device factory information message
 */
readonly class GetUserDeviceFactoryInfos implements API\Messages\Message
{

	/**
	 * @param array<UserDeviceFactoryInfos> $result
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceFactoryInfos::class),
		)]
		private array $result,
	)
	{
	}

	/**
	 * @return array<UserDeviceFactoryInfos>
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
				static fn (UserDeviceFactoryInfos $status): array => $status->toArray(),
				$this->getResult(),
			),
		];
	}

}
