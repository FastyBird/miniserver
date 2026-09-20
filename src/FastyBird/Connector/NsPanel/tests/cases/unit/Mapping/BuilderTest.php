<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\Mapping;

use Error;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use Nette;
use Nette\Utils;
use RuntimeException;
use function array_map;

final class BuilderTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 */
	public function testCategoriesMapping(): void
	{
		$builder = $this->getContainer()->getByType(Mapping\Builder::class);

		$categories = $builder->getCategoriesMapping();

		self::assertCount(14, $categories->getCategories());

		Tests\Tools\JsonAssert::assertFixtureMatch(
			__DIR__ . '/../../../../resources/categories.json',
			Nette\Utils\Json::encode(
				array_map(
					static fn (Mapping\Categories\Category $category): array => $category->toArray(),
					$categories->getCategories(),
				),
			),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 */
	public function testCapabilitiesMapping(): void
	{
		$builder = $this->getContainer()->getByType(Mapping\Builder::class);

		$capabilities = $builder->getCapabilitiesMapping();

		self::assertCount(23, $capabilities->getGroups());

		Tests\Tools\JsonAssert::assertFixtureMatch(
			__DIR__ . '/../../../../resources/capabilities.json',
			Nette\Utils\Json::encode(
				array_map(
					static fn (Mapping\Capabilities\Group $group): array => $group->toArray(),
					$capabilities->getGroups(),
				),
			),
		);
	}

}
