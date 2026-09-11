import { FbComponentLoading } from '@fastybird/web-ui-components';
import { Meta, StoryObj } from '@storybook/vue3';

const meta: Meta<typeof FbComponentLoading> = {
	component: FbComponentLoading,
	title: 'Components/Feedback/Component loading',
	argTypes: {
		size: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['large', 'default', 'small'],
			description: 'loading status size',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: 'default' },
			},
		},
	},
	args: {
		size: 'default',
	},
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbComponentLoading>;

export const Component: Story = {};

export const Sizes: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-component-loading size="large" />
	<fb-component-loading />
	<fb-component-loading size="small" />
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbComponentLoading },
		template: `
<div style="max-width: 600px;">
	<div>
		<fb-component-loading size="large" />
	</div>
	<div style="margin-top: 20px;">
		<fb-component-loading />
	</div>
	<div style="margin-top: 20px;">
		<fb-component-loading size="small" />
	</div>
</div>`,
	}),
};
