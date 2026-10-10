<template>
	<div :class="ns.b()">
		<div :class="ns.e('header')">
			<h3 :class="ns.e('title')">
				<slot name="title" />

				<small v-if="'subtitle' in $slots">
					<slot name="subtitle" />
				</small>
			</h3>

			<div
				v-if="'buttons' in $slots"
				:class="ns.e('buttons')"
			>
				<slot name="buttons" />
			</div>
		</div>

		<slot />
	</div>
</template>

<script setup lang="ts">
import { useNamespace } from 'element-plus';

import './app-list.scss';

defineOptions({
	name: 'AppList',
});

const ns = useNamespace('list');
</script>
