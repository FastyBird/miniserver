<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi;

use Neomerx\JsonApi\Encoder as NeomerxEncoder;

/**
 * Extended Json:API encoder
 */
final class Encoder extends NeomerxEncoder\Encoder
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
