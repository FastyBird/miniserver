<?php declare(strict_types = 1);

/**
 * JsonApiExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:JsonApi!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           27.05.20
 */

namespace FastyBird\Library\JsonApi\DI;

use FastyBird\Library\JsonApi\Builder;
use FastyBird\Library\JsonApi\Helpers;
use FastyBird\Library\JsonApi\Hydrators;
use FastyBird\Library\JsonApi\JsonApi;
use FastyBird\Library\JsonApi\Middleware;
use FastyBird\Library\JsonApi\Schemas;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use function class_exists;

/**
 * {JSON:API} api extension container
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class JsonApiExtension extends DI\CompilerExtension
{

	public static function register(
		Nette\Bootstrap\Configurator $config,
		string $extensionName = 'fbJsonApi',
	): void
	{
		$config->onCompile[] = static function (
			Nette\Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'meta' => Schema\Expect::structure([
				'author' => Schema\Expect::anyOf(Schema\Expect::string(), Schema\Expect::array())
					->default('FastyBird team'),
				'copyright' => Schema\Expect::string()
					->default(null)
					->nullable(),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('builder'), new DI\Definitions\ServiceDefinition())
			->setType(Builder\Builder::class)
			->setArgument('metaAuthor', $configuration->meta->author)
			->setArgument('metaCopyright', $configuration->meta->copyright);

		$builder->addDefinition($this->prefix('middlewares.jsonapi'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\JsonApi::class);

		$builder->addDefinition($this->prefix('hydrators.container'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Container::class);

		$builder->addDefinition($this->prefix('schemas.container'), new DI\Definitions\ServiceDefinition())
			->setType(JsonApi\SchemaContainer::class);

		if (class_exists('\IPub\DoctrineCrud\Mapping\Annotation\Crud')) {
			$builder->addDefinition($this->prefix('helpers.crudReader'), new DI\Definitions\ServiceDefinition())
				->setType(Helpers\CrudReader::class);
		}
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * JSON:API SCHEMAS
		 */

		$schemaContainerServiceName = $builder->getByType(JsonApi\SchemaContainer::class, true);

		$schemaContainerService = $builder->getDefinition($schemaContainerServiceName);
		assert($schemaContainerService instanceof DI\Definitions\ServiceDefinition);

		$schemasServices = $builder->findByType(Schemas\JsonApi::class);

		foreach ($schemasServices as $schemasService) {
			$schemaContainerService->addSetup('add', [$schemasService]);
		}

		/**
		 * JSON:API HYDRATORS
		 */

		$hydratorContainerServiceName = $builder->getByType(Hydrators\Container::class, true);

		$hydratorContainerService = $builder->getDefinition($hydratorContainerServiceName);
		assert($hydratorContainerService instanceof DI\Definitions\ServiceDefinition);

		$hydratorsServices = $builder->findByType(Hydrators\Hydrator::class);

		foreach ($hydratorsServices as $hydratorService) {
			$hydratorContainerService->addSetup('add', [$hydratorService]);
		}
	}

}
