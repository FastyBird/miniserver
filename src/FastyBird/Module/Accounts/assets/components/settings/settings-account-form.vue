<template>
	<el-form
		ref="accountFormEl"
		:model="accountForm"
		:rules="rules"
		:label-position="props.layout === LayoutTypes.PHONE ? 'top' : 'right'"
		:label-width="180"
		status-icon
		class="sm:px-5"
	>
		<div class="mb-5">
			<el-form-item
				:label="t('accountsModule.fields.emailAddress.title')"
				prop="emailAddress"
				class="mb-2"
			>
				<el-input
					v-model="accountForm.emailAddress"
					name="emailAddress"
				/>
			</el-form-item>
		</div>

		<div class="mb-5">
			<el-form-item
				:label="t('accountsModule.fields.language.title')"
				prop="language"
				class="mb-2"
			>
				<el-select
					v-model="accountForm.language"
					name="language"
				>
					<el-option
						v-for="item in languagesOptions"
						:key="item.value"
						:label="item.name"
						:value="item.value"
					/>
				</el-select>
			</el-form-item>
		</div>

		<div class="mb-5">
			<el-form-item
				:label="t('accountsModule.fields.firstName.title')"
				prop="firstName"
				class="mb-2"
			>
				<el-input
					v-model="accountForm.firstName"
					name="firstName"
				/>
			</el-form-item>
		</div>

		<div class="mb-5">
			<el-form-item
				:label="t('accountsModule.fields.lastName.title')"
				prop="lastName"
				class="mb-2"
			>
				<el-input
					v-model="accountForm.lastName"
					name="lastName"
				/>
			</el-form-item>
		</div>

		<div class="mb-5">
			<el-form-item
				:label="t('accountsModule.fields.middleName.title')"
				prop="middleName"
				class="mb-2"
			>
				<el-input
					v-model="accountForm.middleName"
					name="middleName"
				/>
			</el-form-item>
		</div>

		<el-divider class="my-10" />

		<el-row
			:gutter="20"
			class="sm:mb-5"
		>
			<el-col
				:sm="24"
				:md="12"
			>
				<el-form-item
					:label="t('accountsModule.fields.datetime.timezone.title')"
					prop="timezone"
					class="mb-2"
				>
					<el-select
						v-model="accountForm.timezone"
						prop="timezone"
					>
						<el-option-group
							v-for="item in zonesOptions"
							:key="item.name"
							:label="item.name"
						>
							<el-option
								v-for="subItem in item.items"
								:key="subItem.value"
								:label="subItem.name"
								:value="subItem.value"
							/>
						</el-option-group>
					</el-select>
				</el-form-item>
			</el-col>

			<el-col
				:sm="24"
				:md="12"
			>
				<el-form-item
					:label="t('accountsModule.fields.datetime.weekStartOn.title')"
					prop="weekStart"
					class="mb-2"
				>
					<el-select
						v-model="accountForm.weekStart"
						prop="weekStart"
					>
						<el-option
							v-for="item in weekStartOptions"
							:key="item.value"
							:label="item.name"
							:value="item.value"
						/>
					</el-select>
				</el-form-item>
			</el-col>
		</el-row>

		<el-row
			:gutter="20"
			class="sm:mb-5"
		>
			<el-col
				:sm="24"
				:md="12"
			>
				<el-form-item
					:label="t('accountsModule.fields.datetime.dateFormat.title')"
					prop="dateFormat"
					class="mb-2"
				>
					<el-select
						v-model="accountForm.dateFormat"
						prop="dateFormat"
					>
						<el-option
							v-for="item in dateFormatOptions"
							:key="item.value"
							:label="item.name"
							:value="item.value"
						/>
					</el-select>
				</el-form-item>
			</el-col>

			<el-col
				:sm="24"
				:md="12"
			>
				<el-form-item
					:label="t('accountsModule.fields.datetime.timeFormat.title')"
					prop="timeFormat"
					class="mb-2"
				>
					<el-select
						v-model="accountForm.timeFormat"
						prop="timeFormat"
					>
						<el-option
							v-for="item in timeFormatOptions"
							:key="item.value"
							:label="item.name"
							:value="item.value"
						/>
					</el-select>
				</el-form-item>
			</el-col>
		</el-row>
	</el-form>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElCol, ElDivider, ElForm, ElFormItem, ElInput, ElOption, ElOptionGroup, ElRow, ElSelect, FormInstance, FormRules } from 'element-plus';
