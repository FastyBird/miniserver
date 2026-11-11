<?php declare(strict_types = 1);

use FastyBird\MiniServer\Tests\Cases\Application\BridgeMappingProbe;

/**
 * bootstrap-bridge-mapping.php
 *
 * Boots the application as public/index.php does and gives the mapping classes of the two HomeKit bridges that read a
 * mapping file of their connector (Shelly, generations 1 and 2, and Viera) the file as it is and, for every class, a
 * sample taken from it with its fields one at a time in every wrong form there is, through Core's Mapper. It prints, as
 * JSON on stdout, what each input made: serialize() of the object, or that the input was refused, and the first line of
 * what was said.
 *
 * Run as a child process by BridgeMappingPinTest. Run by hand it writes the pins, the file the test compares with:
 *
 *   FB_APP_DIR=$PWD FB_APP_PARAMETER__SECURITY_SIGNATURE=x php tests/cases/application/bootstrap-bridge-mapping.php \
 *       > tests/cases/application/fixtures/bridge-mapping.json
 *
 * With --messages it adds the first line of what a refusal said, which BridgeMappingPinTest checks and the pins do not
 * hold; with --census which side of Mapper::map()'s dispatch each class is on.
 *
 * The pins are what orisai made of these inputs: the file was written with this command on d7e28415c, the last commit on
 * which the mapping classes were still mapped by orisai (#724 PR5). The inputs and the report are in
 * BridgeMappingProbe, which the script runs.
 */

// A diagnostic printed to stdout would be interleaved with the JSON this script writes there.
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

exit(BridgeMappingProbe::run());
