<template>
	<div class="display-chart-graph">
		<div class="display-chart-graph__toolbar">
			<el-radio-group
				v-model="selectedPeriod"
				:aria-label="t('uiModule.chart.period.label')"
				size="small"
			>
				<el-radio-button
					v-for="option in options"
					:key="option"
					:value="option"
				>
					{{ t(`uiModule.chart.period.${periodLabel(option).unit}`, { count: periodLabel(option).count }) }}
				</el-radio-button>
			</el-radio-group>
		</div>

		<div
			:style="{ minHeight: `${height}px` }"
			class="display-chart-graph__body"
		>
			<chart-time-series
				v-if="chartData !== null"
				:band="band"
				:data="chartData"
				:height="height"
				:maximum="display.maximumValue"
				:minimum="display.minimumValue"
				:precision="display.precision"
				:stepped="digital"
				:unit="series?.unit ?? null"
			/>

			<div
				v-if="loading"
				class="display-chart-graph__state display-chart-graph__state--loading"
				role="status"
			>
				<el-icon class="is-loading">
					<Icon icon="fa6-solid:spinner" />
				</el-icon>
				<span>{{ t('uiModule.chart.loading') }}</span>
			</div>

			<div
				v-else-if="error !== null"
				class="display-chart-graph__state"
				role="alert"
			>
				<el-alert
					:closable="false"
					:title="t(`uiModule.chart.error.${error.kind}`)"
					show-icon
					type="error"
				/>
				<el-button
					size="small"
					@click="refresh"
				>
					{{ t('uiModule.chart.retry') }}
				</el-button>
			</div>

			<div
				v-else-if="chartData === null"
				class="display-chart-graph__state"
			>
				<el-empty :description="t('uiModule.chart.empty')" />
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElAlert, ElButton, ElEmpty, ElIcon, ElRadioButton, ElRadioGroup } from 'element-plus';

import { Icon } from '@iconify/vue';

import { useDataSourceHistory } from '../../composables';
import { buildChartData } from '../../utilities/chart';
import { normalizePeriod, periodLabel, periodOptions } from '../../utilities/history';
import ChartTimeSeries from '../charts/chart-time-series.vue';

import { IDisplayChartGraphProps } from './display-chart-graph.types';

defineOptions({
	name: 'DisplayChartGraph',
});

const props = withDefaults(defineProps<IDisplayChartGraphProps>(), {
	digital: false,
	height: 240,
});

const { t } = useI18n();

// The period of the display is where the chart starts. The selector changes what is shown here, it does not edit the display.
const selectedPeriod = ref<number>(normalizePeriod(props.display.period));

watch(
	() => props.display.period,
	(period): void => {
		selectedPeriod.value = normalizePeriod(period);
	}
);

const options = computed<number[]>((): number[] => periodOptions(props.display.period));

const { series, loading, error, empty, refresh } = useDataSourceHistory({
	widgetId: () => props.widgetId,
	dataSourceId: () => props.dataSourceId,
	period: selectedPeriod,
});

const band = computed<boolean>((): boolean => props.display.enableMinMax === true);

const chartData = computed(() => {
	if (series.value === null || empty.value) {
		return null;
	}

	return buildChartData(series.value, { stepped: props.digital, band: band.value });
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
.display-chart-graph {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
	width: 100%;

	&__toolbar {
		display: flex;
		justify-content: flex-end;
	}

	&__body {
		position: relative;
	}

	&__state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		position: absolute;
		inset: 0;

		&--loading {
			flex-direction: row;
			background: color-mix(in srgb, var(--el-bg-color) 70%, transparent);
			color: var(--el-text-color-secondary);
		}
	}
}
</style>
