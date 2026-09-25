<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets\Controller;

use FastyBird\Core\Controllers\WebSockets as Application;
use FastyBird\Core\Controllers\WebSockets\Responses;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as WebSocketsExceptions;
use FastyBird\Core\Http\Routing as HttpRouting;
use FastyBird\Core\Routing as Router;
use Fig\Http;
use Nette;
use Nette\Security as NS;
use Override;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Reflector;
use stdClass;
use TypeError;
use function array_key_exists;
use function assert;
use function call_user_func;
use function get_class;
use function gettype;
use function in_array;
use function is_object;
use function is_string;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_split;
use function sprintf;
use function strtolower;
use function substr;
use const PREG_SPLIT_NO_EMPTY;

/**
 * WebSockets application controller interface
 */
abstract class Controller implements IController
{

	/**
	 * Special parameter keys
	 *
	 * @internal
	 */
	public const string ACTION_KEY = 'action';

	public const string SIGNAL_KEY = 'signal';

	public const string DEFAULT_ACTION = 'default';

	private Application\Request $request;

	private Responses\IResponse $response;

	private stdClass $payload;

	private bool $startupCheck = false;

	private array $globalParams = [];

	private array $params = [];

	private string $action;

	private string|null $signal = null;

	private string $name;

	private Nette\DI\Container|null $context = null;

	private IControllerFactory|null $controllerFactory = null;

	private Router\IWampRouter|null $router = null;

	private HttpRouting\LinkGenerator|null $linkGenerator = null;

	private NS\User|null $user = null;

	public function __construct()
	{
		$this->payload = new stdClass();
	}

	/**
	 * @throws Nette\InvalidStateException
	 */
	public function injectPrimary(
		Nette\DI\Container|null $context = null,
		IControllerFactory|null $controllerFactory = null,
		Router\IWampRouter|null $router = null,
		HttpRouting\LinkGenerator|null $linkGenerator = null,
		NS\User|null $user = null,
	): void
	{
		// $controllerFactory is a typed property with no default; isset() is the only read that
		// does not throw before the first injectPrimary() call ever assigns it.
		if (isset($this->controllerFactory)) {
			throw new Nette\InvalidStateException(
				sprintf(
					'Method "%s" is intended for initialization and should not be called more than once.',
					__METHOD__,
				),
			);
		}

		$this->context = $context;
		$this->controllerFactory = $controllerFactory;
		$this->router = $router;
		$this->linkGenerator = $linkGenerator;
		$this->user = $user;
	}

	/**
	 * @throws WebSocketsExceptions\BadRequest
	 * @throws WebSocketsExceptions\BadSignal
	 * @throws WebSocketsExceptions\ForbiddenRequest
	 * @throws Exceptions\InvalidState
	 * @throws ReflectionException
	 * @throws TypeError
	 */
	#[Override]
	public function run(Application\Request $request): Responses\IResponse
	{
		try {
			// STARTUP
			$this->request = $request;
			$this->payload ??= new stdClass();
			$this->name = $request->getControllerName();

			$this->initGlobalParameters();

			$this->checkRequirements(new ReflectionClass($this));

			$this->startup();

			if (!$this->startupCheck) {
				$class = (new ReflectionClass($this))->getMethod('startup')->getDeclaringClass()->getName();

				throw new Exceptions\InvalidState(
					sprintf('Method %s::startup() or its descendant doesn\'t call parent::startup().', $class),
				);
			}

			if ($this->signal !== null) {
				if (!$this->tryCall(call_user_func([$this, 'formatSignalMethod'], $this->signal), $this->params)) {
					$class = static::class;

					throw new WebSocketsExceptions\BadSignal(
						sprintf('There is no handler for signal "%s" in class "%s".', $this->signal, $class),
					);
				}
			}

			// calls $this->action<Action>()
			$this->tryCall(call_user_func([$this, 'formatActionMethod'], $this->action), $this->params);

			$this->sendPayload();

		} catch (WebSocketsExceptions\Abort) {
			// SHUTDOWN
			$this->shutdown($this->response);
		}

		return $this->response;
	}

