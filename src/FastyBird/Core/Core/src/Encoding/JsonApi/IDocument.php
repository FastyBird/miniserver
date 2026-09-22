<?php declare(strict_types = 1);

/**
 * IDocument.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     common
 * @since          0.0.1
 *
 * @date           05.05.18
 */

namespace FastyBird\Core\Encoding\JsonApi;

/**
 * Response document interface
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IDocument
{

	// Reserved keyword
	public const string KEYWORD_LINKS = 'links';

	// Reserved keyword
	public const string KEYWORD_HREF = 'href';

	// Reserved keyword
	public const string KEYWORD_RELATIONSHIPS = 'relationships';

	// Reserved keyword
	public const string KEYWORD_SELF = 'self';

	// Reserved keyword
	public const string KEYWORD_FIRST = 'first';

	// Reserved keyword
	public const string KEYWORD_LAST = 'last';

	// Reserved keyword
	public const string KEYWORD_NEXT = 'next';

	// Reserved keyword
	public const string KEYWORD_PREV = 'prev';

	// Reserved keyword
	public const string KEYWORD_RELATED = 'related';

	// Reserved keyword
	public const string KEYWORD_TYPE = 'type';

	// Reserved keyword
	public const string KEYWORD_ID = 'id';

	// Reserved keyword
	public const string KEYWORD_ATTRIBUTES = 'attributes';

	// Reserved keyword
	public const string KEYWORD_META = 'meta';

	// Reserved keyword
	public const string KEYWORD_ALIASES = 'aliases';

	// Reserved keyword
	public const string KEYWORD_PROFILE = 'profile';

	// Reserved keyword
	public const string KEYWORD_DATA = 'data';

	// Reserved keyword
	public const string KEYWORD_INCLUDED = 'included';

	// Reserved keyword
	public const string KEYWORD_JSON_API = 'jsonapi';

	// Reserved keyword
	public const string KEYWORD_VERSION = 'version';

	// Reserved keyword
	public const string KEYWORD_ERRORS = 'errors';

	// Reserved keyword
	public const string KEYWORD_ERRORS_ID = 'id';

	// Reserved keyword
	public const string KEYWORD_ERRORS_TYPE = 'type';

	// Reserved keyword
	public const string KEYWORD_ERRORS_STATUS = 'status';

	// Reserved keyword
	public const string KEYWORD_ERRORS_CODE = 'code';

	// Reserved keyword
	public const string KEYWORD_ERRORS_TITLE = 'title';

	// Reserved keyword
	public const string KEYWORD_ERRORS_DETAIL = 'detail';

	// Reserved keyword
	public const string KEYWORD_ERRORS_META = 'meta';

	// Reserved keyword
	public const string KEYWORD_ERRORS_SOURCE = 'source';

	// Reserved keyword
	public const string KEYWORD_ERRORS_ABOUT = 'about';

	// Reserved keyword
	public const string KEYWORD_POINTER = 'pointer';

	// Reserved keyword
	public const string KEYWORD_PARAMETER = 'parameter';

	// Include path separator
	public const string PATH_SEPARATOR = '.';

	public function hasResource(): bool;

	public function getResource(): Objects\IResourceObject;

	public function hasResources(): bool;

	/**
	 * @phpstan-return Objects\IResourceObjectCollection<int, Objects\IResourceObject>
	 */
	public function getResources(): Objects\IResourceObjectCollection;

	/**
	 * @phpstan-return Objects\IStandardObject|Objects\IStandardObjectCollection<int, Objects\IStandardObject>|null
	 */
	public function getData(): Objects\IStandardObject|Objects\IStandardObjectCollection|null;

	public function hasLinks(): bool;

	/**
	 * @phpstan-return Objects\ILinkObjectCollection<string, Objects\ILinkObject|string>
	 */
	public function getLinks(): Objects\ILinkObjectCollection;

	public function hasMeta(): bool;

	/**
	 * @phpstan-return Objects\IMetaObjectCollection<string, Objects\IMetaObject>
	 */
	public function getMeta(): Objects\IMetaObjectCollection;

	public function hasIncluded(): bool;

	/**
	 * @phpstan-return Objects\IResourceObjectCollection<int, Objects\IResourceObject>
	 */
	public function getIncluded(): Objects\IResourceObjectCollection;

	public function hasErrors(): bool;

	/**
	 * @phpstan-return Objects\IErrorObjectCollection<int, Objects\IErrorObject>
	 */
	public function getErrors(): Objects\IErrorObjectCollection;

}
