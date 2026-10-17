<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Tests;
use Orisai\ObjectMapper;
use Throwable;
use function array_diff;
use function array_keys;
use function class_implements;
use function explode;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_starts_with;

/**
 * What a Devices document says when it refuses an input, for each input MappedDocumentPinTest holds refused: the path of
 * the value that was refused, and what was wrong with it. Valinor writes one line per refused value, `<path>: <message>`,
 * where orisai printed the rules a value had to match (#724 PR3)
 */
final class MappedDocumentMessageTest extends Tests\Cases\Unit\BaseTestCase
{

	private const array MESSAGES = [
		'a property without an id' => ['id', 'Cannot be empty and must be filled'],
		'a property whose id is not a UUID' => ['id', 'is not a valid UUID'],
		'a property whose id has no dashes' => ['id', 'is not a valid UUID'],
		'a property without a channel' => ['channel', 'Cannot be empty and must be filled'],
		'a mapped property without a parent' => ['parent', 'Cannot be empty and must be filled'],
		'a property without an identifier' => ['identifier', 'is not a valid string'],
		'a property with a blank identifier' => ['identifier', 'must not be blank'],
		'a property with an empty identifier' => ['identifier', 'must not be blank'],
		'a property with an identifier which is not a string' => ['identifier', 'is not a valid string'],
		'a property without a category' => ['category', 'Cannot be empty and must be filled'],
		'a property with an unknown category' => ['category', 'does not match'],
		'a property without a data type' => ['data_type', 'Cannot be empty and must be filled'],
		'a property with an unknown data type' => ['data_type', 'does not match'],
		'a property with a name which is not a string' => ['name', 'does not match'],
		'a property with a unit which is not a string' => ['unit', 'does not match'],
		'a property with a scale which is a float' => ['scale', 'does not match'],
		'a property with a scale which is a numeric string' => ['scale', 'does not match'],
		'a property with a step which is a word' => ['step', 'does not match'],
		'a property with an invalid value which is a list' => ['invalid', 'does not match'],
		'a property with a default which is a list' => ['default', 'does not match'],
		'a variable with a value which is a list' => ['value', 'does not match'],
		'a property with a value transformer which is a number' => ['value_transformer', 'is not a UUID, a text or null'],
		'a property with an owner which is not a UUID' => ['owner', 'is not a valid UUID'],
		'a property with a date in another format' => ['created_at', 'does not match any of the following formats'],
		'a property with updated_at as a timestamp' => ['updated_at', 'does not match any of the following formats'],
		'a property with an empty created_at' => ['created_at', 'does not match'],
		'a format which is a number' => ['format', 'is not a valid format'],
		'a format which is a bool' => ['format', 'is not a valid format'],
		'a format of three numbers' => ['format', 'is not a valid format'],
		'a format of four numbers' => ['format', 'is not a valid format'],
		'a format of one number' => ['format', 'is not a valid format'],
		'a format of a string and a number' => ['format', 'is not a valid format'],
		'a format of a word and a number' => ['format', 'is not a valid format'],
		'a format with a key which is a word' => ['format', 'is not a valid format'],
		'a format of a list with a bool' => ['format', 'is not a valid format'],
		'a range with a data type that is not a number' => ['format', 'is not a valid format'],
		'a range with an unknown data type' => ['format', 'is not a valid format'],
		'a range with a triple as its bound' => ['format', 'is not a valid format'],
		'a range with a bound which is a string' => ['format', 'is not a valid format'],
		'a range with a bound which is a bool' => ['format', 'is not a valid format'],
		'a combined enum with a pair of three' => ['format', 'is not a valid format'],
		'a combined enum row of two' => ['format', 'is not a valid format'],
		'a combined enum row of four' => ['format', 'is not a valid format'],
		'a combined enum row with an object' => ['format', 'is not a valid format'],
		'a dynamic property with a settable which is a word' => ['settable', 'is not a valid boolean'],
		'a dynamic property with a queryable which is an int' => ['queryable', 'is not a valid boolean'],
		'a dynamic property with a child which is not a UUID' => ['children.0', 'is not a valid UUID'],
		'a dynamic property with children which are not a list' => ['children', 'does not match'],
		'a mapped property with a settable which is an int' => ['settable', 'does not match'],
		'a mapped property with a value which is a list' => ['value', 'does not match'],
		'a connector property without a connector' => ['connector', 'Cannot be empty and must be filled'],
		'a device property without a device' => ['device', 'Cannot be empty and must be filled'],
		'a control without a name' => ['name', 'is not a valid string'],
		'a control with a blank name' => ['name', 'must not be blank'],
		'a control of a connector with a blank name' => ['name', 'must not be blank'],
		'a control with an empty name' => ['name', 'must not be blank'],
		'a control with a name which is not a string' => ['name', 'is not a valid string'],
		'a control without a parent' => ['connector', 'Cannot be empty and must be filled'],
		'a control with an id which is not a UUID' => ['id', 'is not a valid UUID'],
		'a control with an owner which is not a UUID' => ['owner', 'is not a valid UUID'],
		'a control with a date in another format' => ['created_at', 'does not match any of the following formats'],
		'a control action without an action' => ['action', 'Cannot be empty and must be filled'],
		'a control action with an unknown action' => ['action', 'does not match'],
		'a control action without a control' => ['control', 'Cannot be empty and must be filled'],
		'a control action without a connector' => ['connector', 'Cannot be empty and must be filled'],
		'a control action with an expected value which is a list' => ['expected_value', 'does not match'],
		'a state without a read' => ['read', 'Cannot be empty and must be filled'],
		'a state with a get which is a string' => ['get', 'does not match'],
		'a state whose read lacks the expected value' => ['read.expected_value', 'Cannot be empty and must be filled'],
		'a state with a pending which is a word' => ['pending', 'does not match any of the following formats'],
		'a state with a pending which is an int' => ['pending', 'does not match any of the following formats'],
		'a state with a pending in another date format' => ['pending', 'does not match any of the following formats'],
		'a state with a valid which is an int' => ['valid', 'is not a valid boolean'],
		'a state with a value which is a list' => ['read.actual_value', 'does not match'],
		'a state without a channel' => ['channel', 'Cannot be empty and must be filled'],
		'a state without an id' => ['id', 'Cannot be empty and must be filled'],
		'a state with a date in another format' => ['updated_at', 'does not match any of the following formats'],
		'an action without an action' => ['action', 'Cannot be empty and must be filled'],
		'an action which is unknown' => ['action', 'does not match'],
		'an action without a property' => ['property', 'Cannot be empty and must be filled'],
		'an action without a connector' => ['connector', 'Cannot be empty and must be filled'],
		'an action whose set is a string' => ['set', 'does not match'],
		'an action whose write is a number' => ['write', 'does not match'],
		'an action whose set holds a list' => ['set.expected_value', 'does not match'],
		'a blank identifier of a variable property of a channel' => ['identifier', 'must not be blank'],
		'a blank identifier of a dynamic property of a channel' => ['identifier', 'must not be blank'],
		'a blank identifier of a mapped property of a channel' => ['identifier', 'must not be blank'],
		'a blank identifier of a variable property of a device' => ['identifier', 'must not be blank'],
		'a blank identifier of a dynamic property of a device' => ['identifier', 'must not be blank'],
		'a blank identifier of a mapped property of a device' => ['identifier', 'must not be blank'],
		'a blank identifier of a variable property of a connector' => ['identifier', 'must not be blank'],
		'a blank identifier of a dynamic property of a connector' => ['identifier', 'must not be blank'],
	];

