<?php declare(strict_types = 1);

use FastyBird\MiniServer\Tests\Cases\Application\StateRootsProbe;

/**
 * bootstrap-state-roots.php
 *
 * Boots the application as public/index.php does and gives every state that extends the RedisDb plugin's State, in
 * every package, the same inputs, the way the plugin's factory gets them from Redis (JSON text) and the way the
 * property managers hand them to the mapper (an array that can hold objects). It prints, as JSON on stdout, what each
 * input made: the JSON the API encodes (Nette's Json::encode of toArray()) and serialize() of the state, or that the
 * input was refused.
 *
 * Run as a child process by StateRootsPinTest. Run by hand it writes the pins, the file the test compares with:
 *
 *   FB_APP_DIR=$PWD FB_APP_PARAMETER__SECURITY_SIGNATURE=x php tests/cases/application/bootstrap-state-roots.php \
 *       > tests/cases/application/fixtures/state-roots.json
 *
 * With --messages it adds the first line of what a refusal said, with --census which side of Mapper::map()'s dispatch
 * each state is on, and with --objects what a few objects of other classes make: StateRootsPinTest checks them and the
 * pins do not hold them.
 *
 * The pins are what orisai made of these inputs: the file was written with this command on 2e495de6c, the last commit
 * on which the state roots, and everything extending them, were still mapped by orisai (#724 PR4d). The inputs and the
 * report are in StateRootsProbe, which the script runs.
 */

// A diagnostic printed to stdout would be interleaved with the JSON this script writes there.
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

exit(StateRootsProbe::run());
