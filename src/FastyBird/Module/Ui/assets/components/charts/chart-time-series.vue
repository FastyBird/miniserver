<template>
	<div
		ref="container"
		:aria-label="t('uiModule.chart.label')"
		class="chart-time-series"
		role="img"
	/>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import uPlot from 'uplot';
import type { AlignedData, Options } from 'uplot';
import 'uplot/dist/uPlot.min.css';

import { CHART_DEFAULT_HEIGHT, fitSize } from '../../utilities/chart';
import { buildChartOptions } from '../../utilities/chart-options';

import { IChartTimeSeriesProps } from './chart-time-series.types';

defineOptions({
	name: 'ChartTimeSeries',
});

const props = withDefaults(defineProps<IChartTimeSeriesProps>(), {
	precision: null,
	unit: null,
	minimum: null,
	maximum: null,
	stepped: false,
	band: false,
	height: CHART_DEFAULT_HEIGHT,
});

const { t } = useI18n();

const container = ref<HTMLElement | null>(null);

// Raised when the page switches between a light and a dark theme, which changes the CSS variables the colours come from
const themeVersion = ref<number>(0);

let chart: uPlot | null = null;
let chartKey: string | null = null;

let resizeObserver: ResizeObserver | null = null;
let themeObserver: MutationObserver | null = null;

const cssVariable = (element: HTMLElement, name: string, fallback: string): string => {
	return getComputedStyle(element).getPropertyValue(name).trim() || fallback;
};

const buildOptions = (element: HTMLElement, width: number, height: number): Options => {
	return buildChartOptions({
		width,
		height,
		precision: props.precision,
		unit: props.unit,
		minimum: props.minimum,
		maximum: props.maximum,
		stepped: props.stepped,
		band: props.band,
		colors: {
			line: cssVariable(element, '--el-color-primary', '#409eff'),
			text: cssVariable(element, '--el-text-color-regular', '#606266'),
			grid: cssVariable(element, '--el-border-color-lighter', '#ebeef5'),
		},
		labels: {
			value: t('uiModule.chart.series.value'),
			min: t('uiModule.chart.series.min'),
			max: t('uiModule.chart.series.max'),
		},
	});
};

const destroy = (): void => {
	chart?.destroy();

	chart = null;
	chartKey = null;
};

// Everything that changes how the chart is built. New data alone is handed to the chart that exists.
const optionsKey = (): string => {
	return JSON.stringify([props.precision, props.unit, props.minimum, props.maximum, props.stepped, props.band, props.height, themeVersion.value]);
};

const render = (): void => {
	const element = container.value;

	if (element === null) {
		return;
	}

	const size = fitSize(element.clientWidth, props.height);

	// Hidden or not laid out yet: the resize observer renders once it has a width
	if (size === null) {
		return;
	}

	const key = optionsKey();

	if (chart !== null && chartKey === key) {
		chart.setData(props.data as AlignedData);

		return;
	}

	destroy();

	chart = new uPlot(buildOptions(element, size.width, size.height), props.data as AlignedData, element);
	chartKey = key;
};

const onResize = (): void => {
	const element = container.value;

	if (element === null) {
		return;
	}

	const size = fitSize(element.clientWidth, props.height);

	if (size === null) {
		return;
	}

	if (chart === null) {
		render();
	} else {
		chart.setSize(size);
	}
};

onMounted((): void => {
	render();

	if (container.value !== null) {
		resizeObserver = new ResizeObserver(onResize);
		resizeObserver.observe(container.value);
	}

	themeObserver = new MutationObserver((): void => {
		themeVersion.value++;
	});
	themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });
});

onBeforeUnmount((): void => {
	resizeObserver?.disconnect();
	themeObserver?.disconnect();

	resizeObserver = null;
	themeObserver = null;

	destroy();
});

watch(
	() => [props.data, optionsKey()],
	(): void => {
		render();
	},
	{ flush: 'post' }
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
.chart-time-series {
	width: 100%;
	color: var(--el-text-color-regular);

	:deep(.u-legend) {
		color: var(--el-text-color-regular);
	}
}
</style>
