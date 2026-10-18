<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\History;

use DivisionByZeroError;
use FastyBird\Module\Devices\Models\History\Aggregator;
use FastyBird\Module\Devices\Models\History\Bucket;
use PHPUnit\Framework\TestCase;
use function is_finite;

/**
 * The arithmetic of the property value history, without a database (#624)
 */
final class AggregatorTest extends TestCase
{

	public function testEachValueIsWeighedByHowLongItHeld(): void
	{
		// 10 for 100 seconds, 20 for 100, 30 for 100
		$buckets = Aggregator::samples(1_000, 1_300, 300, null, [[1_000, 10.0], [1_100, 20.0], [1_200, 30.0]]);

		self::assertEquals([new Bucket(1_000, 10.0, 30.0, 20.0, 30.0, 3)], $buckets);
	}

	public function testUnevenWeightsMoveTheAverage(): void
	{
		// 10 for 200 seconds and 40 for 100: (2000 + 4000) / 300
		$buckets = Aggregator::samples(0, 300, 300, null, [[0, 10.0], [200, 40.0]]);

		self::assertEquals([new Bucket(0, 10.0, 40.0, 20.0, 40.0, 2)], $buckets);
	}

	public function testTheValueThatHeldWhenTheBucketOpenedCountsFromItsFirstSecond(): void
	{
		// 10 carried in for 100 seconds, then 40 for 200
		$buckets = Aggregator::samples(300, 600, 300, [10.0], [[400, 40.0]]);

		self::assertEquals([new Bucket(300, 10.0, 40.0, 30.0, 40.0, 1)], $buckets);
	}

	public function testBucketWithoutASampleKeepsTheCarriedValue(): void
	{
		$buckets = Aggregator::samples(0, 900, 300, [7.0], [[10, 8.0]]);

		self::assertEquals(
			[
				new Bucket(0, 7.0, 8.0, (7.0 * 10 + 8.0 * 290) / 300, 8.0, 1),
				new Bucket(300, 8.0, 8.0, 8.0, 8.0, 0),
				new Bucket(600, 8.0, 8.0, 8.0, 8.0, 0),
			],
			$buckets,
		);
	}

	public function testGapWeighsNothingAndEndsTheBucketWithNoValue(): void
	{
		// 30 for 100 seconds, then a gap for 200
		$buckets = Aggregator::samples(0, 300, 300, null, [[0, 30.0], [100, null]]);

		self::assertEquals([new Bucket(0, 30.0, 30.0, 30.0, null, 2)], $buckets);
	}

	public function testBucketOfNothingButAGapHasNoValues(): void
	{
		$buckets = Aggregator::samples(0, 600, 300, [null], []);

		self::assertEquals(
			[new Bucket(0, null, null, null, null, 0), new Bucket(300, null, null, null, null, 0)],
			$buckets,
		);
	}

	public function testNothingIsKnownBeforeTheFirstSample(): void
	{
		// The first 150 seconds have no value and no weight, and the bucket before the first sample is not there
		$buckets = Aggregator::samples(0, 600, 300, null, [[450, 5.0]]);

		self::assertEquals([new Bucket(300, 5.0, 5.0, 5.0, 5.0, 1)], $buckets);
	}

	public function testPartOfABucketBeforeTheFirstSampleIsNotWeighed(): void
	{
		$buckets = Aggregator::samples(0, 300, 300, null, [[150, 5.0]]);

		self::assertEquals([new Bucket(0, 5.0, 5.0, 5.0, 5.0, 1)], $buckets);
	}

	public function testLastBucketIsCutAtTheEnd(): void
	{
		// The period ends 100 seconds into the second bucket
		$buckets = Aggregator::samples(0, 400, 300, [1.0], [[300, 3.0]]);

		self::assertEquals([new Bucket(0, 1.0, 1.0, 1.0, 1.0, 0), new Bucket(300, 3.0, 3.0, 3.0, 3.0, 1)], $buckets);
	}

	public function testNothingBuildsNothing(): void
	{
		self::assertSame([], Aggregator::samples(0, 600, 300, null, []));
		self::assertSame([], Aggregator::samples(600, 600, 300, [1.0], []));
	}

