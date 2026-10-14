<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Security;

use FastyBird\Core\Logging;
use FastyBird\Core\Security\Identity;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Tests\Cases\Unit\DbTestCase;
use Override;
use RuntimeException;
use Sentry;
use stdClass;
use Throwable;
use Tracy;
use function bin2hex;
use function file_get_contents;
use function ini_get;
use function ini_set;
use function is_file;
use function is_string;
use function random_bytes;
use function str_contains;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function var_export;

/**
 * A password a sign-in or a password change passes along stays out of what is written about an
 * error raised on the way: the blue screen Core's logger writes and the stack trace of a Sentry
 * event, which both show the arguments of every frame. The password is generated per test, so the
 * source of the test's own frame, which a blue screen shows as well, does not contain it.
 *
 * A stack trace carries the arguments only with zend.exception_ignore_args off, as the application
 * images leave it (they ship no php.ini). php.ini-production turns it on, and so does the CI runner's
 * PHP, so the tests turn it off for themselves; each checks that the arguments are there.
 */
final class PasswordInErrorReportsTest extends DbTestCase
{

	// An identity of sql/dummy.data.sql
	private const string UID = 'john.doe@fastybird.com';

	private string $password;

	private string $file;

	private string|false $ignoreArgs = false;

	/**
	 * @throws Throwable
	 */
	#[Override]
	public function setUp(): void
	{
		parent::setUp();

		$this->ignoreArgs = ini_get('zend.exception_ignore_args');
		ini_set('zend.exception_ignore_args', '0');

		$this->password = 'pw-' . bin2hex(random_bytes(12));
		$this->file = sys_get_temp_dir() . '/' . uniqid('password-in-error-reports-', true) . '.html';
	}

	#[Override]
	protected function tearDown(): void
	{
		if (is_file($this->file)) {
			unlink($this->file);
		}

		if ($this->ignoreArgs !== false) {
			ini_set('zend.exception_ignore_args', $this->ignoreArgs);
		}

		parent::tearDown();
	}

	/**
	 * The stack holds the user's login(), the authenticator's authenticate() with the credentials
	 * and the identity's verifyPassword()
	 *
	 * @throws Throwable
	 */
	public function testBlueScreenOfAFailedSignInHidesThePassword(): void
	{
		$content = $this->renderBlueScreen($this->failSignIn());

		// The frames and their arguments are there: the identifier is not hidden
		self::assertTrue(str_contains($content, 'verifyPassword'), 'The identity is not in the stack');
		self::assertTrue(str_contains($content, 'authenticate'), 'The authenticator is not in the stack');
		self::assertTrue(str_contains($content, self::UID), 'The arguments are not in the blue screen');

		self::assertFalse(str_contains($content, $this->password), 'The password is in the blue screen');
	}

	/**
	 * @throws Throwable
	 */
	public function testSentryStackTraceOfAFailedSignInHidesThePassword(): void
	{
		$frames = $this->sentryFrames($this->failSignIn());

		self::assertTrue(str_contains($frames, 'verifyPassword'), 'The identity is not in the stack');
		self::assertTrue(str_contains($frames, self::UID), 'The arguments are not in the stack trace');

		self::assertFalse(str_contains($frames, $this->password), 'The password is in the stack trace');
	}

	/**
	 * An identity is created with its password, as the account creation command and the identity
	 * hydrator create one; the stack holds the identity's constructor and its setPassword()
	 *
	 * @throws Throwable
	 */
	public function testErrorReportsOfAFailedIdentityCreationHideThePassword(): void
	{
		try {
			new class (new Entities\Accounts\Account(), self::UID, $this->password) extends Entities\Identities\Identity {

				/**
				 * @throws RuntimeException
				 */
				#[Override]
				public function setSalt(string $salt): void
				{
					throw new RuntimeException('Salt could not be stored');
				}

			};

			self::fail('The identity creation did not fail');
		} catch (RuntimeException $ex) {
			$frames = $this->sentryFrames($ex);

			self::assertTrue(str_contains($frames, '__construct'), 'The identity is not in the stack');
			self::assertTrue(str_contains($frames, self::UID), 'The arguments are not in the stack trace');
			self::assertFalse(str_contains($frames, $this->password), 'The password is in the stack trace');

			self::assertFalse(
				str_contains($this->renderBlueScreen($ex), $this->password),
				'The password is in the blue screen',
			);
		}
	}

	/**
	 * An identity whose password was just set is an argument of the frames that store it, as the
	 * entity creator's and updater's are when the account creation command or a password change
	 * stores it; a blue screen dumps the identity's properties
	 *
	 * @throws Throwable
	 */
	public function testBlueScreenOfAFailedPasswordChangeHidesThePassword(): void
	{
		$identity = new Entities\Identities\Identity(new Entities\Accounts\Account(), self::UID, 'previous');
		$identity->setPassword($this->password);

		$store = static function (Entities\Identities\Identity $identity): never {
			throw new RuntimeException('Identity could not be stored');
		};

		try {
			$store($identity);
		} catch (RuntimeException $ex) {
			$content = $this->renderBlueScreen($ex);

			self::assertTrue(str_contains($content, self::UID), 'The identity is not in the blue screen');
			self::assertFalse(str_contains($content, $this->password), 'The password is in the blue screen');

			// The stored hash and salt still verify the password
			self::assertTrue($identity->verifyPassword($this->password));
		}
	}

	/**
	 * Signs in, with the container's user and the module's authenticator, as an identity whose salt
	 * cannot be read, so the error is raised inside the password's verification. The identity is
	 * the one the authenticator's lookup returns, as the entity manager already holds it
	 *
	 * @throws Throwable
	 */
	private function failSignIn(): Throwable
	{
		$identity = $this->getContainer()->getByType(Models\Entities\Identities\IdentitiesRepository::class)
			->findOneByUid(self::UID);
		self::assertNotNull($identity);

		$identity->setParam('salt', new stdClass());

		try {
			$this->getContainer()->getByType(Identity\User::class)->login(self::UID, $this->password);
		} catch (Throwable $ex) {
			self::assertStringContainsString('could not be converted to string', $ex->getMessage());

			return $ex;
		}

		self::fail('The sign-in did not fail');
	}

	/**
	 * Renders the blue screen as Core's logger writes it (Logging\Logger::buildException())
	 *
	 * @throws Throwable
	 */
	private function renderBlueScreen(Throwable $ex): string
	{
		self::assertTrue(Logging\SensitiveData::renderBlueScreen(new Tracy\BlueScreen(), $ex, $this->file));

		$content = file_get_contents($this->file);
		self::assertTrue(is_string($content));

		return $content;
	}

	/**
	 * The frames of the stack trace a Sentry event carries, with the arguments of each
	 */
	private function sentryFrames(Throwable $ex): string
	{
		$options = new Sentry\Options();

		$stacktrace = (new Sentry\StacktraceBuilder(
			$options,
			new Sentry\Serializer\RepresentationSerializer($options),
		))->buildFromException($ex);

		$frames = '';

		foreach ($stacktrace->getFrames() as $frame) {
			$frames .= $frame->getFunctionName() . var_export($frame->getVars(), true) . "\n";
		}

		return $frames;
	}

}
