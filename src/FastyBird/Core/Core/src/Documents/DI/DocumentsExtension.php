<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\DI;

use FastyBird\Core\DI\CoreExtension;
use FastyBird\Core\Documents;
use FastyBird\Core\Exceptions;
use Nette\Caching;
use Nette\DI;
use Nette\Schema;
use Override;
use stdClass;
use function array_values;
use function assert;
use function is_dir;
use function sprintf;

/**
 * Documents: the document factory, its attribute mapping driver chain and metadata cache
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs as fbCore.documents and reads its
 * fbCore > documents section, so its services are fbCore.documents.cache,
 * fbCore.documents.factory and fbCore.documents.mapping.*. Module extensions add their own
 * drivers to the chain through CoreExtension::DRIVER_TAG.
 */
final class DocumentsExtension extends DI\CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			// No default previously -- ->required() forced every container that loads
			// fbCore (now literally every container in the repo) to supply a
			// mapping even when it owns zero JSON:API documents. An empty map is a
			// perfectly valid "this package/container has none" answer.
			'mapping' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string())
				->default([]),
			'excludePaths' => Schema\Expect::arrayOf(Schema\Expect::string(), Schema\Expect::string()),
		]);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$metadataCache = $builder->addDefinition(
			$this->prefix('cache'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Caching\Cache::class)
			->setArguments(['namespace' => 'metadata_class_metadata'])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('factory'), new DI\Definitions\ServiceDefinition())
			->setType(Documents\DocumentFactory::class);

		$attributeDriver = $builder->addDefinition(
			$this->prefix('mapping.attributeDriver'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Documents\Mapping\Driver\AttributeDriver::class)
			->setArguments(['paths' => array_values($configuration->mapping)])
			->addSetup('addExcludePaths', [$configuration->excludePaths])
			->addTag(CoreExtension::DRIVER_TAG)
			->setAutowired(false);

		$mappingDriver = $builder->addDefinition(
			$this->prefix('mapping.driverChain'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Documents\Mapping\Driver\MappingDriverChain::class);

		$builder->addDefinition($this->prefix('mapping.classMetadataFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Documents\Mapping\ClassMetadataFactory::class)
			->setArguments(['driver' => $mappingDriver, 'cache' => $metadataCache]);

		foreach ($configuration->mapping as $namespace => $path) {
			if (!is_dir($path)) {
				throw new Exceptions\InvalidState(sprintf('Given mapping path "%s" does not exist', $path));
			}

			$mappingDriver->addSetup('addDriver', [$attributeDriver, $namespace]);
		}
	}

}
