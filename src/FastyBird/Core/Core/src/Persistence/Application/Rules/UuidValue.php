<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Application\Rules;

use Attribute;
use Orisai\ObjectMapper;
use Override;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class UuidValue implements ObjectMapper\Rules\RuleDefinition
{

	#[Override]
	public function getType(): string
	{
		return UuidRule::class;
	}

	#[Override]
	public function getArgs(): array
	{
		return [];
	}

}
