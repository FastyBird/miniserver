import { App } from 'vue';
import { I18n } from 'vue-i18n';
import { Router } from 'vue-router';

import { Pinia } from 'pinia';

export * from '../composables/types';

export interface IExtensionOptions<I18T extends Record<string, unknown>> {
	router: Router;
	meta: {
		author: string;
		website: string;
		version: string;
	};
	store: Pinia;
	i18n: I18n<I18T>;
}

export interface IExtensionsOptions<I18T extends Record<string, unknown>> {
	extensions: IExtension[];
	options: IExtensionOptions<I18T>;
}

type ExtensionModule = {
	install: (app: App, options: IExtensionOptions<any>) => void;
};

export interface IExtension {
	name: string;
	module: ExtensionModule;
}
