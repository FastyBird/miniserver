<?php declare(strict_types = 1);

/**
 * ConnectorProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Schemas\Widgets\DataSources;

use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Router as DevicesRouter;
use Neomerx\JsonApi;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Connector property data source entity schema
 *
 * @template T of Entities\Widgets\DataSources\ConnectorProperty
 * @extends  Property<T>
 *
 * @package          FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage       Schemas
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorProperty extends Property
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const SCHEMA_TYPE = Sources\Bridge::DEVICES_MODULE_UI_MODULE->value . '/data-source/' . Entities\Widgets\DataSources\ConnectorProperty::TYPE;

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_PROPERTY = 'property';

	public function __construct(
		private readonly DevicesModels\Configuration\Connectors\Properties\Repository $connectorsPropertiesRepository,
		private readonly DevicesModels\States\ConnectorPropertiesManager $connectorPropertiesManager,
		Routing\IRouter $router,
	)
	{
		parent::__construct($router);
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\DataSources\ConnectorProperty::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		$attributes = parent::getAttributes($resource, $context);

		if ($resource->getProperty() instanceof DevicesEntities\Connectors\Properties\Dynamic) {
			$property = $this->connectorsPropertiesRepository->find(
				$resource->getProperty()->getId(),
				DevicesDocuments\Connectors\Properties\Dynamic::class,
			);

			$state = $property !== null ? $this->connectorPropertiesManager->readState($property) : null;

			return array_merge(
				(array) $attributes,
				[
					'value' => $state !== null && $state->isValid() ? Utilities\Value::flattenValue(
						$state->getRead()->getExpectedValue() ?? $state->getRead()->getActualValue(),
					) : null,
				],
			);
		} elseif ($resource->getProperty() instanceof DevicesEntities\Connectors\Properties\Variable) {
			return array_merge(
				(array) $attributes,
				[
					'value' => Utilities\Value::flattenValue($resource->getProperty()->getValue()),
				],
			);
		}

		return $attributes;
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_PROPERTY) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CONNECTOR_PROPERTY,
					[
						DevicesRouter\ApiRoutes::URL_CONNECTOR_ID => $resource->getProperty()->getConnector()->getId()->toString(),
						DevicesRouter\ApiRoutes::URL_ITEM_ID => $resource->getProperty()->getId()->toString(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($resource, $name);
	}

}
