import { FbSpinner } from '@fastybird/web-ui-components';
import { Meta, StoryObj } from '@storybook/vue3';

import './fb-spinner.stories.scss';

const meta: Meta<typeof FbSpinner> = {
	component: FbSpinner,
	title: 'Components/Feedback/Spinner',
	argTypes: {
		size: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['large', 'default', 'small'],
			description: 'spinner size',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: 'default' },
			},
		},
		type: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['default', 'primary', 'info', 'success', 'warning', 'danger'],
			description: 'spinner type',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: 'default' },
			},
		},
		bg: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'determine whether the spinner background color is always on',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: 'false' },
			},
		},
	},
	args: {
		size: 'default',
		type: 'default',
		bg: true,
	},
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbSpinner>;

export const Component: Story = {
	tags: ['hideInSidebar'],
};

export const Sizes: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-spinner size="large" />
	<fb-spinner />
	<fb-spinner size="small" />
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbSpinner },
		template: `
<div class="fb-spinner-story-block">
	<div class="fb-spinner-story-block__item">
		<fb-spinner size="large" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner size="small" />
	</div>
</div>`,
	}),
};

export const Variants: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-spinner type="primary" />
	<fb-spinner type="default" />
	<fb-spinner type="info" />
	<fb-spinner type="success" />
	<fb-spinner type="warning" />
	<fb-spinner type="danger" />
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbSpinner },
		template: `
<div class="fb-spinner-story-block">
	<div class="fb-spinner-story-block__item">
		<fb-spinner type="primary" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner type="default" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner type="info" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner type="success" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner type="warning" />
	</div>
	<div class="fb-spinner-story-block__item">
		<fb-spinner type="danger" />
	</div>
</div>`,
	}),
};
