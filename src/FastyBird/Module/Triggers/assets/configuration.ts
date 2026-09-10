import { InjectionKey } from 'vue';

import { ITriggersModuleConfiguration, ITriggersModuleMeta } from './types';

export const metaKey: InjectionKey<ITriggersModuleMeta> = Symbol('triggers-module_meta');
export const configurationKey: InjectionKey<ITriggersModuleConfiguration> = Symbol('triggers-module_configuration');
