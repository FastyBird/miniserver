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
use stdClass;
use function assert;
use function class_exists;

/**
 * JSON:API: the document builder, the middleware, and the schema and hydrator containers
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs under the composite's name and reads its
 * fbCore > jsonApi section, so its services are fbCore.jsonApi.*. In beforeCompile() it adds
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
			]),
		]);
	}

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('jsonApi.builder'), new DI\Definitions\ServiceDefinition())
			->setType(Encoding\Builder::class)
			->setArgument('metaAuthor', $configuration->meta->author)
			->setArgument('metaCopyright', $configuration->meta->copyright);

		$builder->addDefinition($this->prefix('jsonApi.middlewares.jsonapi'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\JsonApiMiddleware::class);

		$builder->addDefinition($this->prefix('jsonApi.hydrators.container'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Container::class);

		$builder->addDefinition($this->prefix('jsonApi.schemas.container'), new DI\Definitions\ServiceDefinition())
			->setType(Encoding\SchemaContainer::class);

		if (class_exists('\IPub\DoctrineCrud\Mapping\Annotation\Crud')) {
			$builder->addDefinition($this->prefix('jsonApi.helpers.crudReader'), new DI\Definitions\ServiceDefinition())
				->setType(Helpers\CrudReader::class);
		}
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
