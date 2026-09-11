import { ref } from 'vue';

import { ElButton } from 'element-plus';

import { FbBottomNavigation, FbBottomNavigationHeading } from '@fastybird/web-ui-components';
import { action } from '@storybook/addon-actions';
import { Meta, StoryObj } from '@storybook/vue3';

import './fb-bottom-navigation.stories.scss';

const meta: Meta<typeof FbBottomNavigation> = {
	component: FbBottomNavigation,
	title: 'Components/Navigation/Bottom navigation',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbBottomNavigation>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
</template>

<script setup lang="ts">
</script>

<style scoped>
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { ElButton, FbBottomNavigation, FbBottomNavigationHeading },
		setup: () => {
			const show = ref<boolean>(false);
			const onClick = action('button-clicked');

			return {
				show,
				onClick,
			};
		},
		template: `
<div class="fb-bottom-navigation-story-block">
	<el-button @click="show = true">Open menu</el-button>

	<fb-bottom-navigation
		:show="show"
		@close="show = false"
	>
		<template #heading>
			<fb-bottom-navigation-heading :teleport="false">
				Select one from items
			</fb-bottom-navigation-heading>
		</template>

		<fb-bottom-navigation-content :teleport="false">
			<el-button
				style="font-size: 1.8rem; padding: 1rem;"
				link
				block
				@click.prevent="onClick"
			>
				Option one
			</el-button>
		</fb-bottom-navigation-content>

		<fb-bottom-navigation-content :teleport="false">
			<el-button
				style="font-size: 1.8rem; padding: 1rem;"
				link
				block
				@click.prevent="onClick"
			>
				Option two
			</el-button>
		</fb-bottom-navigation-content>

		<fb-bottom-navigation-content :teleport="false">
			<el-button
				style="font-size: 1.8rem; padding: 1rem;"
				link
				block
				@click.prevent="onClick"
			>
				Option three
			</el-button>
		</fb-bottom-navigation-content>
	</fb-bottom-navigation>
</div>`,
	}),
};
