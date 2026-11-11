<?php declare(strict_types = 1);

use FastyBird\MiniServer\Tests\Cases\Application\TriggerRootsProbe;

/**
 * bootstrap-trigger-roots.php
 *
 * Boots the application as public/index.php does and gives every concrete document of the six trees of the Triggers
 * module (Trigger, Notification, Action, Condition, Control and the Action of a control), in every package, its fields
 * one at a time in every wrong form there is, next to the whole document, through Core's DocumentFactory. It prints,
 * as JSON on stdout, what each input made: the JSON the exchange and the API encode (Nette's Json::encode of toArray())
 * and serialize() of the document, or that the input was refused, and the first line of what was said.
 *
 * Run as a child process by TriggerRootsPinTest. Run by hand it writes the pins, the file the test compares with:
 *
 *   FB_APP_DIR=$PWD FB_APP_PARAMETER__SECURITY_SIGNATURE=x php tests/cases/application/bootstrap-trigger-roots.php \
 *       > tests/cases/application/fixtures/trigger-roots.json
 *
 * With --messages it adds the first line of what a refusal said, which TriggerRootsPinTest checks and the pins do not
 * hold; with --census which side of Mapper::map()'s dispatch each document is on, and with --objects what a few objects
 * of other classes make.
 *
 * The pins are what orisai made of these inputs: the file was written with this command on ff789635c, the last commit on
 * which the roots and everything extending them were still mapped by orisai (#724 PR4e). The inputs and the report
 * are in TriggerRootsProbe, which the script runs.
 */

// A diagnostic printed to stdout would be interleaved with the JSON this script writes there.
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

exit(TriggerRootsProbe::run());
