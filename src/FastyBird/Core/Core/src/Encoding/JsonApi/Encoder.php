<?php declare(strict_types = 1);

/**
 * Encoder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:JsonApi!
 * @subpackage     JsonApi
 * @since          0.1.0
 *
 * @date           24.03.20
 */

namespace FastyBird\Core\Encoding\JsonApi;

use Neomerx\JsonApi\Encoder as NeomerxEncoder;

/**
 * Extended Json:API encoder
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     JsonApi
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Encoder extends NeomerxEncoder\Encoder
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
