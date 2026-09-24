<?php declare(strict_types = 1);

/**
 * E3.6 (#499): Values.
 *
 * Written by hand from the approved census, section "Values (29 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, and nothing else.
 * The largest sweep of the epic: 29 files, ~710 referencing files across 27 packages.
 * Clears the `as MetadataTypes` baseline entries (~711) and most of `Utilities\Tools`
 * (104). None of the 29 are renamed (0 I/T types, per the #458 pilot checkpoint's
 * per-capability table) -- every short name is kept, only the namespace moves.
 *
 * The census's collision table resolves the two `DataType`s by sub-namespace, not by
 * rename: `Types\Metadata\DataType` (enum) -> `Values\Types\DataType`,
 * `Utilities\Tools\DataType` (class) -> `Values\Utilities\DataType`.
 *
 * `Types/Metadata/Sources/{Connector,Plugin,Bridge,Module}.php` are 4 of the census's 5
 * files expected to fall below git's rename threshold (upper bound, measured against the
 * whole epic); listed in the PR description with `-M10%` proof.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Exceptions\\InvalidData' => 'FastyBird\\Core\\Values\\Exceptions\\InvalidData',
		'FastyBird\\Core\\Exceptions\\InvalidValue' => 'FastyBird\\Core\\Values\\Exceptions\\InvalidValue',
		'FastyBird\\Core\\Formats\\Tools\\CombinedEnum' => 'FastyBird\\Core\\Values\\Formats\\CombinedEnum',
		'FastyBird\\Core\\Formats\\Tools\\CombinedEnumItem' => 'FastyBird\\Core\\Values\\Formats\\CombinedEnumItem',
		'FastyBird\\Core\\Formats\\Tools\\NumberRange' => 'FastyBird\\Core\\Values\\Formats\\NumberRange',
		'FastyBird\\Core\\Formats\\Tools\\StringEnum' => 'FastyBird\\Core\\Values\\Formats\\StringEnum',
		'FastyBird\\Core\\Schemas\\Tools\\Validator' => 'FastyBird\\Core\\Values\\Schemas\\Validator',
		'FastyBird\\Core\\Transformers\\Tools\\DataTypeTransformer' => 'FastyBird\\Core\\Values\\Transformers\\DataTypeTransformer',
		'FastyBird\\Core\\Transformers\\Tools\\EquationTransformer' => 'FastyBird\\Core\\Values\\Transformers\\EquationTransformer',
		'FastyBird\\Core\\Transformers\\Tools\\HsbTransformer' => 'FastyBird\\Core\\Values\\Transformers\\HsbTransformer',
		'FastyBird\\Core\\Transformers\\Tools\\HsiTransformer' => 'FastyBird\\Core\\Values\\Transformers\\HsiTransformer',
		'FastyBird\\Core\\Transformers\\Tools\\MiredTransformer' => 'FastyBird\\Core\\Values\\Transformers\\MiredTransformer',
		'FastyBird\\Core\\Transformers\\Tools\\RgbTransformer' => 'FastyBird\\Core\\Values\\Transformers\\RgbTransformer',
		'FastyBird\\Core\\Transformers\\Tools\\Transformer' => 'FastyBird\\Core\\Values\\Transformers\\Transformer',
		'FastyBird\\Core\\Types\\Metadata\\DataType' => 'FastyBird\\Core\\Values\\Types\\DataType',
		'FastyBird\\Core\\Types\\Metadata\\DataTypeShort' => 'FastyBird\\Core\\Values\\Types\\DataTypeShort',
		'FastyBird\\Core\\Types\\Metadata\\Payloads\\Button' => 'FastyBird\\Core\\Values\\Types\\Payloads\\Button',
		'FastyBird\\Core\\Types\\Metadata\\Payloads\\Cover' => 'FastyBird\\Core\\Values\\Types\\Payloads\\Cover',
		'FastyBird\\Core\\Types\\Metadata\\Payloads\\Payload' => 'FastyBird\\Core\\Values\\Types\\Payloads\\Payload',
		'FastyBird\\Core\\Types\\Metadata\\Payloads\\Switcher' => 'FastyBird\\Core\\Values\\Types\\Payloads\\Switcher',
		'FastyBird\\Core\\Types\\Metadata\\Sources\\Addon' => 'FastyBird\\Core\\Values\\Types\\Sources\\Addon',
		'FastyBird\\Core\\Types\\Metadata\\Sources\\Automator' => 'FastyBird\\Core\\Values\\Types\\Sources\\Automator',
		'FastyBird\\Core\\Types\\Metadata\\Sources\\Bridge' => 'FastyBird\\Core\\Values\\Types\\Sources\\Bridge',
		'FastyBird\\Core\\Types\\Metadata\\Sources\\Connector' => 'FastyBird\\Core\\Values\\Types\\Sources\\Connector',
		'FastyBird\\Core\\Types\\Metadata\\Sources\\Module' => 'FastyBird\\Core\\Values\\Types\\Sources\\Module',
		'FastyBird\\Core\\Types\\Metadata\\Sources\\Plugin' => 'FastyBird\\Core\\Values\\Types\\Sources\\Plugin',
		'FastyBird\\Core\\Types\\Metadata\\Sources\\Source' => 'FastyBird\\Core\\Values\\Types\\Sources\\Source',
		'FastyBird\\Core\\Utilities\\Tools\\DataType' => 'FastyBird\\Core\\Values\\Utilities\\DataType',
		'FastyBird\\Core\\Utilities\\Tools\\Value' => 'FastyBird\\Core\\Values\\Utilities\\Value',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
];
