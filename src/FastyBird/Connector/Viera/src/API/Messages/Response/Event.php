<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\API\Messages\Response;

use FastyBird\Connector\Viera\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Event data message
 */
final readonly class Event implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('screen_state')]
		private bool|null $screenState,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('input_mode')]
		private string|null $inputMode,
	)
	{
	}

	public function getScreenState(): bool|null
	{
		return $this->screenState;
	}

	public function getInputMode(): string|null
	{
		return $this->inputMode;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'screen_state' => $this->getScreenState(),
			'input_mode' => $this->getInputMode(),
		];
	}

}
