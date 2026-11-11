<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use Orisai\ObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A NUL byte in the dates of a document (created_at and updated_at, on every document of the module) is a refusal of
 * the field with the formats it is read in: createFromFormat() throws a ValueError for it, which orisai let through (#836
 * turned it into a refusal of the whole document)
 */
final class NulDateTest extends TestCase
{

	#[DataProvider('dates')]
	public function testANulByteInADateIsRefusedAtTheField(string $field): void
	{
		$mapper = new CoreDocuments\Mapping\Mapper($this->createMock(ObjectMapper\Processing\Processor::class));

		try {
			$mapper->map(
				['id' => '176984ad-7cf7-465d-9e53-71668a74a688', 'identifier' => 'a', $field => "2020-04-01T12:00:00+00:00\0"],
				UiDocuments\Dashboards\Dashboard::class,
			);

			self::fail('the mapper accepted the data');
		} catch (CoreDocuments\Exceptions\MappingFailed $ex) {
			self::assertStringStartsWith($field . ': Value ', $ex->getMessage());
			self::assertStringEndsWith(
				'does not match any of the following formats: `Y-m-d\TH:i:sP`.',
				$ex->getMessage(),
			);
		}
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function dates(): array
	{
		return ['created_at' => ['created_at'], 'updated_at' => ['updated_at']];
	}

}
