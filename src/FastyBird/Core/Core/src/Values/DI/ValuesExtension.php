<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\DI;

use FastyBird\Core\Values\Schemas;
use Nette\DI;
use Override;

/**
 * The JSON schema validator
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs as fbCore.values, so its service is
 * fbCore.values.schemas.validator. It has no configuration.
 */
final class ValuesExtension extends DI\CompilerExtension
{

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('schemas.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Validator::class);
	}

}
