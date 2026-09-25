<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Documents;

use Error;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Tests;
use Nette;
use PHPUnit\Framework\Attributes\DataProvider;
use function file_get_contents;

final class ChannelPropertyActionDocumentTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('channelProperty')]
	public function testCreateDocument(string $data, string $class): void
	{
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);

		$document = $factory->create(DevicesDocuments\States\Channels\Properties\Actions\Action::class, $data);

		self::assertTrue($document instanceof $class);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	#[DataProvider('channelPropertyInvalid')]
	public function testCreateDocumentInvalid(string $data): void
	{
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);

		$this->expectException(CoreExceptions\InvalidArgument::class);

		$factory->create(DevicesDocuments\States\Channels\Properties\Actions\Action::class, $data);
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelProperty(): array
	{
		return [
			'get' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.action.get.json'),
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
			],
			'set' => [
				file_get_contents(__DIR__ . '/../../../fixtures/Documents/channel.property.action.set.json'),
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
			],
		];
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelPropertyInvalid(): array
	{
		return [
			'missing' => [
				file_get_contents(
					__DIR__ . '/../../../fixtures/Documents/channel.property.action.missing.json',
				),
			],
		];
	}

}
