// Plain TypeScript with no Vue or uPlot import, for the same reason as ./history.ts: the range,
// formatting and sizing rules can be run on their own.
import type { IHistorySeries } from './history';

export const CHART_DEFAULT_HEIGHT = 240;

// Share of the data span that is left free above and below an automatic range
const RANGE_PADDING = 0.05;

export type ChartColumn = (number | null)[];

/**
 * Columns in the layout uPlot draws: time, the line, and when the band is on, its lower and upper edge
 */
export type ChartData = [number[], ...ChartColumn[]];

export interface IChartDataOptions {
	// A stepped line shows the value that held until the next change, which is what the last value of a bucket is
	stepped: boolean;
	band: boolean;
}

/**
 * @param {IHistorySeries} series
 * @param {IChartDataOptions} options
 */
export const buildChartData = (series: IHistorySeries, options: IChartDataOptions): ChartData => {
	const { time, min, max, avg, last } = series.columns;

	const data: ChartData = [time, options.stepped ? last : avg];

	if (options.band) {
		data.push(min, max);
	}

	return data;
};

/**
 * The visible range of the value axis
 *
 * A configured bound is used as it is. A side without one follows the data, with some room, and a flat line
 * gets a range of its own instead of a zero-height one.
 *
 * @param {number | null} dataMin
 * @param {number | null} dataMax
 * @param {number | null} configuredMin
 * @param {number | null} configuredMax
 */
export const valueRange = (
	dataMin: number | null | undefined,
	dataMax: number | null | undefined,
	configuredMin: number | null | undefined,
	configuredMax: number | null | undefined
): [number, number] => {
	const hasData = typeof dataMin === 'number' && typeof dataMax === 'number';

	const padding = hasData ? (dataMax - dataMin) * RANGE_PADDING : 0;

	let lower = typeof configuredMin === 'number' ? configuredMin : hasData ? dataMin - padding : 0;
	let upper = typeof configuredMax === 'number' ? configuredMax : hasData ? dataMax + padding : 1;

	if (lower >= upper) {
		if (typeof configuredMin === 'number') {
			// Also the contradicting bounds: the minimum wins, with a unit of room instead of an empty axis
			upper = lower + 1;
		} else if (typeof configuredMax === 'number') {
			lower = upper - 1;
		} else {
			lower -= 1;
			upper += 1;
		}
	}

	return [lower, upper];
};

/**
 * @param {number | null | undefined} value
 * @param {number | null | undefined} precision decimal places, or null to leave the number as it is
 * @param {string | null} unit
 */
export const formatValue = (value: number | null | undefined, precision: number | null | undefined, unit: string | null = null): string => {
	if (typeof value !== 'number' || !Number.isFinite(value)) {
		return '--';
	}

	const text =
		typeof precision === 'number' && Number.isInteger(precision) && precision >= 0
			? value.toFixed(Math.min(precision, 20))
			: String(Math.round(value * 10000) / 10000);

	return unit !== null && unit !== '' ? `${text} ${unit}` : text;
};

/**
 * Width of the value axis for its labels, so a long number is not cut off
 *
 * @param {string[]} labels
 * @param {number} fontSize
 */
export const axisWidth = (labels: string[], fontSize: number = 12): number => {
	const longest = labels.reduce((length, label) => Math.max(length, label.length), 0);

	return Math.ceil(longest * fontSize * 0.62) + 24;
};

/**
 * Colour with an alpha, for the band between the minimum and the maximum
 *
 * @param {string} color
 * @param {number} alpha
 */
export const withAlpha = (color: string, alpha: number): string => {
	const value = color.trim();

	const hex = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(value);

	if (hex !== null) {
		const digits = hex[1].length === 3 ? [...hex[1]].map((digit) => digit + digit).join('') : hex[1];

		return `rgba(${parseInt(digits.slice(0, 2), 16)}, ${parseInt(digits.slice(2, 4), 16)}, ${parseInt(digits.slice(4, 6), 16)}, ${alpha})`;
	}

	const rgb = /^rgba?\(\s*(\d{1,3})\s*[, ]\s*(\d{1,3})\s*[, ]\s*(\d{1,3})\s*(?:[,/]\s*[\d.]+%?\s*)?\)$/i.exec(value);

	if (rgb !== null) {
		return `rgba(${rgb[1]}, ${rgb[2]}, ${rgb[3]}, ${alpha})`;
	}

	return `color-mix(in srgb, ${value} ${Math.round(alpha * 100)}%, transparent)`;
};

/**
 * Size to give the chart for the width its container has now, or null while the container has none (hidden)
 *
 * Only the width follows the container: the chart sets its own height, and following that would feed back.
 *
 * @param {number} width
 * @param {number} height
 */
export const fitSize = (width: number, height: number = CHART_DEFAULT_HEIGHT): { width: number; height: number } | null => {
	if (!Number.isFinite(width) || width < 1) {
		return null;
	}

	return { width: Math.floor(width), height: Number.isFinite(height) && height >= 1 ? Math.floor(height) : CHART_DEFAULT_HEIGHT };
};
