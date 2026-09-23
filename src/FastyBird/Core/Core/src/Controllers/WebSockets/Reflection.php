<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets;

use FastyBird\Core\Exceptions;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use function boolval;
use function floatval;
use function get_class;
use function gettype;
use function intval;
use function is_array;
use function is_object;
use function is_scalar;
use function preg_replace;
use function sprintf;
use function strval;

/**
 * Helpers for Controllers
 *
 * @internal
 */
final class Reflection
{

	/**
	 * @param array<mixed> $args
	 *
	 * @throws Exceptions\BadRequest
	 * @throws ReflectionException
	 */
	public static function combineArgs(ReflectionFunctionAbstract $method, array $args): array
	{
		$res = [];

		foreach ($method->getParameters() as $i => $param) {
			$name = $param->getName();

			[$type, $isClass] = self::getParameterType($param);

			if (isset($args[$name])) {
				$res[$i] = $args[$name];

				if (!self::convertType($res[$i], $type, $isClass)) {
					throw new Exceptions\BadRequest(sprintf(
						'Argument $%s passed to %s() must be %s, %s given.',
						$name,
						($method instanceof ReflectionMethod ? $method->getDeclaringClass()->getName() . '::' : '') . $method->getName(),
						$type === 'null' ? 'scalar' : $type,
						is_object($args[$name]) ? get_class($args[$name]) : gettype($args[$name]),
					));
				}
			} elseif ($param->isDefaultValueAvailable()) {
				$res[$i] = $param->getDefaultValue();

			} elseif ($type === 'null' || $param->allowsNull()) {
				$res[$i] = null;

			} elseif ($type === 'array') {
				$res[$i] = [];

			} else {
				throw new Exceptions\BadRequest(sprintf(
					'Missing parameter $%s required by %s()',
					$name,
					($method instanceof ReflectionMethod ? $method->getDeclaringClass()->getName() . '::' : '') . $method->getName(),
				));
			}
		}

		return $res;
	}

	/**
	 * Non data-loss type conversion.
	 */
	public static function convertType(mixed &$val, string $type, bool $isClass = false): bool
	{
		if ($isClass) {
			return $val instanceof $type;
		} elseif ($type === 'callable') {
			return false;
		} elseif ($type === 'null') { // means 'not array'
			return !is_array($val);
		} elseif ($type === 'array') {
			return is_array($val);
		} elseif (!is_scalar($val)) { // array, resource, null, etc.
			return false;
		} else {
			$tmp = ($val === false ? '0' : (string) $val);

			if ($type === 'double' || $type === 'float') {
				$tmp = preg_replace('#\.0*\z#', '', $tmp);
			}

			$orig = $tmp;

			if ($type === 'double' || $type === 'float') {
				$tmp = floatval($tmp);

			} elseif ($type === 'boolean') {
				$tmp = boolval($tmp);

			} elseif ($type === 'string') {
				$tmp = strval($tmp);

			} elseif ($type === 'int') {
				$tmp = intval($tmp);
			}

			if ($orig !== ($tmp === false ? '0' : (string) $tmp)) {
				return false; // data-loss occurs
			}

			$val = $tmp;
		}

		return true;
	}

	/**
	 * @return array [string|null, bool]
	 *
	 * @throws ReflectionException
	 */
	public static function getParameterType(ReflectionParameter $param): array
	{
		$def = gettype($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);

		return $param->hasType() ? [$param->getType()->getName(), !$param->getType()->isBuiltin()] : [$def, false];
	}

}
