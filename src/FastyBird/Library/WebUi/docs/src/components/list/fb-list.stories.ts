import { ref } from 'vue';

import { ElButton, ElIcon, ElSwitch } from 'element-plus';

import { FbList, FbListItem, ListItemVariantTypes } from '@fastybird/web-ui-components';
import { FasGear, FasPlug } from '@fastybird/web-ui-icons';
import { action } from '@storybook/addon-actions';
import { Meta, StoryObj } from '@storybook/vue3';

import './fb-list.stories.scss';

const meta: Meta<typeof FbList> = {
	component: FbList,
	subcomponents: { FbListItem },
	title: 'Components/Data/List',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbList>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-list>
		<template #heading>List of awesome items</template>
		<template #subheading>Subheading of awesome items</template>
		<template #buttons>
			<el-button size="small" @click.prevent="onClick">Add</el-button>
			<el-button size="small" type="warning" :icon="FasGear" @click.prevent="onClick" />
		</template>
		<fb-list-item @click="onClick">
			<template #title>Temperature</template>
			<template #subtitle>Master room measured temperature</template>
			<template #detail>23 °C</template>
			<template #icon>
				<el-icon>
					<fas-plug />
				</el-icon>
			</template>
		</fb-list-item>
		<fb-list-item @click="onClick">
			<template #title>Humidity</template>
			<template #subtitle>Master room measured humidity</template>
			<template #detail>43 %</template>
			<template #icon>
				<el-icon>
					<fas-plug />
				</el-icon>
			</template>
		</fb-list-item>
		<fb-list-item @click="onClick">
			<template #title>Heater</template>
			<template #subtitle>Master room heater switch</template>
			<template #button>
				<el-switch v-model="switchState" />
			</template>
			<template #icon>
				<el-icon>
					<fas-plug />
				</el-icon>
			</template>
		</fb-list-item>
	</fb-list>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const switchState = ref(true);

const onClick = () => {
	console.log('clicked')
};
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { ElButton, ElSwitch, ElIcon, FbList, FbListItem, FasGear, FasPlug },
		setup: () => {
			const switchState = ref(true);
			const onClick = action('button-clicked');

			return {
				switchState,
				onClick,
				FasGear,
				FasPlug,
			};
		},
		template: `
<fb-list>
	<template #heading>List of awesome items</template>
	<template #subheading>Subheading of awesome items</template>
	<template #buttons>
		<el-button size="small" @click.prevent="onClick">Add</el-button>
		<el-button size="small" type="warning" :icon="FasGear" @click.prevent="onClick" />
	</template>
	<fb-list-item @click="onClick">
		<template #title>Temperature</template>
		<template #subtitle>Master room measured temperature</template>
		<template #detail>23 °C</template>
		<template #icon>
			<el-icon>
				<fas-plug />
			</el-icon>
		</template>
	</fb-list-item>
	<fb-list-item @click="onClick">
		<template #title>Humidity</template>
		<template #subtitle>Master room measured humidity</template>
		<template #detail>43 %</template>
		<template #icon>
			<el-icon>
				<fas-plug />
			</el-icon>
		</template>
	</fb-list-item>
	<fb-list-item @click="onClick">
		<template #title>Heater</template>
		<template #subtitle>Master room heater switch</template>
		<template #button>
			<el-switch v-model="switchState" />
		</template>
		<template #icon>
			<el-icon>
				<fas-plug />
			</el-icon>
		</template>
	</fb-list-item>
</fb-list>`,
	}),
};

export const ListStyle: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-list>
		<template #heading>List of awesome items</template>
		<template #subheading>Subheading of awesome items</template>
		<template #buttons>
			<el-button size="small" @click.prevent="onClick">Add</el-button>
			<el-button size="small" type="warning" :icon="FasGear" @click.prevent="onClick" />
		</template>
		<fb-list-item @click="onClick" type="${ListItemVariantTypes.LIST}">
			<template #title>Temperature</template>
			<template #subtitle>Master room measured temperature</template>
			<template #detail>23 °C</template>
			<template #icon>
				<el-icon>
					<fas-plug />
				</el-icon>
			</template>
		</fb-list-item>
		<fb-list-item @click="onClick" type="${ListItemVariantTypes.LIST}">
			<template #title>Humidity</template>
			<template #subtitle>Master room measured humidity</template>
			<template #detail>43 %</template>
			<template #icon>
				<el-icon>
					<fas-plug />
				</el-icon>
			</template>
		</fb-list-item>
		<fb-list-item @click="onClick" type="${ListItemVariantTypes.LIST}">
			<template #title>Heater</template>
			<template #subtitle>Master room heater switch</template>
			<template #button>
				<el-switch v-model="switchState" />
			</template>
			<template #icon>
				<el-icon>
					<fas-plug />
				</el-icon>
			</template>
		</fb-list-item>
	</fb-list>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const switchState = ref(true);

const onClick = () => {
	console.log('clicked')
};
</script>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { ElButton, ElSwitch, ElIcon, FbList, FbListItem, FasGear, FasPlug },
		setup: () => {
			const switchState = ref(true);
			const onClick = action('button-clicked');

			return {
				switchState,
				onClick,
				FasGear,
				FasPlug,
			};
		},
		template: `
<fb-list>
	<template #heading>List of awesome items</template>
	<template #subheading>Subheading of awesome items</template>
	<template #buttons>
		<el-button size="small" @click.prevent="onClick">Add</el-button>
		<el-button size="small" type="warning" :icon="FasGear" @click.prevent="onClick" />
	</template>
	<fb-list-item @click="onClick" type="${ListItemVariantTypes.LIST}">
		<template #title>Temperature</template>
		<template #subtitle>Master room measured temperature</template>
		<template #detail>23 °C</template>
		<template #icon>
			<el-icon>
				<fas-plug />
			</el-icon>
		</template>
	</fb-list-item>
	<fb-list-item @click="onClick" type="${ListItemVariantTypes.LIST}">
		<template #title>Humidity</template>
		<template #subtitle>Master room measured humidity</template>
		<template #detail>43 %</template>
		<template #icon>
			<el-icon>
				<fas-plug />
			</el-icon>
		</template>
	</fb-list-item>
	<fb-list-item @click="onClick" type="${ListItemVariantTypes.LIST}">
		<template #title>Heater</template>
		<template #subtitle>Master room heater switch</template>
		<template #button>
			<el-switch v-model="switchState" />
		</template>
		<template #icon>
			<el-icon>
				<fas-plug />
			</el-icon>
		</template>
	</fb-list-item>
</fb-list>`,
	}),
};