	#[Override]
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Checks authorization
	 *
	 * @throws WebSocketsExceptions\ForbiddenRequest
	 * @throws Exceptions\InvalidState
	 */
	public function checkRequirements(mixed $element): void
	{
		$user = (array) $this->parseAnnotation($element, 'User');

		if (in_array('loggedIn', $user, true) && !$this->getUser()->isLoggedIn()) {
			throw new WebSocketsExceptions\ForbiddenRequest();
		}
	}

	public function getPayload(): stdClass
	{
		return $this->payload;
	}

	/**
	 * Sends payload to the output
	 *
	 * @throws WebSocketsExceptions\Abort
	 */
	public function sendPayload(): void
	{
		if (isset($this->payload->data)) {
			$this->sendResponse(new Responses\MessageResponse($this->payload->data));
		}

		$this->sendResponse(new Responses\NullResponse());
	}

	/**
	 * Sends response and terminates presenter
	 *
	 * @throws WebSocketsExceptions\Abort
	 */
	public function sendResponse(Responses\IResponse $response): void
	{
		$this->response = $response;

		$this->terminate();
	}

	/**
	 * Correctly terminates controller
	 *
	 * @throws WebSocketsExceptions\Abort
	 */
	public function terminate(): void
	{
		throw new WebSocketsExceptions\Abort();
	}

	/**
	 * @throws WebSocketsExceptions\InvalidLink
	 * @throws ReflectionException
	 */
	public function link(string $destination, array $args = []): string
	{
		assert($this->linkGenerator !== null);

		return $this->linkGenerator->link($destination, $args);
	}

