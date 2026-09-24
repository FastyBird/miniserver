<?php declare(strict_types = 1);

/**
 * TProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Core\Values\Types;
use TypeError;
use ValueError;
use function array_merge;
use function boolval;
use function is_string;
use function strval;

/**
 * Property message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
trait TProperty
{

	/**
	 * @return array<string, (string|array<string>|array<float>|array<null>|bool|Types\DataType|null)>
	 *
	 * @throws Exceptions\ParseMessage
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function handlePropertyConfiguration(
		Queue\Messages\Property $message,
	): array
	{
		$toUpdate = [];

		foreach ($message->getAttributes() as $attribute) {
			if (
				$attribute->getAttribute() === Queue\Messages\PropertyAttribute::NAME
				&& is_string($attribute->getValue())
			) {
				$toUpdate = array_merge($toUpdate, [
					'name' => strval($attribute->getValue()),
				]);
			}

			if ($attribute->getAttribute() === Queue\Messages\PropertyAttribute::SETTABLE) {
				$toUpdate = array_merge($toUpdate, [
					'settable' => boolval($attribute->getValue()),
				]);
			}

			if ($attribute->getAttribute() === Queue\Messages\PropertyAttribute::QUERYABLE) {
				$toUpdate = array_merge($toUpdate, [
					'queryable' => boolval($attribute->getValue()),
				]);
			}

			if (
				$attribute->getAttribute() === Queue\Messages\PropertyAttribute::DATA_TYPE
				&& is_string($attribute->getValue())
				&& Types\DataType::tryFrom(strval($attribute->getValue())) !== null
			) {
				$toUpdate = array_merge($toUpdate, [
					'dataType' => Types\DataType::from(strval($attribute->getValue())),
				]);
			}

			if ($attribute->getAttribute() === Queue\Messages\PropertyAttribute::FORMAT) {
				$toUpdate = array_merge($toUpdate, [
					'format' => $attribute->getValue(),
				]);
			}

			if ($attribute->getAttribute() === Queue\Messages\PropertyAttribute::UNIT) {
				$toUpdate = array_merge($toUpdate, [
					'unit' => $attribute->getValue(),
				]);
			}
		}

		return $toUpdate;
	}

}
