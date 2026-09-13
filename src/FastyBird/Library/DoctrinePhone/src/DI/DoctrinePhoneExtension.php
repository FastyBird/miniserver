<?php declare(strict_types = 1);

/**
 * DoctrinePhoneExtension.php
 *
 * @copyright      More in LICENSE.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:DoctrinePhone!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           25.12.15
 */

namespace FastyBird\Library\DoctrinePhone\DI;

use Doctrine;
use FastyBird\Library\DoctrinePhone\Events;
use FastyBird\Library\DoctrinePhone\Types;
use Nette;
use Nette\DI;
use Nette\PhpGenerator as Code;
use function assert;

/**
 * Doctrine phone extension container
 *
 * @package        iPublikuj:DoctrinePhone!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class DoctrinePhoneExtension extends DI\CompilerExtension
{

	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'doctrinePhone',
	): void
	{
		$config->onCompile[] = static function (Nette\Configurator $config, Nette\DI\Compiler $compiler) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration()
	{
		// Get container builder
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('subscriber'))
			->setType(Events\PhoneObjectSubscriber::class);
	}

	public function beforeCompile(): void
	{
		parent::beforeCompile();

		// Get container builder
		$builder = $this->getContainerBuilder();

		$emServiceName = $builder->getByType(Doctrine\ORM\EntityManagerInterface::class, true);

		if ($emServiceName !== null) {
			$emService = $builder->getDefinition($emServiceName);
			assert($emService instanceof DI\Definitions\ServiceDefinition);

			$emService
				->addSetup('?->getEventManager()->addEventSubscriber(?)', [
					'@self',
					$builder->getDefinition($this->prefix('subscriber')),
				]);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function afterCompile(Code\ClassType $class)
	{
		parent::afterCompile($class);

		$initialize = $class->getMethod('initialize');
		// Registers the 'phone' DBAL type. Entities map columns to it by name -- see
		// Module/Triggers Entities\Notifications\Sms -- so without this every test that
		// loads the Triggers metadata fails.
		$initialize->addBody(
			'if (!Doctrine\DBAL\Types\Type::hasType(\'' . Types\Phone::PHONE . '\')) {'
			. ' Doctrine\DBAL\Types\Type::addType('
			. '\'' . Types\Phone::PHONE . '\', \'' . Types\Phone::class . '\''
			. '); }',
		);
	}

}
