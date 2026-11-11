// The uPlot options of the history chart, kept apart from the component so that a chart can be built
// without mounting anything.
import uPlot from 'uplot';
import type { Options, Series } from 'uplot';

import { axisWidth, formatValue, valueRange, withAlpha } from './chart';

export interface IChartColors {
	line: string;
	text: string;
	grid: string;
}

export interface IChartLabels {
	value: string;
	min: string;
	max: string;
}

export interface IChartOptionsInput {
	width: number;
	height: number;
	precision: number | null;
	unit: string | null;
	minimum: number | null;
	maximum: number | null;
	stepped: boolean;
	band: boolean;
	colors: IChartColors;
	labels: IChartLabels;
}

/**
 * Series index of the band edges: the line is series 1, then the lower and the upper edge
 */
const BAND_LOWER = 2;
const BAND_UPPER = 3;

/**
 * @param {IChartOptionsInput} input
 */
export const buildChartOptions = (input: IChartOptionsInput): Options => {
	const { colors } = input;

	const format = (value: number | null | undefined): string => formatValue(value, input.precision, input.unit);

	// A step holds the value until the next point, which is how a state that changes between recorded points reads
	const paths = (): Series['paths'] => (input.stepped ? uPlot.paths.stepped?.({ align: 1 }) : undefined);

	const series: Series[] = [
		{},
		{
			label: input.labels.value,
			stroke: colors.line,
			width: 2,
			spanGaps: false,
			points: { show: false },
			paths: paths(),
			value: (_chart: uPlot, value: number | null): string => format(value),
		},
	];

	if (input.band) {
		for (const label of [input.labels.min, input.labels.max]) {
			series.push({
				label,
				stroke: withAlpha(colors.line, 0.4),
				width: 1,
				spanGaps: false,
				points: { show: false },
				paths: paths(),
				value: (_chart: uPlot, value: number | null): string => format(value),
			});
		}
	}

	return {
		width: input.width,
		height: input.height,
		series,
		bands: input.band ? [{ series: [BAND_UPPER, BAND_LOWER], fill: withAlpha(colors.line, 0.15) }] : [],
		scales: {
			x: { time: true },
			y: {
				range: (_chart: uPlot, min: number, max: number): [number, number] => valueRange(min, max, input.minimum, input.maximum),
			},
		},
		axes: [
			{ stroke: colors.text, grid: { stroke: colors.grid, width: 1 }, ticks: { stroke: colors.grid, width: 1 } },
			{
				stroke: colors.text,
				grid: { stroke: colors.grid, width: 1 },
				ticks: { stroke: colors.grid, width: 1 },
				values: (_chart: uPlot, splits: number[]): string[] => splits.map((value: number): string => formatValue(value, input.precision)),
				size: (_chart: uPlot, values: string[] | undefined): number => axisWidth(values ?? []),
			},
		],
		cursor: { drag: { x: false, y: false } },
	};
};
