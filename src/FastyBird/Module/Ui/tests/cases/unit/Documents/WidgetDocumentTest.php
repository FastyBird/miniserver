<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Documents;

use Error;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Tests;
use Nette;
use PHPUnit\Framework\Attributes\DataProvider;
use function file_get_contents;
use function method_exists;

final class WidgetDocumentTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed> $fixture
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('widget')]
	public function testCreateDocument(string $data, string $class, array $fixture): void
	{
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);

		$document = $factory->create($class, $data);

		self::assertTrue($document instanceof $class);
		self::assertTrue(method_exists($document, 'getGroups'));
		self::assertIsArray($document->getGroups());
		self::assertEquals($fixture, $document->toArray());
	}

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('widgetInvalid')]
	public function testCreateDocumentInvalid(string $data, string $class): void
	{
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);

		$this->expectException(CoreExceptions\InvalidArgument::class);

		$factory->create($class, $data);
	}

	/**
	 * @return array<string, array<string|bool|array<string, mixed>>>
	 */
	public static function widget(): array
	{
		return [
			'analog-sensor' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/widget.json'),
				UiDocuments\Widgets\AnalogSensor::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => UiDocuments\Widgets\AnalogSensor::getType(),
					'source' => Sources\Module::UI->value,
					'identifier' => 'widget-identifier',
					'name' => null,
					'display' => '1e19c996-a9fe-429a-8db0-ffdac4a5b6c3',
					'data_sources' => [
						'92bf037a-db87-4a01-b888-d43d188f3e12',
					],
					'tabs' => [
						'672dd2fc-9134-4673-a7c5-7a69d789ff95',
					],
					'groups' => [
						'28396431-6bf3-45ae-a9e1-f6b2a590e6d6',
					],
					'owner' => null,
					'created_at' => null,
					'updated_at' => null,
				],
			],
		];
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function widgetInvalid(): array
	{
		return [
			'missing' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/widget.missing.json'),
				UiDocuments\Widgets\AnalogSensor::class,
			],
		];
	}

}
