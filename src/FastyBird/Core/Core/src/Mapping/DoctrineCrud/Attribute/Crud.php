<?php declare(strict_types = 1);

namespace FastyBird\Core\Mapping\DoctrineCrud\Attribute;

use Attribute;
use Doctrine\ORM\Mapping as ORMMapping;

/**
 * Doctrine CRUD attribute for Doctrine2
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
