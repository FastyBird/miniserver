import { InjectionKey } from 'vue';

import { IUiModuleMeta } from './types';

export const metaKey: InjectionKey<IUiModuleMeta> = Symbol('ui-module_meta');
