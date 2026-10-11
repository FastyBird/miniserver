<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\ValueObjects;

use Orisai\ObjectMapper;

/**
 * OpenAPI sign
 */
final readonly class OpenApiRequestSign implements ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $sign,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $timestamp,
	)
	{
	}

	public function getSign(): string
	{
		return $this->sign;
	}

	public function getTimestamp(): int
	{
		return $this->timestamp;
	}

}
