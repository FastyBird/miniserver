<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
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
use FastyBird\Module\Ui\Schemas as UiSchemas;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Property data source entity schema
 *
 * @template T of Entities\Widgets\DataSources\Property
 * @extends  UiSchemas\Widgets\DataSources\DataSource<T>
 *
 * @package          FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage       Schemas
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Property extends UiSchemas\Widgets\DataSources\DataSource
{

	/**
	 * Define relationships names
	 */
	public const string RELATIONSHIPS_PROPERTY = 'property';

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getRelationships($resource, $context), [
			self::RELATIONSHIPS_PROPERTY => [
				self::RELATIONSHIP_DATA => $resource->getProperty(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => false,
			],
		]);
	}

}
