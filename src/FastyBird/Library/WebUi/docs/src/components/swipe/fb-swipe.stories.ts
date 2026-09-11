import { ElIcon } from 'element-plus';

import { FbSwipe } from '@fastybird/web-ui-components';
import { FasHeart, FasPenToSquare, FasTrash, FasXmark } from '@fastybird/web-ui-icons';
import { Meta, StoryObj } from '@storybook/vue3';

import './fb-swipe.stories.scss';

const meta: Meta<typeof FbSwipe> = {
	component: FbSwipe,
	title: 'Components/Data/Swipe actions',
	excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbSwipe>;

export const BasicUsage: Story = {
	parameters: {
		docs: {
			source: {
				code: `
<template>
	<fb-swipe
		:items="items"
		itemKey="id"
	>
		<template #default="{ item, index, revealLeft, revealRight, close }">
			<div class="custom-item">
				<h2>{{ item.title }}</h2>
				<p>{{ item.description }}</p>
				<span>index: {{ index }}</span>
			</div>
		</template>

		<template #left="{ item, close }">
			<div class="btn-trash">
				<el-icon>
					<fas-trash />
				</el-icon>
			</div>

			<div class="btn-close">
				<el-icon>
					<fas-xmark />
				</el-icon>
			</div>
		</template>

		<template #right="{ item }">
			<div class="btn-favorite">
				<el-icon>
					<fas-heart />
				</el-icon>
			</div>

			<div class="btn-edit">
				<el-icon>
					<fas-pen-to-square />
				</el-icon>
			</div>
		</template>

		<template #empty>
			<div>
				list is empty ( filtered or just empty )
			</div>
		</template>
	</fb-swipe>
</template>

<script setup lang="ts">
import { FasTrash, FasXmark, FasHeart, FasPenToSquare } from '@fastybird/web-ui-icons';

const items = [
	{
		id: 'a',
		title: 'Some title',
		description: 'some description',
		disabled: false,
	},
	{
		id: 'b',
		title: 'Some title',
		description: 'some description',
		disabled: false,
	},
	{
		id: 'c',
		title: 'Some title',
		description: 'some description',
		disabled: false,
	},
];
</script>

<style scoped>
.custom-item {
	width: 100%;
	box-shadow: none;
	border: 1px solid lightgray;
	padding: 1rem;
}

.btn-trash {
	display: flex;
	align-items: center;
	padding: 0 3rem;
	cursor: pointer;
	left: 0;
	color: white;
	background-color: rgb(255, 59, 48);
}

.btn-close {
	display: flex;
	align-items: center;
	padding: 0 3rem;
	cursor: pointer;
	left: 0;
	color: white;
	background-color: rgb(88, 86, 214);
}

.btn-favorite {
	display: flex;
	align-items: center;
	padding: 0 3rem;
	cursor: pointer;
	left: 0;
	color: white;
	background-color: rgb(0, 122, 255);
}

.btn-edit {
	display: flex;
	align-items: center;
	padding: 0 3rem;
	cursor: pointer;
	left: 0;
	color: white;
	background-color: rgb(76, 217, 100);
}
</style>`,
			},
		},
	},
	tags: ['hideInSidebar'],
	render: () => ({
		components: { ElIcon, FbSwipe, FasTrash, FasXmark, FasHeart, FasPenToSquare },
		setup: () => {
			const items = [
				{
					id: 'a',
					title: 'Some title',
					description: 'some description',
					disabled: false,
				},
				{
					id: 'b',
					title: 'Some title',
					description: 'some description',
					disabled: false,
				},
				{
					id: 'c',
					title: 'Some title',
					description: 'some description',
					disabled: false,
				},
			];

			return {
				items,
				FasTrash,
				FasXmark,
				FasHeart,
				FasPenToSquare,
			};
		},
		template: `
<div class="fb-swipe-story-block">
	<fb-swipe
		:items="items"
		itemKey="id"
	>
		<template #default="{ item, index, revealLeft, revealRight, close }">
			<div class="fb-swipe-story-block__item">
				<h2>{{ item.title }}</h2>
				<p>{{ item.description }}</p>
				<span>index: {{ index }}</span>
			</div>
		</template>

		<template #left="{ item, close }">
			<div class="fb-swipe-story-block__button-trash">
				<el-icon>
					<fas-trash />
				</el-icon>
			</div>

			<div class="fb-swipe-story-block__button-close">
				<el-icon>
					<fas-xmark />
				</el-icon>
			</div>
		</template>

		<template #right="{ item }">
			<div class="fb-swipe-story-block__button-favorite">
				<el-icon>
					<fas-heart />
				</el-icon>
			</div>

			<div class="fb-swipe-story-block__button-edit">
				<el-icon>
					<fas-pen-to-square />
				</el-icon>
			</div>
		</template>

		<template #empty>
			<div>
				list is empty ( filtered or just empty )
			</div>
		</template>
	</fb-swipe>
</div>`,
	}),
};
