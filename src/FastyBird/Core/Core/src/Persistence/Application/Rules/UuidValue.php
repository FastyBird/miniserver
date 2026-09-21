<?php declare(strict_types = 1);

/**
 * UuidValue.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     ObjectMapper
 * @since          1.0.0
 *
 * @date           02.08.23
 */

namespace FastyBird\Core\Persistence\Application\Rules;

use Attribute;
use Orisai\ObjectMapper;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class UuidValue implements ObjectMapper\Rules\RuleDefinition
{

	public function getType(): string
	{
		return UuidRule::class;
	}

	public function getArgs(): array
	{
		return [];
	}

}
