<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Mapping\Attribute;

use Attribute;
use Doctrine\ORM\Mapping as ORMMapping;

/**
 * Property attribute marking a Doctrine entity field as required and/or writable through CRUD hydration
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Crud implements ORMMapping\MappingAttribute
{

	/** @var string|array<string> */
	public string|array $is;

	public bool $required;

	public bool $writable;

	public function __construct(bool|null $required = null, bool|null $writable = null)
	{
		$this->required = $required ?? false;
		$this->writable = $writable ?? false;
	}

	public function isRequired(): bool
	{
		return $this->required;
	}

	public function isWritable(): bool
	{
		return $this->writable;
	}

}
