<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\DI;

/**
 * The fbUiModule configuration, which UiExtension::getConfigSchema() declares and casts to this
 * class
 */
final readonly class Config
{

	public function __construct(public readonly bool $apiPrefix)
	{
	}

}
