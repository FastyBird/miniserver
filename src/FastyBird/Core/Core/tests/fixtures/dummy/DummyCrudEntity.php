<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Persistence\Mapping\Attribute;

/**
 * One field per #[Crud] outcome the JSON:API hydrator distinguishes once a CrudReader is set:
 * required but not writable, writable, and not annotated at all
 */
final class DummyCrudEntity
{

	#[Attribute\Crud(required: true)]
	private string $identifier = 'dummy';

	#[Attribute\Crud(writable: true)]
	private string|null $label = null;

	private string|null $secret = null;

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function setIdentifier(string $identifier): void
	{
		$this->identifier = $identifier;
	}

	public function getLabel(): string|null
	{
		return $this->label;
	}

	public function setLabel(string|null $label): void
	{
		$this->label = $label;
	}

	public function getSecret(): string|null
	{
		return $this->secret;
	}

	public function setSecret(string|null $secret): void
	{
		$this->secret = $secret;
	}

}
