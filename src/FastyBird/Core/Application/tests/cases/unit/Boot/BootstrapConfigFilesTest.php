<?php declare(strict_types = 1);

namespace FastyBird\Core\Application\Tests\Cases\Unit\Boot;

use FastyBird\Core\Application\Boot;
use Nette;
use PHPUnit\Framework\TestCase;
use function file_put_contents;
use function mkdir;
use function realpath;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR as DS;

final class BootstrapConfigFilesTest extends TestCase
{

	private string $workDir;

	protected function setUp(): void
	{
		parent::setUp();

		$this->workDir = realpath(sys_get_temp_dir()) . DS . 'fb-config-' . uniqid();

		mkdir($this->workDir . DS . 'extension', 0777, true);
		mkdir($this->workDir . DS . 'app', 0777, true);
		mkdir($this->workDir . DS . 'overrides', 0777, true);

		file_put_contents($this->workDir . DS . 'extension' . DS . 'common.neon', "parameters:\n");
		file_put_contents($this->workDir . DS . 'extension' . DS . 'defaults.neon', "parameters:\n");
		file_put_contents($this->workDir . DS . 'app' . DS . 'common.neon', "parameters:\n");
		file_put_contents($this->workDir . DS . 'overrides' . DS . 'local.neon', "parameters:\n");
	}

	/**
	 * @throws Nette\IOException
	 */
	protected function tearDown(): void
	{
		Nette\Utils\FileSystem::delete($this->workDir);

		parent::tearDown();
	}

	public function testMissingFilesAreSkippedAndOrderIsPreserved(): void
	{
		$files = Boot\Bootstrap::resolveConfigFiles([
			[$this->workDir . DS . 'extension', ['common.neon', 'defaults.neon']],
			[$this->workDir . DS . 'app', ['common.neon', 'defaults.neon']],
			[$this->workDir . DS . 'overrides', ['common.neon', 'defaults.neon', 'local.neon']],
		]);

		self::assertSame(
			[
				$this->workDir . DS . 'extension' . DS . 'common.neon',
				$this->workDir . DS . 'extension' . DS . 'defaults.neon',
				$this->workDir . DS . 'app' . DS . 'common.neon',
				$this->workDir . DS . 'overrides' . DS . 'local.neon',
			],
			$files,
		);
	}

	public function testSameDirectoryReachedThroughSymlinkIsLoadedOnce(): void
	{
		symlink($this->workDir . DS . 'app', $this->workDir . DS . 'link');

		$files = Boot\Bootstrap::resolveConfigFiles([
			[$this->workDir . DS . 'app', ['common.neon', 'defaults.neon']],
			[$this->workDir . DS . 'link', ['common.neon', 'defaults.neon', 'local.neon']],
		]);

		self::assertSame([$this->workDir . DS . 'app' . DS . 'common.neon'], $files);
	}

}
