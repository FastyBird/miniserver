<?php declare(strict_types = 1);

/**
 * MiniServerExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     DI
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\MiniServer\DI;

use FastyBird\MiniServer\Commands;
use Nette\DI;

/**
 * MiniServer application
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MiniServerExtension extends DI\CompilerExtension
{

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('command.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\InitializeCommand::class);
	}

}