import get from 'lodash.get';

import { ModuleSource, injectStoresManager, useFlashMessage } from '@fastybird/miniserver-core';

import { useTimezones } from '../../composables';
import { accountStoreKey } from '../../configuration';
import { FormResultType, FormResultTypes, LayoutTypes } from '../../types';

import { ISettingsAccountForm, ISettingsAccountFormProps } from './settings-account-form.types';

defineOptions({
	name: 'SettingsAccountForm',
});

const props = withDefaults(defineProps<ISettingsAccountFormProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
	layout: LayoutTypes.DEFAULT,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultType): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();

const storesManager = injectStoresManager();

const accountStore = storesManager.getStore(accountStoreKey);

const { timezones } = useTimezones();

const countries: string[] = ['Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific'];

const accountFormEl = ref<FormInstance | undefined>(undefined);

const rules = reactive<FormRules<ISettingsAccountForm>>({
	emailAddress: [
		{ required: true, message: t('accountsModule.fields.emailAddress.validation.required'), trigger: 'change' },
		{ type: 'email', message: t('accountsModule.fields.emailAddress.validation.email'), trigger: 'change' },
	],
	firstName: [{ required: true, message: t('accountsModule.fields.firstName.validation.required'), trigger: 'change' }],
	lastName: [{ required: true, message: t('accountsModule.fields.lastName.validation.required'), trigger: 'change' }],
	middleName: [{ required: false }],
	language: [{ required: false }],
	weekStart: [{ required: false }],
	timezone: [{ required: false }],
	dateFormat: [{ required: false }],
	timeFormat: [{ required: false }],
});

const accountForm = reactive<ISettingsAccountForm>({
	emailAddress: props.account.email?.address ?? '@',
	firstName: props.account.details.firstName,
	lastName: props.account.details.lastName,
	middleName: props.account.details.middleName ?? undefined,
	language: props.account.language,
	weekStart: props.account.weekStart,
	timezone: props.account.dateTime.timezone,
	dateFormat: props.account.dateTime.dateFormat,
	timeFormat: props.account.dateTime.timeFormat,
});

const languagesOptions: { name: string; value: string }[] = [
	{
		name: 'English',
		value: 'en',
	},
	{
		name: 'Czech',
		value: 'cs',
	},
];

const weekStartOptions: { name: string; value: number }[] = [
	{
		name: t('accountsModule.fields.datetime.weekStartOn.values.monday'),
		value: 1,
	},
	{
		name: t('accountsModule.fields.datetime.weekStartOn.values.saturday'),
		value: 6,
	},
	{
		name: t('accountsModule.fields.datetime.weekStartOn.values.sunday'),
		value: 7,
	},
];

const dateFormatOptions: { name: string; value: string }[] = [
	{
		name: 'mm/dd/yyyy',
		value: 'MM/dd/yyyy',
	},
	{
		name: 'dd/mm/yyyy',
		value: 'dd/MM/yyyy',
	},
	{
		name: 'dd.mm.yyyy',
		value: 'dd.MM.yyyy',
	},
	{
		name: 'yyyy-mm-dd',
		value: 'yyyy-MM-dd',
	},
];

const timeFormatOptions: { name: string; value: string }[] = [
	{
		name: 'hh:mm',
		value: 'HH:mm',
	},
	{
		name: 'hh:mm am/pm',
		value: 'hh:mm a',
	},
];

