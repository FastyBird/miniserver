<?php declare(strict_types = 1);

use FastyBird\MiniServer\Tests\Cases\Application\AccountsUiRootsProbe;

/**
 * bootstrap-accounts-ui-roots.php
 *
 * Boots the application as public/index.php does and gives every concrete document of the Accounts module (Account, Email,
 * Identity, Role) and of the Ui module's Document interface (dashboards, tabs, groups, widgets, data sources and displays), in every package, its fields
 * one at a time in every wrong form there is, next to the whole document, through Core's DocumentFactory. It prints,
 * as JSON on stdout, what each input made: the JSON the exchange and the API encode (Nette's Json::encode of toArray())
 * and serialize() of the document, or that the input was refused, and the first line of what was said.
 *
 * Run as a child process by AccountsUiRootsPinTest. Run by hand it writes the pins, the file the test compares with:
 *
 *   FB_APP_DIR=$PWD FB_APP_PARAMETER__SECURITY_SIGNATURE=x php tests/cases/application/bootstrap-accounts-ui-roots.php \
 *       > tests/cases/application/fixtures/accounts-ui-roots.json
 *
 * With --messages it adds the first line of what a refusal said, which AccountsUiRootsPinTest checks and the pins do not
 * hold; with --census which side of Mapper::map()'s dispatch each document is on, and with --objects what a few objects
 * of other classes make.
 *
 * The pins are what orisai made of these inputs: the file was written with this command on d7e28415c, the last commit on
 * which the roots and everything extending them were still mapped by orisai (#724 PR4f). The inputs and the report
 * are in AccountsUiRootsProbe, which the script runs.
 */

// A diagnostic printed to stdout would be interleaved with the JSON this script writes there.
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

exit(AccountsUiRootsProbe::run());