	/**
	 * @throws Throwable
	 */
	public function testARefusalNamesTheValueAndWhatWasWrongWithIt(): void
	{
		$factory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);
		$wrong = [];

		foreach (MappedDocumentPinTest::invalid() as $name => [$class, $data]) {
			self::assertArrayHasKey($name, self::MESSAGES);

			[$path, $kind] = self::MESSAGES[$name];

			try {
				$factory->create($class, $data);

				$wrong[] = $name . ': accepted';
			} catch (CoreExceptions\InvalidArgument $ex) {
				$lines = explode("\n", $ex->getMessage());

				// "Could not map data to document: " comes before the first line only
				$first = (string) preg_replace('/^Could not map data to document: /', '', $lines[0]);

				if (!str_starts_with($first, $path . ': ') || !str_contains($first, $kind)) {
					$wrong[] = sprintf('%s: "%s" is not "%s: ... %s"', $name, $first, $path, $kind);
				}
			}
		}

		self::assertSame([], $wrong);
		self::assertSame([], array_diff(array_keys(self::MESSAGES), array_keys(MappedDocumentPinTest::invalid())));
	}

	/**
	 * Which of the Devices documents orisai maps and which Valinor does (Mapper::map() chooses by MappedObject). The
	 * roots of the connectors, devices and channels stay orisai's until a pull request of their own moves them with
	 * everything in the tree that extends them, the documents that stay inside Devices are Valinor's
	 */
	public function testTheDocumentsThatStayInsideDevicesAreMappedByValinor(): void
	{
		foreach (
			[
				DevicesDocuments\Channels\Controls\Control::class,
				DevicesDocuments\Channels\Controls\Actions\Action::class,
				DevicesDocuments\Connectors\Controls\Control::class,
				DevicesDocuments\Connectors\Controls\Actions\Action::class,
				DevicesDocuments\Devices\Controls\Control::class,
				DevicesDocuments\Devices\Controls\Actions\Action::class,
				DevicesDocuments\Property::class,
				DevicesDocuments\Channels\Properties\Property::class,
				DevicesDocuments\Channels\Properties\Variable::class,
				DevicesDocuments\Channels\Properties\Dynamic::class,
				DevicesDocuments\Channels\Properties\Mapped::class,
				DevicesDocuments\Connectors\Properties\Property::class,
				DevicesDocuments\Connectors\Properties\Variable::class,
				DevicesDocuments\Connectors\Properties\Dynamic::class,
				DevicesDocuments\Devices\Properties\Property::class,
				DevicesDocuments\Devices\Properties\Variable::class,
				DevicesDocuments\Devices\Properties\Dynamic::class,
				DevicesDocuments\Devices\Properties\Mapped::class,
				DevicesDocuments\States\Property::class,
				DevicesDocuments\States\Channels\Properties\Property::class,
				DevicesDocuments\States\Connectors\Properties\Property::class,
				DevicesDocuments\States\Devices\Properties\Property::class,
				DevicesDocuments\States\StateValues::class,
				DevicesDocuments\States\ActionValues::class,
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
				DevicesDocuments\States\Connectors\Properties\Actions\Action::class,
				DevicesDocuments\States\Devices\Properties\Actions\Action::class,
				DevicesDocuments\Document::class,
			] as $class
		) {
			self::assertNotContains(ObjectMapper\MappedObject::class, class_implements($class), $class);
		}

		foreach (
			[
				DevicesDocuments\Channels\Channel::class,
				DevicesDocuments\Channels\Generic::class,
				DevicesDocuments\Connectors\Connector::class,
				DevicesDocuments\Connectors\Generic::class,
				DevicesDocuments\Devices\Device::class,
				DevicesDocuments\Devices\Generic::class,
			] as $class
		) {
			self::assertContains(ObjectMapper\MappedObject::class, class_implements($class), $class);
		}
	}

}
