<?php declare(strict_types = 1);

/**
 * PropertiesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           16.01.24
 */

namespace FastyBird\Module\Devices\Models\States;

use DateTimeInterface;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Transformers\Tools as ToolsTransformers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\States;
use Orisai\ObjectMapper;
use Throwable;
use TypeError;
use ValueError;
use function array_merge;
use function is_float;
use function is_int;
use function is_string;

/**
 * Useful dynamic property state helpers
 *
 * @template TParent of (Documents\Connectors\Properties\Dynamic | Documents\Devices\Properties\Dynamic | Documents\Channels\Properties\Dynamic)
 * @template TChild of (Documents\Devices\Properties\Mapped | Documents\Channels\Properties\Mapped | null)
 * @template TState of States\Property
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class PropertiesManager
{

	public function __construct(
		protected readonly Devices\Logger $logger,
		protected readonly ObjectMapper\Processing\Processor $stateMapper,
	)
	{
	}

	/**
	 * @param TParent $property
	 * @param TChild $mappedProperty
	 * @param TState $state
	 *
	 * @return TState
	 *
	 * @throws Exceptions\InvalidActualValue
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidExpectedValue
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function convertStoredState($property, $mappedProperty, $state, bool $forReading)
	{
		$updateValues = [];

		if ($state->getActualValue() !== null) {
			try {
				$updateValues[States\Property::ACTUAL_VALUE_FIELD] = $this->convertReadValue(
					$state->getActualValue(),
					$property,
					$mappedProperty,
					$forReading,
				);
			} catch (ToolsExceptions\InvalidValue | ApplicationExceptions\InvalidState $ex) {
				if ($mappedProperty !== null) {
					$updateValues[States\Property::ACTUAL_VALUE_FIELD] = null;
					$updateValues[States\Property::VALID_FIELD] = false;

					$this->logger->error(
						'Property stored actual value could not be converted to mapped property',
						[
							'source' => MetadataTypes\Sources\Module::DEVICES->value,
							'type' => 'properties-states',
							'exception' => ToolsHelpers\Logger::buildException($ex),
							'property' => $property->getId()->toString(),
							'mapped_property' => $mappedProperty->getId()->toString(),
						],
					);

				} else {
					throw new Exceptions\InvalidActualValue('Property stored actual value was not valid');
				}
			} catch (Throwable $ex) {
				if ($mappedProperty !== null) {
					$updateValues[States\Property::ACTUAL_VALUE_FIELD] = null;
					$updateValues[States\Property::VALID_FIELD] = false;

					$this->logger->error(
						'Property stored actual value could not be converted to mapped property',
						[
							'source' => MetadataTypes\Sources\Module::DEVICES->value,
							'type' => 'properties-states',
							'exception' => ToolsHelpers\Logger::buildException($ex),
						],
					);

				} else {
					throw new Exceptions\InvalidActualValue('Property stored actual value was not valid');
				}
			}
		}

		if ($state->getExpectedValue() !== null) {
			try {
				$expectedValue = $this->convertReadValue(
					$state->getExpectedValue(),
					$property,
					$mappedProperty,
					$forReading,
				);

				if ($expectedValue !== null && !$property->isSettable()) {
					throw new Exceptions\InvalidExpectedValue('Property is not settable but has stored expected value');
				}

				$updateValues[States\Property::EXPECTED_VALUE_FIELD] = $expectedValue;
			} catch (ToolsExceptions\InvalidValue $ex) {
				if ($mappedProperty !== null) {
					$updateValues[States\Property::EXPECTED_VALUE_FIELD] = null;
					$updateValues[States\Property::PENDING_FIELD] = false;

					$this->logger->error(
						'Property stored actual value could not be converted to mapped property',
						[
							'source' => MetadataTypes\Sources\Module::DEVICES->value,
							'type' => 'properties-states',
							'exception' => ToolsHelpers\Logger::buildException($ex),
						],
					);

				} else {
					throw new Exceptions\InvalidExpectedValue('Property stored expected value was not valid');
				}
			}
		}

		if ($mappedProperty !== null) {
			$updateValues['id'] = $mappedProperty->getId();
		}

		if ($updateValues === []) {
			return $state;
		}

		return $this->updateState($state, $state::class, $updateValues);
	}

	/**
	 * IMPORTANT: These rules are important for handling work with device states
	 *
	 * Parameter $forReading is used to define if value transformers should be used or not.
	 * If $forReading is TRUE it meant that value will be used for display purposes e.g. in user interface
	 * or will be published via application system.
	 *
	 * Handling dynamic property:
	 * - Loaded value is force retyped to property defined data type e.g. string value is retyped to float.
	 * - Value is normalized and validated against property defined data type and format.
	 *   E.g. numeric values are compared against ranges [x, y]. If value is wrong, exception is thrown.
	 *   Special data types like SWITCH, COVER, etc. are validated against format
	 * For READING
	 * - If property has defined a SCALE TRANSFORMER, then it is applied to the value
	 * - If property has defined a VALUE TRANSFORMER, then it is applied to the value.
	 *   VALUE TRANSFORMER could modify final value which could be out of the allowed range defined by property format
	 * For USING
	 * - If a value is going to be loaded for USING, then TO DEVICE transformation is used.
	 *
	 *  Handling mapped property:
	 * - Loaded value is force retyped to parent property defined data type e.g. string value is retyped to float.
	 * - Value is normalized and validated against parent property defined data type and format.
	 *   E.g. numeric values are compared against ranges [x, y]. If value is wrong, exception is thrown.
	 *   Special data types like SWITCH, COVER, etc. are validated against format.
	 * - If a parent property has defined a SCALE TRANSFORMER, then it is applied to the value.
	 * - If parent property has defined a VALUE TRANSFORMER, then it is applied to the value.
	 * - If mapped property has defined a VALUE TRANSFORMER, then it is applied to the value.
	 * - Value is normalized and validated against mapped property defined data type and format.
	 * For READING
	 * - If a SCALE TRANSFORMER is defined on mapped property, then it is applied to the value.
	 * For USING
	 * - If a value is going to be loaded for USING, then TO DEVICE transformation is used.
	 *
	 *  NOTE: Value transformer on mapped property is always used, if defined. This transformer is to transform parent property value to mapped property.
	 *
	 * @param TParent $property
	 * @param TChild $mappedProperty
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidValue
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function convertReadValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
		$property,
		$mappedProperty,
		bool $forReading,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		/**
		 * Transform value to property defined data type
		 */
		$value = ToolsUtilities\Value::transformDataType(
			ToolsUtilities\Value::flattenValue($value),
			$property->getDataType(),
		);

		/**
		 * Read value is now normalized and validated against property configuration
		 */
		$value = ToolsUtilities\Value::normalizeValue(
			$value,
			$property->getDataType(),
			$property->getFormat(),
		);

		if ($forReading || $mappedProperty !== null) {
			$value = ToolsUtilities\Value::transformToScale(
				$value,
				$property->getDataType(),
				$property->getScale(),
			);

			if (is_string($property->getValueTransformer())) {
				$transformer = new ToolsTransformers\EquationTransformer($property->getValueTransformer());

				if (is_int($value) || is_float($value)) {
					$value = $transformer->calculateEquationTo(
						$value,
						$property->getDataType(),
					);
				}
			}
		}

		if (!$forReading && $mappedProperty === null) {
			$value = ToolsUtilities\Value::transformValueToDevice(
				$value,
				$property->getDataType(),
				$property->getFormat(),
			);
		}

		if ($mappedProperty !== null) {
			if (is_string($mappedProperty->getValueTransformer())) {
				$transformer = new ToolsTransformers\EquationTransformer($mappedProperty->getValueTransformer());

				if (is_int($value) || is_float($value)) {
					$value = $transformer->calculateEquationFrom(
						$value,
						$mappedProperty->getDataType(),
					);
				}
			}

			/**
			 * Transform value to mapped property defined data type
			 */
			$value = ToolsUtilities\Value::transformDataType(
				ToolsUtilities\Value::flattenValue($value),
				$mappedProperty->getDataType(),
			);

			/**
			 * Read value is now normalized and validated against mapped property configuration
			 */
			$value = ToolsUtilities\Value::normalizeValue(
				$value,
				$mappedProperty->getDataType(),
				$mappedProperty->getFormat(),
			);

			$value = $forReading ? ToolsUtilities\Value::transformToScale(
				$value,
				$mappedProperty->getDataType(),
				$mappedProperty->getScale(),
			) : ToolsUtilities\Value::transformValueToDevice(
				$value,
				$mappedProperty->getDataType(),
				$mappedProperty->getFormat(),
			);
		}

		return $value;
	}

	/**
	 * @param TParent $property
	 *
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidValue
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function convertWriteActualValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
		$property,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		/**
		 * Convert value received from device to property defined data type
		 */
		$value = ToolsUtilities\Value::transformDataType(
			ToolsUtilities\Value::flattenValue($value),
			$property->getDataType(),
		);

		/**
		 * Value received from device have to be converted to system acceptable value
		 * It is mandatory for properties with combined enum format defined values
		 */
		$value = ToolsUtilities\Value::transformValueFromDevice(
			$value,
			$property->getDataType(),
			$property->getFormat(),
		);

		/**
		 * Value received from device is now normalized and validated against property configuration
		 */
		return ToolsUtilities\Value::normalizeValue(
			$value,
			$property->getDataType(),
			$property->getFormat(),
		);
	}

	/**
	 * @param TParent $property
	 * @param TChild $mappedProperty
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidValue
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function convertWriteExpectedValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value,
		$property,
		$mappedProperty,
		bool $forWriting,
	): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if ($mappedProperty !== null) {
			/**
			 * Transform value to mapped property defined data type
			 */
			$value = ToolsUtilities\Value::transformDataType(
				ToolsUtilities\Value::flattenValue($value),
				$mappedProperty->getDataType(),
			);

			$value = $forWriting ? ToolsUtilities\Value::transformFromScale(
				$value,
				$mappedProperty->getDataType(),
				$mappedProperty->getScale(),
			) : ToolsUtilities\Value::transformValueFromDevice(
				$value,
				$mappedProperty->getDataType(),
				$mappedProperty->getFormat(),
			);

			/**
			 * Write value is now normalized and validated against property configuration
			 */
			$value = ToolsUtilities\Value::normalizeValue(
				$value,
				$mappedProperty->getDataType(),
				$mappedProperty->getFormat(),
			);

			/**
			 * If property has some value transformer, it is now applied
			 */
			if (is_string($mappedProperty->getValueTransformer())) {
				$transformer = new ToolsTransformers\EquationTransformer($mappedProperty->getValueTransformer());

				if (is_int($value) || is_float($value)) {
					$value = $transformer->calculateEquationTo($value, $mappedProperty->getDataType());
				}
			}
		}

		/**
		 * Transform value to property defined data type
		 */
		$value = ToolsUtilities\Value::transformDataType(
			ToolsUtilities\Value::flattenValue($value),
			$property->getDataType(),
		);

		if ($forWriting) {
			if (is_string($property->getValueTransformer())) {
				$transformer = new ToolsTransformers\EquationTransformer($property->getValueTransformer());

				if (is_int($value) || is_float($value)) {
					$value = $transformer->calculateEquationFrom(
						$value,
						$property->getDataType(),
					);
				}
			}
		}

		if ($forWriting || $mappedProperty !== null) {
			$value = ToolsUtilities\Value::transformFromScale(
				$value,
				$property->getDataType(),
				$property->getScale(),
			);
		}

		/**
		 * Write value is now normalized and validated against property configuration
		 */
		return ToolsUtilities\Value::normalizeValue(
			$value,
			$property->getDataType(),
			$property->getFormat(),
		);
	}

	/**
	 * @param TState $state
	 * @param class-string<TState> $class
	 * @param array<string, mixed> $update
	 *
	 * @return TState
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	protected function updateState($state, string $class, array $update)
	{
		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->stateMapper->process(
				array_merge(
					$state->toArray(),
					[
						$state::ACTUAL_VALUE_FIELD => $state->getActualValue(),
						$state::EXPECTED_VALUE_FIELD => $state->getExpectedValue(),
						$state::PENDING_FIELD => $state->getPending(),
						$state::VALID_FIELD => $state->isValid(),
						$state::CREATED_AT => $state->getCreatedAt()?->format(DateTimeInterface::ATOM),
						$state::UPDATED_AT => $state->getUpdatedAt()?->format(DateTimeInterface::ATOM),
					],
					$update,
				),
				$class,
				$options,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\InvalidArgument('Could not map data to state: ' . $errorPrinter->printError($ex));
		}
	}

}
