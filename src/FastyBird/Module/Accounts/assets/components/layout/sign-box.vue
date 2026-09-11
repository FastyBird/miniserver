<template>
	<div class="flex flex-row items-center align-center w-full h-full">
		<div class="mx-a w-[25rem]">
			<el-card
				v-if="isMDDevice"
				class="mb-5"
			>
				<router-link
					:to="{ name: routeNames.signIn }"
					class="block w-[8rem] my-0 mx-a"
				>
					<logo class="fill-brand-primary" />
				</router-link>

				<slot />
			</el-card>

			<div
				v-else
				class="mb-5"
			>
				<router-link
					:to="{ name: routeNames.signIn }"
					class="block w-[8rem] my-0 mx-a"
				>
					<logo class="fill-brand-primary" />
				</router-link>

				<slot />
			</div>

			<div class="text-center">
				<i18n-t
					v-if="route.name === routeNames.signUp"
					keypath="messages.haveAccount"
					tag="p"
				>
					<router-link :to="{ name: routeNames.signIn }">
						{{ t('accountsModule.buttons.signIn.title') }}
					</router-link>
				</i18n-t>

				<i18n-t
					v-else
					keypath="messages.notHaveAccount"
					tag="p"
				>
					<router-link :to="{ name: routeNames.signUp }">
						{{ t('accountsModule.buttons.signUp.title') }}
					</router-link>
				</i18n-t>

				<div class="flex flex-row justify-center items-center mb-5">
					<el-link
						:underline="false"
						href="http://www.github.com/fastybird"
						target="_blank"
						class="mx-5"
					>
						<template #icon>
							<el-icon :size="20">
								<Icon icon="fa6-brands:github" />
							</el-icon>
						</template>
					</el-link>

					<el-divider direction="vertical" />

					<el-link
						:underline="false"
						href="http://www.x.com/fastybird"
						target="_blank"
						class="mx-5"
					>
						<template #icon>
							<el-icon :size="20">
								<Icon icon="fa6-brands:x-twitter" />
							</el-icon>
						</template>
					</el-link>

					<el-divider direction="vertical" />

					<el-link
						:underline="false"
						href="http://www.facebook.com/fastybird"
						target="_blank"
						class="mx-5"
					>
						<template #icon>
							<el-icon :size="20">
								<Icon icon="fa6-brands:facebook" />
							</el-icon>
						</template>
					</el-link>
				</div>

				<div class="flex flex-row justify-center items-baseline gap-[0.5rem]">
					&copy;
					<el-link
						:href="moduleMeta?.website"
						target="_blank"
					>
						{{ moduleMeta?.author }}
					</el-link>
					2017
				</div>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { inject } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';

import { ElCard, ElDivider, ElIcon, ElLink } from 'element-plus';

import { useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

// @ts-ignore
import Logo from '../../assets/images/fastybird_bird.svg?component';
import { useRoutesNames } from '../../composables';
import { metaKey } from '../../configuration';

defineOptions({
	name: 'SignBox',
});

const { routeNames } = useRoutesNames();
const { t } = useI18n();

const moduleMeta = inject(metaKey);

const route = useRoute();
const { isMDDevice } = useBreakpoints();
</script>
