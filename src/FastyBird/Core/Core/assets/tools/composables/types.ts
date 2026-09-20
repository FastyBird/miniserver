import { ComputedRef, Ref } from 'vue';

import { AxiosInstance, AxiosResponse } from 'axios';
import { Emitter, Handler } from 'mitt';

import { Events } from '../eventBus';

export interface UseBackend {
	pendingRequests: Ref<number>;
	axios: AxiosInstance;
}

export interface UseBreakpoints {
	isXSDevice: ComputedRef<boolean>;
	isSMDevice: ComputedRef<boolean>;
	isMDDevice: ComputedRef<boolean>;
	isLGDevice: ComputedRef<boolean>;
	isXLDevice: ComputedRef<boolean>;
	isXXLDevice: ComputedRef<boolean>;
}

export interface UseDarkMode {
	isDark: ComputedRef<boolean>;
	toggleDark: (mode?: boolean) => boolean;
}

export interface UseEventBus {
	eventBus: Emitter<Events>;
	register: <Key extends keyof Events>(event: Key, listener: Handler<Events[Key]>) => void;
	unregister: <Key extends keyof Events>(event: Key, listener: Handler<Events[Key]>) => void;
	emit: <Key extends keyof Events>(event: Key, payload: Events[Key]) => void;
}

export interface UseFlashMessage {
	success: (message: string) => void;
	info: (message: string) => void;
	error: (message: string) => void;
	exception: (exception: Error, errorMessage: string) => void;
	requestError: (response: AxiosResponse, errorMessage: string) => void;
}
