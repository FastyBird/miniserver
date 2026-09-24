<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           02.01.22
 */

namespace FastyBird\Module\Devices\Hydrators;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Api\Exceptions;
use FastyBird\Core\Api\Hydrators;
use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Values\Types;
use FastyBird\Module\Devices\Entities;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use TypeError;
use ValueError;
use function array_map;
use function boolval;
use function implode;
use function in_array;
use function is_array;
use function is_scalar;
use function preg_match;
use function strval;

/**
 * Property entity hydrator
 *
 * @template T of Entities\Property
 * @extends  Hydrators\Hydrator<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Property extends Hydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes
		= [
			0 => 'category',
			1 => 'identifier',
			2 => 'name',
			3 => 'settable',
			4 => 'queryable',
			5 => 'unit',
			6 => 'format',
			7 => 'invalid',
			8 => 'value',

			'data_type' => 'dataType',
			'scale' => 'scale',
		];

	protected function hydrateNameAttribute(Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
	}

	protected function hydrateSettableAttribute(Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('settable')) && boolval($attributes->get('settable'));
	}

	protected function hydrateQueryableAttribute(Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('queryable')) && boolval($attributes->get('queryable'));
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function hydrateDataTypeAttribute(
		Objects\IStandardObject $attributes,
	): Types\DataType|null
	{
		if (
			!is_scalar($attributes->get('data_type'))
			|| (string) $attributes->get('data_type') === ''
			|| Types\DataType::tryFrom((string) $attributes->get('data_type')) === null
		) {
			return null;
		}

		return Types\DataType::from((string) $attributes->get('data_type'));
	}

	protected function hydrateUnitAttribute(Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('unit'))
			|| (string) $attributes->get('unit') === ''
		) {
			return null;
		}

		return (string) $attributes->get('unit');
	}

	/**
	 * @throws Exceptions\JsonApiError
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function hydrateFormatAttribute(Objects\IStandardObject $attributes): string|null
	{
		$rawFormat = $attributes->get('format');
		$rawDataType = $attributes->get('data_type');

		if (
			!is_scalar($rawDataType)
			|| Types\DataType::tryFrom((string) $rawDataType) === null
		) {
			return null;
		}

		$dataType = Types\DataType::from((string) $rawDataType);

		if (is_array($rawFormat)) {
			if (
				$dataType === Types\DataType::ENUM
				|| $dataType === Types\DataType::BUTTON
				|| $dataType === Types\DataType::SWITCH
				|| $dataType === Types\DataType::COVER
			) {
				$plainFormat = implode(',', array_map(static function ($item): string {
					if (is_array($item) || $item instanceof Utils\ArrayHash) {
						return implode(
							':',
							array_map(
								static fn (string|array|int|float|bool|Utils\ArrayHash|null $part): string => is_array(
									$part,
								) || $part instanceof Utils\ArrayHash
									? implode('|', (array) $part)
									: ($part !== null ? strval(
										$part,
									) : ''),
								(array) $item,
							),
						);
					}

					return strval($item);
				}, $rawFormat));

				if (
					preg_match(Metadata\Constants::VALUE_FORMAT_STRING_ENUM, $plainFormat) === 1
					|| preg_match(Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM, $plainFormat) === 1
				) {
					return $plainFormat;
				}

				throw new Exceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//devices-module.base.messages.invalidAttribute.heading')),
					strval($this->translator->translate('//devices-module.base.messages.invalidAttribute.message')),
					[
						'pointer' => '/data/attributes/format',
					],
				);
			} elseif (
				in_array(
					$dataType,
					[
						Types\DataType::CHAR,
						Types\DataType::UCHAR,
						Types\DataType::SHORT,
						Types\DataType::USHORT,
						Types\DataType::INT,
						Types\DataType::UINT,
						Types\DataType::FLOAT,
					],
					true,
				)
			) {
				$plainFormat = implode(':', array_map(static function ($item): string {
					if (is_array($item) || $item instanceof Utils\ArrayHash) {
						return implode(
							'|',
							array_map(
								static fn ($part): string|int|float => is_array($part) ? implode($part) : $part,
								(array) $item,
							),
						);
					}

					return strval($item);
				}, $rawFormat));

				if (preg_match(Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE, $plainFormat) === 1) {
					return $plainFormat;
				}

				throw new Exceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//devices-module.base.messages.invalidAttribute.heading')),
					strval($this->translator->translate('//devices-module.base.messages.invalidAttribute.message')),
					[
						'pointer' => '/data/attributes/format',
					],
				);
			}

			return null;
		} elseif (!is_scalar($rawFormat) || (string) $rawFormat === '') {
			return null;
		}

		return (string) $rawFormat;
	}

	protected function hydrateInvalidAttribute(Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('invalid'))
			|| (string) $attributes->get('invalid') === ''
		) {
			return null;
		}

		return (string) $attributes->get('invalid');
	}

	protected function hydrateScaleAttribute(Objects\IStandardObject $attributes): int|null
	{
		if (
			!is_scalar($attributes->get('scale'))
			|| (string) $attributes->get('scale') === ''
		) {
			return null;
		}

		return (int) $attributes->get('scale');
	}

	protected function hydrateValueAttribute(Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('value'))
			|| (string) $attributes->get('value') === ''
		) {
			return null;
		}

		return (string) $attributes->get('value');
	}

}