	/**
	 * @throws DivisionByZeroError
	 */
	public function testBucketsCombineByExtremesAndTheMeanOfTheirAverages(): void
	{
		$rows = [
			new Bucket(0, 1.0, 5.0, 2.0, 4.0, 3),
			new Bucket(300, 0.5, 9.0, 4.0, 8.0, 2),
			new Bucket(600, null, null, null, null, 1),
			new Bucket(3_600, 7.0, 7.0, 7.0, 7.0, 0),
		];

		self::assertEquals(
			[
				new Bucket(0, 0.5, 9.0, 3.0, null, 6),
				new Bucket(3_600, 7.0, 7.0, 7.0, 7.0, 0),
			],
			Aggregator::combine(0, 3_600, $rows),
		);
	}

	/**
	 * @throws DivisionByZeroError
	 */
	public function testCombinedBucketTakesTheLastValueOfItsLastRow(): void
	{
		$rows = [new Bucket(0, 1.0, 1.0, 1.0, 1.0, 1), new Bucket(300, 2.0, 2.0, 2.0, 2.0, 1)];

		self::assertEquals([new Bucket(0, 1.0, 2.0, 1.5, 2.0, 2)], Aggregator::combine(0, 3_600, $rows));
	}

	/**
	 * @throws DivisionByZeroError
	 */
	public function testRowBeforeTheStartBelongsToTheFirstBucket(): void
	{
		$rows = [new Bucket(250, 1.0, 1.0, 1.0, 1.0, 1), new Bucket(400, 3.0, 3.0, 3.0, 3.0, 1)];

		self::assertEquals([new Bucket(300, 1.0, 3.0, 2.0, 3.0, 2)], Aggregator::combine(300, 600, $rows));
	}

	/**
	 * @throws DivisionByZeroError
	 */
	public function testRowsFarBeforeTheStartAllBelongToTheFirstBucket(): void
	{
		$rows = [new Bucket(100, 1.0, 1.0, 1.0, 1.0, 1), new Bucket(400, 3.0, 3.0, 3.0, 3.0, 1)];

		self::assertEquals([new Bucket(1_000, 1.0, 3.0, 2.0, 3.0, 2)], Aggregator::combine(1_000, 600, $rows));
	}

	/**
	 * @throws DivisionByZeroError
	 */
	public function testBucketsAreCountedFromTheStart(): void
	{
		$rows = [new Bucket(1_000, 1.0, 1.0, 1.0, 1.0, 1), new Bucket(1_700, 2.0, 2.0, 2.0, 2.0, 1)];

		self::assertEquals(
			[new Bucket(1_000, 1.0, 1.0, 1.0, 1.0, 1), new Bucket(1_600, 2.0, 2.0, 2.0, 2.0, 1)],
			Aggregator::combine(1_000, 600, $rows),
		);
	}

	public function testAverageNearTheLargestDoubleStaysFinite(): void
	{
		// The plain sum of value times seconds is infinite here
		$held = Aggregator::samples(0, 300, 300, null, [[0, 1.7e308], [100, 1.7e308]]);

		self::assertCount(1, $held);
		self::assertSame(1.7e308, $held[0]->min);
		self::assertSame(1.7e308, $held[0]->max);
		self::assertNotNull($held[0]->avg);
		self::assertEqualsWithDelta(1.7e308, $held[0]->avg, 1.7e308 * 1e-12);

		$swing = Aggregator::samples(0, 300, 300, null, [[0, 1.7e308], [200, -1.7e308]]);

		self::assertCount(1, $swing);
		self::assertSame(-1.7e308, $swing[0]->min);
		self::assertSame(1.7e308, $swing[0]->max);
		self::assertNotNull($swing[0]->avg);
		self::assertTrue(is_finite($swing[0]->avg));
		self::assertEqualsWithDelta(1.7e308 / 3, $swing[0]->avg, 1.7e308 * 1e-12);
	}

	/**
	 * @throws DivisionByZeroError
	 */
	public function testMeanOfAveragesNearTheLargestDoubleStaysFinite(): void
	{
		$rows = [
			new Bucket(0, 1.7e308, 1.7e308, 1.7e308, 1.7e308, 1),
			new Bucket(300, 1.7e308, 1.7e308, 1.7e308, 1.7e308, 1),
			new Bucket(600, 1.7e308, 1.7e308, 1.7e308, 1.7e308, 1),
		];

		$combined = Aggregator::combine(0, 3_600, $rows);

		self::assertCount(1, $combined);
		self::assertNotNull($combined[0]->avg);
		self::assertEqualsWithDelta(1.7e308, $combined[0]->avg, 1.7e308 * 1e-12);
		self::assertSame(3, $combined[0]->samples);
	}

}
