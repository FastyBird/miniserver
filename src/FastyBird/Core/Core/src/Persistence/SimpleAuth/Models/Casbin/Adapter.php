<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\SimpleAuth\Models\Casbin;

use Casbin\Model as CasbinModel;
use Casbin\Persist as CasbinPersist;
use Closure;
use Doctrine\DBAL;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Types\SimpleAuth as Types;
use Ramsey\Uuid;
use Throwable;
use TypeError;
use ValueError;
use function array_filter;
use function array_values;
use function implode;
use function intval;
use function is_string;
use function strval;
use function trim;

/**
 * Casbin database adapter
 */
class Adapter implements CasbinPersist\FilteredAdapter, CasbinPersist\BatchAdapter, CasbinPersist\UpdatableAdapter
{

	use CasbinPersist\AdapterHelper;

	private string $policyTableName = 'fb_security_policies';

	private bool $filtered = false;

	/** @var array<string> */
	private array $columns = ['ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5'];

	public function __construct(private readonly DBAL\Connection $connection)
	{
	}

	/**
	 * @param array<int, string|null> $rule
	 *
	 * @throws DBAL\Exception
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function savePolicyLine(string $pType, array $rule): DBAL\Result|int|string
	{
		$queryBuilder = $this->connection->createQueryBuilder();
		$queryBuilder
			->insert($this->policyTableName)
			->values([
				'policy_id' => '?',
				'ptype' => '?',
				'policy_type' => '?',
			])
			->setParameter(0, Uuid\Uuid::uuid4(), Uuid\Doctrine\UuidBinaryType::NAME)
			->setParameter(1, Types\PolicyType::from($pType)->value)
			->setParameter(2, 'policy');

		foreach ($rule as $key => $value) {
			$queryBuilder
				->setValue('v' . strval($key), '?')
				->setParameter(intval($key) + 3, $value);
		}

		return $queryBuilder->executeQuery();
	}

	/**
	 * @throws DBAL\Exception
	 */
	public function loadPolicy(CasbinModel\Model $model): void
	{
		$queryBuilder = $this->connection->createQueryBuilder();

		$stmt = $queryBuilder
			->select('ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5')
			->from($this->policyTableName)
			->executeQuery();

		while ($row = $stmt->fetchAssociative()) {
			/** @var array<string, string|null> $row */
			$this->loadPolicyArray($this->filterRule($row), $model);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 */
	public function loadFilteredPolicy(CasbinModel\Model $model, $filter): void
	{
		$queryBuilder = $this->connection->createQueryBuilder();
		$queryBuilder->select('ptype', 'v0', 'v1', 'v2', 'v3', 'v4', 'v5');

		if (is_string($filter) || $filter instanceof DBAL\Query\Expression\CompositeExpression) {
			$queryBuilder->where($filter);
		} elseif ($filter instanceof Filter) {
			$queryBuilder->where($filter->getPredicates());
			foreach ($filter->getParams() as $key => $value) {
				$queryBuilder->setParameter($key, $value);
			}
		} elseif ($filter instanceof Closure) {
			$filter($queryBuilder);
		} else {
			throw new Exceptions\InvalidState('Invalid filter type');
		}

		$stmt = $queryBuilder->from($this->policyTableName)->executeQuery();

		while ($row = $stmt->fetchAssociative()) {
			/** @var array<string, string|null> $row */
			$line = implode(', ', array_filter($row, static fn ($val) => $val != '' && $val !== null));
			$this->loadPolicyLine(trim($line), $model);
		}

		$this->setFiltered(true);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function savePolicy(CasbinModel\Model $model): void
	{
		foreach ($model['p'] ?? [] as $pType => $ast) {
			foreach ($ast->policy as $rule) {
				$this->savePolicyLine($pType, $rule);
			}
		}

		foreach ($model['g'] ?? [] as $pType => $ast) {
			foreach ($ast->policy as $rule) {
				$this->savePolicyLine($pType, $rule);
			}
		}
	}

	/**
	 * @param array<int, string|null> $rule
	 *
	 * @throws DBAL\Exception
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function addPolicy(string $sec, string $pType, array $rule): void
	{
		$this->savePolicyLine($pType, $rule);
	}

	/**
	 * @param array<int, array<int, string|null>> $rules
	 *
	 * @throws DBAL\Exception
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function addPolicies(string $sec, string $pType, array $rules): void
	{
		$this->connection->transactional(function () use ($pType, $rules): void {
			foreach ($rules as $rule) {
				$this->savePolicyLine($pType, $rule);
			}
		});
	}

	/**
	 * @param array<int, string|null> $rule
	 *
	 * @throws DBAL\Exception
	 */
	public function removePolicy(string $sec, string $pType, array $rule): void
	{
		$this->removePolicyLine($pType, $rule);
	}

	/**
	 * @param array<int, array<int, string|null>> $rules
	 *
	 * @throws Throwable
	 */
	public function removePolicies(string $sec, string $pType, array $rules): void
	{
		$this->connection->transactional(function () use ($pType, $rules): void {
			foreach ($rules as $rule) {
				$this->removePolicyLine($pType, $rule);
			}
		});
	}

	/**
	 * @throws Throwable
	 */
	public function removeFilteredPolicy(string $sec, string $pType, int $fieldIndex, string ...$fieldValues): void
	{
		$this->removeFiltered($pType, $fieldIndex, ...$fieldValues);
	}

	/**
	 * @param array<int, string|null> $oldRule
	 * @param array<int, string|null> $newPolicy
	 *
	 * @throws DBAL\Exception
	 */
	public function updatePolicy(string $sec, string $pType, array $oldRule, array $newPolicy): void
	{
		$queryBuilder = $this->connection->createQueryBuilder();
		$queryBuilder->where('ptype = :ptype')->setParameter('ptype', $pType);

		foreach ($oldRule as $key => $value) {
			$placeholder = 'w' . strval($key);
			$queryBuilder->andWhere('v' . strval($key) . ' = :' . $placeholder)->setParameter(
				$placeholder,
				$value,
			);
		}

		foreach ($newPolicy as $key => $value) {
			$placeholder = 's' . strval($key);
			$queryBuilder->set('v' . strval($key), ':' . $placeholder)->setParameter($placeholder, $value);
		}

		$queryBuilder->update($this->policyTableName);

		$queryBuilder->executeQuery();
	}

	/**
	 * @param array<int, array<int, string|null>> $oldRules
	 * @param array<int, array<int, string|null>> $newRules
	 *
	 * @throws DBAL\Exception
	 */
	public function updatePolicies(string $sec, string $pType, array $oldRules, array $newRules): void
	{
		$this->connection->transactional(function () use ($sec, $pType, $oldRules, $newRules): void {
			foreach ($oldRules as $i => $oldRule) {
				$this->updatePolicy($sec, $pType, $oldRule, $newRules[$i]);
			}
		});
	}

	/**
	 * @param array<int, array<int, string|null>> $newRules
	 *
	 * @return array<int, array<int, string|null>>
	 *
	 * @throws Throwable
	 */
	public function updateFilteredPolicies(
		string $sec,
		string $pType,
		array $newRules,
		int $fieldIndex,
		string|null ...$fieldValues,
	): array
	{
		$oldRules = [];

		$this->connection->transactional(
			function () use ($sec, $pType, $newRules, $fieldIndex, $fieldValues, &$oldRules): void {
				$oldRules = $this->removeFiltered($pType, $fieldIndex, ...$fieldValues);

				$this->addPolicies($sec, $pType, $newRules);
			},
		);

		return $oldRules;
	}

	/**
	 * @param array<string, string|null> $rule
	 *
	 * @return array<int, string>
	 */
	public function filterRule(array $rule): array
	{
		$rule = array_values($rule);

		return array_filter($rule, static fn ($value): bool => $value !== null && $value !== '');
	}

	public function isFiltered(): bool
	{
		return $this->filtered;
	}

	public function setFiltered(bool $filtered): void
	{
		$this->filtered = $filtered;
	}

	/**
	 * @param array<int, string|null> $rule
	 *
	 * @throws DBAL\Exception
	 */
	private function removePolicyLine(string $pType, array $rule): void
	{
		$queryBuilder = $this->connection->createQueryBuilder();
		$queryBuilder->where('ptype = ?')->setParameter(0, $pType);

		foreach ($rule as $key => $value) {
			$queryBuilder->andWhere('v' . strval($key) . ' = ?')->setParameter($key + 1, $value);
		}

		$queryBuilder->delete($this->policyTableName)->executeQuery();
	}

	/**
	 * @return array<int, array<int, string|null>>
	 *
	 * @throws Throwable
	 */
	public function removeFiltered(string $pType, int $fieldIndex, string|null ...$fieldValues): array
	{
		$removedRules = [];

		$this->connection->transactional(function () use ($pType, $fieldIndex, $fieldValues, &$removedRules): void {
			$queryBuilder = $this->connection->createQueryBuilder();
			$queryBuilder->where('ptype = :ptype')->setParameter('ptype', $pType);

			foreach ($fieldValues as $value) {
				if ($value !== null && $value !== '') {
					$key = 'v' . strval($fieldIndex);

					$queryBuilder
						->andWhere($key . ' = :' . $key)
						->setParameter($key, $value);
				}

				$fieldIndex++;
			}

			$stmt = $queryBuilder->select(...$this->columns)->from($this->policyTableName)->executeQuery();

			while ($row = $stmt->fetchAssociative()) {
				/** @var array<string, string|null> $row */
				$removedRules[] = $this->filterRule($row);
			}

			$queryBuilder->delete($this->policyTableName)->executeQuery();
		});

		return $removedRules;
	}

}
