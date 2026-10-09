<?php declare(strict_types = 1);

/**
 * #676 (follow-up of escalation #672, handed off by #645): Phone\Entities\Phone::$numberOfLeadingZeros
 * gets the default `null`. The change list format is documented in tools/api-surface.php.
 *
 * fromNumber() writes the property only when libphonenumber reports leading zeros (two or more),
 * so without a default it stayed uninitialised for every other number and reading it threw. The
 * property keeps its type (`?int`), its visibility (`public private(set)`) and `final`; only
 * hasDefault (false -> true) and the recorded default (no value -> `null`) change. 12-accessors.php
 * still records the row as #644 left it, with no default (#672).
 */

return [
	'changed' => [
		'FastyBird\\Core\\Phone\\Entities\\Phone::$numberOfLeadingZeros' => [
			'hasDefault' => true,
			'default' => 'null',
		],
	],
];
