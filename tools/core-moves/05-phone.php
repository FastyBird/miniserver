<?php declare(strict_types = 1);

/**
 * E3.5 (#498): Phone.
 *
 * Written by hand from the approved census, section "Phone (8 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, and nothing else.
 * The first DBAL type (registered by `Phone::class` in `CoreExtension::afterCompile()`,
 * the type NAME string `'phone'` is a class constant and is untouched by this map) and
 * the first exceptions to leave the shared root. `Entities\Phone\TPhone` is NOT renamed
 * here -- policy B hands its rename (`PhoneContract`, per the census's name table) off to
 * #460. The other three `Phone`-named classes stutter against their own sub-namespace and
 * are renamed per the census's collision table: `Services\Phone\Phone` ->
 * `Phone\Services\PhoneNumberHelper`, `Types\Phone\Phone` -> `Phone\Types\PhoneType`.
 * `Entities\Phone\Phone` keeps its short name.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Entities\\Phone\\Phone' => 'FastyBird\\Core\\Phone\\Entities\\Phone',
		'FastyBird\\Core\\Entities\\Phone\\TPhone' => 'FastyBird\\Core\\Phone\\Entities\\TPhone',
		'FastyBird\\Core\\Exceptions\\NoValidCountry' => 'FastyBird\\Core\\Phone\\Exceptions\\NoValidCountry',
		'FastyBird\\Core\\Exceptions\\NoValidPhone' => 'FastyBird\\Core\\Phone\\Exceptions\\NoValidPhone',
		'FastyBird\\Core\\Exceptions\\NoValidType' => 'FastyBird\\Core\\Phone\\Exceptions\\NoValidType',
		'FastyBird\\Core\\Services\\Phone\\Phone' => 'FastyBird\\Core\\Phone\\Services\\PhoneNumberHelper',
		'FastyBird\\Core\\Subscribers\\Phone\\PhoneObjectSubscriber' => 'FastyBird\\Core\\Phone\\Subscribers\\PhoneObjectSubscriber',
		'FastyBird\\Core\\Types\\Phone\\Phone' => 'FastyBird\\Core\\Phone\\Types\\PhoneType',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
];