	/**
	 * Changes current action. Only alphanumeric characters are allowed
	 *
	 * @throws WebSocketsExceptions\BadRequest
	 */
	private function changeAction(string $action): void
	{
		if (is_string($action) && Nette\Utils\Strings::match($action, '#^[a-zA-Z0-9][a-zA-Z0-9_\x7f-\xff]*\z#')) {
			$this->action = $action;

		} else {
			throw new WebSocketsExceptions\BadRequest(
				'Action name is not alphanumeric string.',
				Http\Message\StatusCodeInterface::STATUS_NOT_FOUND,
			);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getUser(): Nette\Security\User
	{
		if ($this->user === null) {
			throw new Exceptions\InvalidState('Service User has not been set.');
		}

		return $this->user;
	}

	/**
	 * Formats action method name
	 */
	public static function formatActionMethod(string $action): string
	{
		return 'action' . $action;
	}

	/**
	 * Formats signal handler method name -> case sensitivity doesn't matter
	 */
	public static function formatSignalMethod(string $signal): string|null
	{
		return $signal === null ? null : 'handle' . $signal; // intentionally ==
	}

	/**
	 * Converts list of arguments to named parameters
	 *
	 * @param string $class       class name
	 * @param string $method      method name
	 * @param array $supplemental supplemental arguments
	 * @param array $missing      missing arguments
	 *
	 * @throws WebSocketsExceptions\InvalidLink
	 * @throws ReflectionException
	 *
	 * @internal
	 */
	public static function argsToParams(
		string $class,
		string $method,
		array &$args,
		array $supplemental = [],
		array &$missing = [],
	): void
	{
		$i = 0;
		$rm = new ReflectionMethod($class, $method);

		foreach ($rm->getParameters() as $param) {
			[$type, $isClass] = Application\Reflection::getParameterType($param);
			$name = $param->getName();

			if (array_key_exists($i, $args)) {
				$args[$name] = $args[$i];
				unset($args[$i]);
				$i++;

			} elseif (array_key_exists($name, $args)) { // phpcs:ignore
				// continue with process

			} elseif (array_key_exists($name, $supplemental)) {
				$args[$name] = $supplemental[$name];
			}

			if (!isset($args[$name])) {
				if (
					!$param->isDefaultValueAvailable()
					&& !$param->allowsNull()
					&& $type !== 'null'
					&& $type !== 'array'
				) {
					$missing[] = $param;
					unset($args[$name]);
				}

				continue;
			}

			if (!Application\Reflection::convertType($args[$name], $type, $isClass)) {
				throw new WebSocketsExceptions\InvalidLink(sprintf(
					'Argument $%s passed to %s() must be %s, %s given.',
					$name,
					$rm->getDeclaringClass()->getName() . '::' . $rm->getName(),
					$type === 'null' ? 'scalar' : $type,
					is_object($args[$name]) ? get_class($args[$name]) : gettype($args[$name]),
				));
			}

			$def = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
			if ($args[$name] === $def || ($def === null && $args[$name] === '')) {
				$args[$name] = null; // value transmit is unnecessary
			}
		}

		if (array_key_exists($i, $args)) {
			throw new WebSocketsExceptions\InvalidLink(
				sprintf('Passed more parameters than method %s::%s() expects.', $class, $rm->getName()),
			);
		}
	}

	protected function startup(): void
	{
		$this->startupCheck = true;
	}

	protected function shutdown(Responses\IResponse $response): void
	{
		// Template method: subclasses override this hook, no default behaviour
	}

	/**
	 * Call method of object
	 *
	 * @throws WebSocketsExceptions\BadRequest
	 * @throws WebSocketsExceptions\ForbiddenRequest
	 * @throws Exceptions\InvalidState
	 * @throws ReflectionException
	 */
	protected function tryCall(string $method, array $params): bool
	{
		$rc = new ReflectionClass($this);

		if ($rc->hasMethod($method)) {
			$rm = $rc->getMethod($method);

			if ($rm->isPublic() && !$rm->isAbstract() && !$rm->isStatic()) {
				$this->checkRequirements($rm);
				$rm->invokeArgs($this, Application\Reflection::combineArgs($rm, $params));

				return true;
			}
		}

		return false;
	}

	/**
	 * Initializes $this->globalParams, $this->action. Called by run()
	 *
	 * @throws WebSocketsExceptions\BadRequest
	 * @throws TypeError
	 */
	private function initGlobalParameters(): void
	{
		// init $this->globalParams
		$this->globalParams = [];

		$selfParams = [];

		$params = $this->request->getParameters();

		foreach ($params as $key => $value) {
			if (!preg_match('#^((?:[a-z0-9_]+-)*)((?!\d+\z)[a-z0-9_]+)\z#i', $key, $matches)) {
				continue;
			} elseif (!$matches[1]) {
				$selfParams[$key] = $value;

			} else {
				$this->globalParams[substr($matches[1], 0, -1)][$matches[2]] = $value;
			}
		}

		$this->params = $selfParams;

		// init & validate $this->action & $this->view
		$this->changeAction($selfParams[self::ACTION_KEY] ?? self::DEFAULT_ACTION);

		if (isset($selfParams[self::SIGNAL_KEY])) {
			$this->signal = $selfParams[self::SIGNAL_KEY];
		}
	}

	/**
	 * Returns an annotation value
	 */
	private function parseAnnotation(Reflector $ref, string $name): array|bool
	{
		if (
			!$ref->getDocComment()
			|| !preg_match_all(
				'#[\\s*]@' . preg_quote($name, '#') . '(?:\(\\s*([^)]*)\\s*\)|\\s|$)#',
				$ref->getDocComment(),
				$m,
			)
		) {
			return false;
		}

		static $tokens = ['true' => true, 'false' => false, 'null' => null];

		$res = [];

		foreach ($m[1] as $s) {
			$parts = preg_split('#\s*,\s*#', $s, -1, PREG_SPLIT_NO_EMPTY);

			foreach ($parts !== [] ? $parts : ['true'] as $item) {
				$res[] = array_key_exists($tmp = strtolower($item), $tokens) ? $tokens[$tmp] : $item;
			}
		}

		return $res;
	}

}
