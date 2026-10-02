<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding;

use Neomerx\JsonApi\Encoder as JsonApiEncoder;

/**
 * Extended Json:API encoder
 */
final class Encoder extends JsonApiEncoder\Encoder
{

	/**
	 * @param object|iterable<mixed>|null $data
	 *
	 * @return array<mixed>
	 */
	public function encodeDataAsArray(object|iterable|null $data): array
	{
		return $this->encodeDataToArray($data);
	}

}
