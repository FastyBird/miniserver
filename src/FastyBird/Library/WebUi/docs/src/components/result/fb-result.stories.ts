import { ElButton, ElCol, ElIcon, ElRow } from 'element-plus';

import { FbResult, ResultIconTypes, resultIcons } from '@fastybird/web-ui-components';
import { FarFaceSmile } from '@fastybird/web-ui-icons';
import { Meta, StoryObj } from '@storybook/vue3';

import './fb-result.stories.scss';

const meta: Meta<typeof FbResult> = {
	component: FbResult,
	title: 'Components/Feedback/Result',
	argTypes: {
		title: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'content as result title',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		subtitle: {
			type: { name: 'string', required: false },
			control: { type: 'text' },
			description: 'content as result subtitle',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: '-' },
			},
		},
		status: {
			type: { name: 'string', required: false },
			control: { type: 'select' },
			options: resultIcons,
			description: 'result status variant',
			table: {
				type: { summary: 'string' },
				defaultValue: { summary: ResultIconTypes.INFO },
			},
		},
		animation: {
			type: { name: 'boolean', required: false },
			control: { type: 'boolean' },
			description: 'animate status icon',
			table: {
				type: { summary: 'boolean' },
				defaultValue: { summary: 'false' },
			},
		},
	},
	args: {
		title: 'Successfully Configured Device!',
		subtitle: 'Your device: Living room heater is being to be prepared, please wait.',
		status: ResultIconTypes.INFO,
		animation: false,
	},
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbResult>;

export const Component: Story = {
	tags: ['hideInSidebar'],
};

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<el-row>
		<el-col :sm="12" :lg="6">
			<fb-result
				status="success"
				title="Success Tip"
				subtitle="Please follow the instructions"
			>
				<template #extra>
					<el-button>Back</el-button>
				</template>
			</fb-result>
		</el-col>
		<el-col :sm="12" :lg="6">
			<fb-result
				status="warning"
				title="Warning Tip"
				subtitle="Please follow the instructions"
			>
				<template #extra>
					<el-button>Back</el-button>
				</template>
			</fb-result>
		</el-col>
		<el-col :sm="12" :lg="6">
			<fb-result
				status="error"
				title="Error Tip"
				subtitle="Please follow the instructions"
			>
				<template #extra>
					<el-button>Back</el-button>
				</template>
			</fb-result>
		</el-col>
		<el-col :sm="12" :lg="6">
			<fb-result
				status="info"
				title="Info Tip"
				subtitle="Please follow the instructions"
			>
				<template #extra>
					<el-button>Back</el-button>
				</template>
			</fb-result>
		</el-col>
	</el-row>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { ElButton, ElRow, ElCol, FbResult },
		template: `
<el-row>
	<el-col :sm="12" :lg="6">
		<fb-result
			status="success"
			title="Success Tip"
			subtitle="Please follow the instructions"
		>
			<template #extra>
				<el-button>Back</el-button>
			</template>
		</fb-result>
	</el-col>
	<el-col :sm="12" :lg="6">
		<fb-result
			status="warning"
			title="Warning Tip"
			subtitle="Please follow the instructions"
		>
			<template #extra>
				<el-button>Back</el-button>
			</template>
		</fb-result>
	</el-col>
	<el-col :sm="12" :lg="6">
		<fb-result
			status="error"
			title="Error Tip"
			subtitle="Please follow the instructions"
		>
			<template #extra>
				<el-button>Back</el-button>
			</template>
		</fb-result>
	</el-col>
	<el-col :sm="12" :lg="6">
		<fb-result
			status="info"
			title="Info Tip"
			subtitle="Please follow the instructions"
		>
			<template #extra>
				<el-button>Back</el-button>
			</template>
		</fb-result>
	</el-col>
</el-row>`,
	}),
};

export const LoadingStatus403: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-result
		status="${ResultIconTypes.NOT_ALLOWED}"
		title="403"
		subtitle="Sorry, you are not authorized to access this page."
	>
		<template #extra>
			<el-button>Back Home</el-button>
		</template>
	</fb-result>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { ElButton, FbResult },
		template: `
<fb-result
	status="${ResultIconTypes.NOT_ALLOWED}"
	title="403"
	subtitle="Sorry, you are not authorized to access this page."
>
	<template #extra>
		<el-button>Back Home</el-button>
	</template>
</fb-result>`,
	}),
};

export const LoadingStatus404: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-result
		status="${ResultIconTypes.NOT_FOUND}"
		title="404"
		subtitle="Sorry, the page you visited does not exist."
	>
		<template #extra>
			<el-button>Back Home</el-button>
		</template>
	</fb-result>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { ElButton, FbResult },
		template: `
<fb-result
	status="${ResultIconTypes.NOT_FOUND}"
	title="404"
	subtitle="Sorry, the page you visited does not exist."
>
	<template #extra>
		<el-button>Back Home</el-button>
	</template>
</fb-result>`,
	}),
};

export const LoadingStatus500: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-result
		status="${ResultIconTypes.SERVER_ERROR}"
		title="500"
		subtitle="Sorry, something went wrong."
	>
		<template #extra>
			<el-button>Back Home</el-button>
		</template>
	</fb-result>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { ElButton, FbResult },
		template: `
<fb-result
	status="${ResultIconTypes.SERVER_ERROR}"
	title="500"
	subtitle="Sorry, something went wrong."
>
	<template #extra>
		<el-button>Back Home</el-button>
	</template>
</fb-result>`,
	}),
};

export const CustomIcon: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-result
		title="Great, we have done all the operations!"
	>
		<template #icon>
			<el-icon size="80">
				<far-face-smile />
			</el-icon>
		</template>
		<template #extra>
			<el-button>Next</el-button>
		</template>
	</fb-result>
</template>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { ElButton, ElIcon, FbResult, FarFaceSmile },
		template: `
<fb-result
	title="Great, we have done all the operations!"
>
	<template #icon>
		<el-icon size="80">
			<far-face-smile />
		</el-icon>
	</template>
	<template #extra>
		<el-button>Next</el-button>
	</template>
</fb-result>`,
	}),
};
