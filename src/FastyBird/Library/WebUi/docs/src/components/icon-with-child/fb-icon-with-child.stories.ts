import { FbIconWithChild } from '@fastybird/web-ui-components';
import { FasBell, FasEnvelope } from '@fastybird/web-ui-icons';
import { Meta, StoryObj } from '@storybook/vue3';

import './fb-icon-with-child.stories.scss';

const meta: Meta<typeof FbIconWithChild> = {
	component: FbIconWithChild,
	title: 'Components/Basic/Icon with child',
	argTypes: {
		type: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: ['default', 'primary', 'info', 'success', 'warning', 'danger'],
			description: 'child icon type',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: 'default' },
			},
		},
		size: {
			type: { name: 'number', required: false },
			control: { type: 'text' },
			description: 'main icon size',
			table: {
				type: { summary: 'number' },
				defaultValue: { summary: '-' },
			},
		},
		color: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'main icon color',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
	},
	args: {
		type: 'default',
	},
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbIconWithChild>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-icon-with-child :size="50">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" type="primary">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" type="info">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" type="success">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" type="warning">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" type="danger">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
</template>

<style scoped>
.icon {
	color: var(--fb-color-default-light-5);
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbIconWithChild, FasBell, FasEnvelope },
		template: `
<div class="fb-icon-with-child-story-block">
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" type="primary">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" type="info">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" type="success">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" type="warning">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" type="danger">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
</div>`,
	}),
};

export const Sizes: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-icon-with-child :size="80" type="primary">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="50" type="primary">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="40" type="primary">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child :size="30" type="primary">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
	<fb-icon-with-child type="primary">
		<template #primary><fas-envelope class="icon" /></template>
		<template #secondary><fas-bell /></template>
	</fb-icon-with-child>
</template>

<style scoped>
.icon {
	color: var(--fb-color-default-light-5);
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { FbIconWithChild, FasBell, FasEnvelope },
		template: `
<div class="fb-icon-with-child-story-block">
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="80" type="primary">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="50" type="primary">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="40" type="primary">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child :size="30" type="primary">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
	<div class="fb-icon-with-child-story-block__item">
		<fb-icon-with-child type="primary">
			<template #primary><fas-envelope class="icon-color" /></template>
			<template #secondary><fas-bell /></template>
		</fb-icon-with-child>
	</div>
</div>`,
	}),
};
