<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Boot;

use FastyBird\Core\Boot;
use Nette;
use PHPUnit\Framework\TestCase;
use function file_put_contents;
use function mkdir;
use function realpath;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR;

final class BootstrapConfigFilesTest extends TestCase
{

	private string $workDir;

	protected function setUp(): void
	{
		parent::setUp();

		$this->workDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'fb-config-' . uniqid();

		mkdir($this->workDir . DIRECTORY_SEPARATOR . 'extension', 0777, true);
		mkdir($this->workDir . DIRECTORY_SEPARATOR . 'app', 0777, true);
		mkdir($this->workDir . DIRECTORY_SEPARATOR . 'overrides', 0777, true);

		file_put_contents(
			$this->workDir . DIRECTORY_SEPARATOR . 'extension' . DIRECTORY_SEPARATOR . 'common.neon',
			"parameters:\n",
		);
		file_put_contents(
			$this->workDir . DIRECTORY_SEPARATOR . 'extension' . DIRECTORY_SEPARATOR . 'defaults.neon',
			"parameters:\n",
		);
		file_put_contents(
			$this->workDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common.neon',
			"parameters:\n",
		);
		file_put_contents(
			$this->workDir . DIRECTORY_SEPARATOR . 'overrides' . DIRECTORY_SEPARATOR . 'local.neon',
			"parameters:\n",
		);
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
			[$this->workDir . DIRECTORY_SEPARATOR . 'extension', ['common.neon', 'defaults.neon']],
			[$this->workDir . DIRECTORY_SEPARATOR . 'app', ['common.neon', 'defaults.neon']],
			[$this->workDir . DIRECTORY_SEPARATOR . 'overrides', ['common.neon', 'defaults.neon', 'local.neon']],
		]);

		self::assertSame(
			[
				$this->workDir . DIRECTORY_SEPARATOR . 'extension' . DIRECTORY_SEPARATOR . 'common.neon',
				$this->workDir . DIRECTORY_SEPARATOR . 'extension' . DIRECTORY_SEPARATOR . 'defaults.neon',
				$this->workDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common.neon',
				$this->workDir . DIRECTORY_SEPARATOR . 'overrides' . DIRECTORY_SEPARATOR . 'local.neon',
			],
			$files,
		);
	}

	public function testSameDirectoryReachedThroughSymlinkIsLoadedOnce(): void
	{
		symlink($this->workDir . DIRECTORY_SEPARATOR . 'app', $this->workDir . DIRECTORY_SEPARATOR . 'link');

		$files = Boot\Bootstrap::resolveConfigFiles([
			[$this->workDir . DIRECTORY_SEPARATOR . 'app', ['common.neon', 'defaults.neon']],
			[$this->workDir . DIRECTORY_SEPARATOR . 'link', ['common.neon', 'defaults.neon', 'local.neon']],
		]);

		self::assertSame([$this->workDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common.neon'], $files);
	}

}
