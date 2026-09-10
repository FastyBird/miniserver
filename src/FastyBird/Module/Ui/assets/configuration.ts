import { InjectionKey } from 'vue';

import { IUiModuleConfiguration, IUiModuleMeta } from './types';

export const metaKey: InjectionKey<IUiModuleMeta> = Symbol('ui-module_meta');
export const configurationKey: InjectionKey<IUiModuleConfiguration> = Symbol('ui-module_configuration');
