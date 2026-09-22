<?php declare(strict_types = 1);

namespace FastyBird\Core\Helpers\DoctrineCrud;

use FastyBird\Core\Exceptions;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use function array_key_exists;
use function array_slice;
use function class_exists;
use function interface_exists;
use function is_object;
use function is_subclass_of;
use function method_exists;
use function sprintf;
use function strtolower;

/**
 * Doctrine CRUD helpers
 */
class Helpers
{

	/**
	 * This method was inspired by same method in Nette framework
	 *
	 * @param array<mixed> $arguments
	 *
	 * @return array<mixed>
	 *
	 * @throws Exceptions\EntityCreation
	 *
	 * @throws ReflectionException
	 */
	public static function autowireArguments(ReflectionMethod $method, array $arguments): array
	{
		$optCount = 0;
		$num = -1;
		$res = [];
		$methodName = $method->getDeclaringClass()->getName() . '::' . $method->getName() . '()';

		foreach ($method->getParameters() as $subNum => $parameter) {
			if (!$parameter->isVariadic() && array_key_exists($parameter->getName(), $arguments)) {
				$res[$subNum] = $arguments[$parameter->getName()];
				unset($arguments[$parameter->getName()], $arguments[$subNum]);
				$optCount = 0;

			} elseif (array_key_exists($subNum, $arguments)) {
				$res[$subNum] = $arguments[$subNum];
				unset($arguments[$subNum]);
				$optCount = 0;

			} else {
				$class = self::getParameterType($parameter);

				if (
					(
						$class !== null
						&& $parameter->allowsNull()
					)
					|| $parameter->isOptional()
					|| $parameter->isDefaultValueAvailable()
				) {
					// !optional + defaultAvailable = func($a = null, $b) since 5.4.7
					// optional + !defaultAvailable = i.e. Exception::__construct, mysqli::mysqli, ...
					$res[$subNum] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
					$optCount++;

				} else {
					if ($class !== null && (class_exists($class) || interface_exists($class))) {
						foreach ($arguments as $key => $argument) {
							if (
								is_object($argument)
								&& (
									is_subclass_of($argument, $class) || $argument::class === $class
								)
							) {
								$res[$subNum] = $argument;
								unset($arguments[$key]);
								$optCount = 0;

								continue 2;
							}
						}
					}

					throw new Exceptions\EntityCreation(
						$parameter->getName(),
						sprintf(
							'Parameter %s in %s has no class type hint or default value, so its value must be specified.',
							$methodName,
							$parameter->getName(),
						),
					);
				}
			}
		}

		// extra parameters
		while (array_key_exists(++$num, $arguments)) {
			$res[$num] = $arguments[$num];
			unset($arguments[$num]);
			$optCount = 0;
		}

		return $optCount > 0 ? array_slice($res, 0, -$optCount) : $res;
	}

	public static function getParameterType(ReflectionParameter $param): string|null
	{
		if ($param->hasType()) {
			$rt = $param->getType();

			$type = $rt !== null && method_exists($rt, 'getName') ? $rt->getName() : null;

			$rc = $param->getDeclaringClass();

			if ($rc === null) {
				return null;
			}

			return $type !== null && strtolower($type) === 'self' ? $rc->getName() : $type;
		}

		return null;
	}

}
