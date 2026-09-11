import { FbComponentLoadingError } from '@fastybird/web-ui-components';
import { Meta, StoryObj } from '@storybook/vue3';

const meta: Meta<typeof FbComponentLoadingError> = {
	component: FbComponentLoadingError,
	title: 'Components/Feedback/Component loading error',
	argTypes: {
		size: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['large', 'default', 'small'],
			description: 'loading result size',
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

type Story = StoryObj<typeof FbComponentLoadingError>;

export const Component: Story = {};

export const Sizes: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-component-loading-error size="large" />
	<fb-component-loading-error />
	<fb-component-loading-error size="small" />
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbComponentLoadingError },
		template: `
<div style="max-width: 600px;">
	<div>
		<fb-component-loading-error size="large" />
	</div>
	<div style="margin-top: 20px;">
		<fb-component-loading-error />
	</div>
	<div style="margin-top: 20px;">
		<fb-component-loading-error size="small" />
	</div>
</div>`,
	}),
};