const zonesOptions = countries.map((country): { name: string; items: { name: string; value: string }[] } => {
	return {
		name: country,
		items: timezones
			.filter((zone) => zone.substring(0, zone.search('/')) === country)
			.map((timezone) => {
				return {
					value: timezone,
					name: timezone.substring(timezone.search('/') + 1),
				};
			})
			.sort((a, b) => {
				const nameA = a.name.toUpperCase();
				const nameB = b.name.toUpperCase();

				if (nameA < nameB) {
					return -1;
				}

				if (nameA > nameB) {
					return 1;
				}

				return 0;
			}),
	};
});

let timer: number;

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FormResultTypes.NONE);
};

const updateAccount = async (): Promise<void> => {
	const errorMessage = t('accountsModule.messages.accountNotEdited');

	try {
		await accountStore.edit({
			data: {
				details: {
					firstName: accountForm.firstName,
					lastName: accountForm.lastName,
					middleName: accountForm.middleName,
				},
				language: accountForm.language,
				weekStart: accountForm.weekStart,
				dateTime: {
					timezone: accountForm.timezone,
					dateFormat: accountForm.dateFormat,
					timeFormat: accountForm.timeFormat,
				},
			},
		});

		emit('update:remoteFormResult', FormResultTypes.OK);

		flashMessage.success('Your account has been updated successfully.');

		timer = window.setTimeout(clearResult, 2000);
	} catch (e: any) {
		if (get(e, 'exception', null) !== null) {
			flashMessage.exception(e.exception, errorMessage);
		} else {
			flashMessage.error(errorMessage);
		}

		emit('update:remoteFormResult', FormResultTypes.ERROR);

		timer = window.setTimeout(clearResult, 2000);
	}
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val: boolean): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			await accountFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				emit('update:remoteFormResult', FormResultTypes.WORKING);

				// Email has been changed
				if (accountForm.emailAddress !== props.account.email?.address) {
					const storedEmail = accountStore.emails().find((email) => email.address.toLowerCase() === accountForm.emailAddress.toLowerCase());

					const emailErrorMessage = t('accountsModule.messages.emailNotEdited');

					if (typeof storedEmail !== 'undefined') {
						try {
							await accountStore.editEmail({
								id: storedEmail.id,
								data: {
									default: true,
									private: storedEmail.private,
								},
							});

							await updateAccount();
						} catch (e: any) {
							if (get(e, 'exception', null) !== null) {
								flashMessage.exception(e.exception, emailErrorMessage);
							} else {
								flashMessage.error(emailErrorMessage);
							}

							emit('update:remoteFormResult', FormResultTypes.ERROR);

							timer = window.setTimeout(clearResult, 2000);
						}
					} else {
						try {
							await accountStore.addEmail({
								type: {
									source: ModuleSource.ACCOUNTS,
									entity: 'email',
								},
								data: {
									address: accountForm.emailAddress,
									default: true,
									private: false,
								},
							});

							await updateAccount();
						} catch (e: any) {
							if (get(e, 'exception', null) !== null) {
								flashMessage.exception(e.exception, emailErrorMessage);
							} else {
								flashMessage.error(emailErrorMessage);
							}

							emit('update:remoteFormResult', FormResultTypes.ERROR);

							timer = window.setTimeout(clearResult, 2000);
						}
					}
				} else {
					await updateAccount();
				}
			});
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val: boolean): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			accountForm.emailAddress = props.account.email?.address ?? '@';
			accountForm.firstName = props.account.details.firstName;
			accountForm.lastName = props.account.details.lastName;
			accountForm.middleName = props.account.details.middleName ?? undefined;
			accountForm.language = props.account.language;
			accountForm.weekStart = props.account.weekStart;
			accountForm.timezone = props.account.dateTime.timezone;
			accountForm.dateFormat = props.account.dateTime.dateFormat;
			accountForm.timeFormat = props.account.dateTime.timeFormat;
		}
	}
);
</script>
