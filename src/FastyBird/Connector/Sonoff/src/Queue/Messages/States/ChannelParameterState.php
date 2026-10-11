<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Queue\Messages\States;

use Orisai\ObjectMapper;
use Override;
use function array_merge;

/**
 * Channel parameter state entity
 */
final class ChannelParameterState extends ParameterState
{

	public function __construct(
		string $name,
		readonly float|int|string|bool|null $value,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $group,
	)
	{
		parent::__construct($name, $value);
	}

	public function getGroup(): string
	{
		return $this->group;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'group' => $this->getGroup(),
		]);
	}

}
