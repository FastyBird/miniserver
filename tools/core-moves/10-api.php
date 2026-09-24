<?php declare(strict_types = 1);

/**
 * E3.10 (#503): Api.
 *
 * Written by hand from the approved census, section "Api (55 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md. 55 files:
 * `Encoding\JsonApi\*` (36, including the 16 `Objects\I*` interfaces the census's interface
 * policy B keeps unrenamed -- they are all single-implementer hand-off types), `Persistence\
 * JsonApi\Hydrators\*` (13), `Exceptions\{JsonApi, JsonApiError, JsonApiMultipleError}` (3),
 * `Helpers\JsonApi\CrudReader` (1), and 2 renamed for stutter (per the census's "Stutter and
 * banned-segment renames" table): `Middleware\JsonApi\JsonApi` -- which also carries a
 * `// phpcs:ignoreFile` directive (#489), left untouched by this move -- becomes
 * `Middleware\JsonApiMiddleware`, and `Schemas\JsonApi\JsonApi` becomes `Schemas\JsonApiSchema`.
 *
 * `Encoding\JsonApi\*` and `Persistence\JsonApi\Hydrators\*` both fully vacate (the sibling
 * `Encoding\WebSockets\*` and `Persistence\{Rules,Crud,Query}` namespaces are unrelated, not
 * part of this map), so every consumer's import resolves through the `classes` map alone; no
 * `normalize` entry is needed.
 *
 * `src/Translations/JsonApi/jsonApi.en_US.neon` is not a class (the census's table lists only
 * class-bearing files); the issue (#503) requires it move into the capability too, keeping its
 * file name and `jsonApi` translation domain. Moved via the `files` map key to
 * `src/Api/Translations/jsonApi.en_US.neon`, matching the target namespace's directory
 * (`FastyBird\Core\Api\` => `src/Api/`, per Core's own PSR-4 root `FastyBird\Core\` => `src/`).
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Encoding\\JsonApi\\Builder' => 'FastyBird\\Core\\Api\\Encoding\\Builder',
		'FastyBird\\Core\\Encoding\\JsonApi\\Document' => 'FastyBird\\Core\\Api\\Encoding\\Document',
		'FastyBird\\Core\\Encoding\\JsonApi\\Encoder' => 'FastyBird\\Core\\Api\\Encoding\\Encoder',
		'FastyBird\\Core\\Encoding\\JsonApi\\IDocument' => 'FastyBird\\Core\\Api\\Encoding\\IDocument',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\ErrorObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ErrorObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\ErrorObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ErrorObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IErrorObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IErrorObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IErrorObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IErrorObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\ILinkObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ILinkObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\ILinkObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ILinkObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IMetaObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IMetaObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IMetaObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IMetaObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IRelationshipObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IRelationshipObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IRelationshipObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IRelationshipObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IResourceIdentifierCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IResourceIdentifierCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IResourceIdentifierObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IResourceIdentifierObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IResourceObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IResourceObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IResourceObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IResourceObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\ISourceObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ISourceObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IStandardObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IStandardObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\IStandardObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\IStandardObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\LinkObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\LinkObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\LinkObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\LinkObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\MetaObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\MetaObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\MetaObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\MetaObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\Obj' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\Obj',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\RelationshipObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\RelationshipObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\RelationshipObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\RelationshipObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\ResourceIdentifierCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceIdentifierCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\ResourceIdentifierObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceIdentifierObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\ResourceObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\ResourceObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\SourceObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\SourceObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\StandardObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\StandardObject',
		'FastyBird\\Core\\Encoding\\JsonApi\\Objects\\StandardObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\StandardObjectCollection',
		'FastyBird\\Core\\Encoding\\JsonApi\\SchemaContainer' => 'FastyBird\\Core\\Api\\Encoding\\SchemaContainer',
		'FastyBird\\Core\\Exceptions\\JsonApi' => 'FastyBird\\Core\\Api\\Exceptions\\JsonApi',
		'FastyBird\\Core\\Exceptions\\JsonApiError' => 'FastyBird\\Core\\Api\\Exceptions\\JsonApiError',
		'FastyBird\\Core\\Exceptions\\JsonApiMultipleError' => 'FastyBird\\Core\\Api\\Exceptions\\JsonApiMultipleError',
		'FastyBird\\Core\\Helpers\\JsonApi\\CrudReader' => 'FastyBird\\Core\\Api\\Helpers\\CrudReader',
		'FastyBird\\Core\\Middleware\\JsonApi\\JsonApi' => 'FastyBird\\Core\\Api\\Middleware\\JsonApiMiddleware',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Container' => 'FastyBird\\Core\\Api\\Hydrators\\Container',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\ArrayField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\ArrayField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\BackedEnumField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\BackedEnumField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\BooleanField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\BooleanField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\CollectionField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\CollectionField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\DateTimeField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\DateTimeField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\EntityField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\EntityField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\Field' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\Field',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\MixedField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\MixedField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\NumberField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\NumberField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\SingleEntityField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\SingleEntityField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Fields\\TextField' => 'FastyBird\\Core\\Api\\Hydrators\\Fields\\TextField',
		'FastyBird\\Core\\Persistence\\JsonApi\\Hydrators\\Hydrator' => 'FastyBird\\Core\\Api\\Hydrators\\Hydrator',
		'FastyBird\\Core\\Schemas\\JsonApi\\JsonApi' => 'FastyBird\\Core\\Api\\Schemas\\JsonApiSchema',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [
		'src/FastyBird/Core/Core/src/Translations/JsonApi/jsonApi.en_US.neon' => 'src/FastyBird/Core/Core/src/Api/Translations/jsonApi.en_US.neon',
	],
];
