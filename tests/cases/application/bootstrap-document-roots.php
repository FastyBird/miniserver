<?php declare(strict_types = 1);

use FastyBird\MiniServer\Tests\Cases\Application\DocumentRootsProbe;

/**
 * bootstrap-document-roots.php
 *
 * Boots the application as public/index.php does and gives every concrete document that extends the Devices
 * module's Connector and Device documents, in every package, the same inputs through Core's DocumentFactory. It
 * prints, as JSON on stdout, what each input made: the JSON the exchange and the API encode (Nette's Json::encode of
 * toArray()) and serialize() of the document, or that the input was refused, and the first line of what was said.
 *
 * Run as a child process by DocumentRootsPinTest. Run by hand it writes the pins, the file the test compares with:
 *
 *   FB_APP_DIR=$PWD FB_APP_PARAMETER__SECURITY_SIGNATURE=x php tests/cases/application/bootstrap-document-roots.php \
 *       > tests/cases/application/fixtures/document-roots.json
 *
 * With --messages it adds the first line of what a refusal said, which DocumentRootsPinTest checks and the pins do not hold.
 *
 * The pins are what orisai made of these inputs: the file was written with this command on 45787d7b4, the last commit
 * on which the two roots and everything extending them were still mapped by orisai (#724 PR4), and on every commit of
 * the branch before the roots moved. The inputs and the report are in DocumentRootsProbe, which the script runs.
 */

// A diagnostic printed to stdout would be interleaved with the JSON this script writes there.
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

exit(DocumentRootsProbe::run());
