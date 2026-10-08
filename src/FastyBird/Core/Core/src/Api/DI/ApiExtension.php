<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\DI;

use FastyBird\Core\Api\Encoding;
use FastyBird\Core\Api\Helpers;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Core\Api\Middleware;
use FastyBird\Core\Api\Schemas;
use Nette\DI;
use Nette\Schema;
use Override;
use function assert;

/**
 * JSON:API: the document builder, the middleware, and the schema and hydrator containers
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs as fbCore.api and reads its fbCore > api
 * section, so its services are fbCore.api.*. In beforeCompile() it adds
 * every JSON:API schema and hydrator service to its container.
 */
final class ApiExtension extends DI\CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'meta' => Schema\Expect::structure([
				'author' => Schema\Expect::anyOf(Schema\Expect::string(), Schema\Expect::array())
					->default('FastyBird team'),
				'copyright' => Schema\Expect::string()->default(null)->nullable(),
			])->castTo(Config\Meta::class),
		])->castTo(Config::class);
	}

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof Config);

		$builder->addDefinition($this->prefix('builder'), new DI\Definitions\ServiceDefinition())
			->setType(Encoding\Builder::class)
			->setArgument('metaAuthor', $configuration->meta->author)
			->setArgument('metaCopyright', $configuration->meta->copyright);

		$builder->addDefinition($this->prefix('middleware'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\JsonApiMiddleware::class);

		$builder->addDefinition($this->prefix('hydrators.container'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Container::class);

		$schemaContainer = $builder->addDefinition(
			$this->prefix('schemas.container'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Encoding\SchemaContainer::class);

		// The builder, the middleware and the hydrators container take the schema container in
		// their constructors, while each schema reaches the router and the router reaches them
		// (the middleware, the module routes' controllers): a cycle. A lazy service is a native
		// PHP 8.4 lazy ghost, built, setups and all, on its first use, which breaks it (#639)
		$schemaContainer->lazy = true;

		// Every hydrator takes this reader as an optional constructor argument, so registering it
		// switches on the #[Crud] required/writable rules for every JSON:API write (#552)
		$builder->addDefinition($this->prefix('helpers.crudReader'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\CrudReader::class);
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws DI\NotAllowedDuringResolvingException
	 */
	#[Override]
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		$schemaContainerServiceName = $builder->getByType(Encoding\SchemaContainer::class, true);
		$schemaContainerService = $builder->getDefinition($schemaContainerServiceName);
		assert($schemaContainerService instanceof DI\Definitions\ServiceDefinition);

		foreach ($builder->findByType(Schemas\JsonApiSchema::class) as $schemasService) {
			$schemaContainerService->addSetup('add', [$schemasService]);
		}

		$hydratorContainerServiceName = $builder->getByType(
			Hydrators\Container::class,
			true,
		);
		$hydratorContainerService = $builder->getDefinition($hydratorContainerServiceName);
		assert($hydratorContainerService instanceof DI\Definitions\ServiceDefinition);

		foreach ($builder->findByType(Hydrators\Hydrator::class) as $hydratorService) {
			$hydratorContainerService->addSetup('add', [$hydratorService]);
		}
	}

}
