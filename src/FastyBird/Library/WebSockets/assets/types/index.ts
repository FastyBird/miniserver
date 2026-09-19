import { Plugin, Ref } from 'vue';

export type OnOpenCallback = () => void;
export type OnCloseCallback = (code: number, reason: string | null) => void;
export type OnConnectCallback = () => void;
export type OnDisconnectCallback = () => void;

export type SubscribeCallback = (content: string) => void;

export interface RpCallResponse<T> {
	data: T;
}

export interface IRpcCallError extends Error {
	topic: string;
	message: string;
	details: string | any[] | null;
}

export interface IWampSubscription {
	topic: string;
	subscribed: boolean;
	callbacks: SubscribeCallback[];
}

export interface IWampRpCall {
	id: string;
	resolve: any;
	reject: any;
}

export interface IWampClient {
	isConnected: Ref<boolean>;
	isConnecting: Ref<boolean>;
	isLost: Ref<boolean>;

	set host(host: string);

	get host(): string;

	set logger(logger: IWampLogger);

	open(): void;

	reconnect(): void;

	close(reason?: number, message?: string): void;

	subscribe(topic: string, handler: SubscribeCallback): boolean;

	unsubscribe(topic: string, handler: SubscribeCallback): boolean;

	isSubscribed(topic: string): boolean;

	publish(topic: string, event: string, exclude?: string[] | null, eligible?: string[] | null): boolean;

	call<T>(topic: string, ...data: any): Promise<RpCallResponse<T>>;

	onOpenEvent(listener: OnOpenCallback): void;

	onCloseEvent(listener: OnCloseCallback): void;

	onConnectEvent(listener: OnConnectCallback): void;

	onDisconnectEvent(listener: OnDisconnectCallback): void;

	offOpenEvent(listener: OnOpenCallback): void;

	offCloseEvent(listener: OnCloseCallback): void;

	offConnectEvent(listener: OnConnectCallback): void;

	offDisconnectEvent(listener: OnDisconnectCallback): void;
}

export interface IWampLogger {
	info(text: string, ...args: any[]): void;

	error(text: string, ...args: any[]): void;

	warn(text: string, ...args: any[]): void;

	event(text: string, ...args: any[]): void;
}

export interface PluginOptions {
	host: string;
	debug: boolean;
	autoReestablish: boolean;
	autoCloseTimeout: number;
}

export interface IWebSocketResult<T> {
	status: Ref<boolean>;
	open: () => void;
	close: (reason?: number, message?: string) => void;
	call: (topic: string, ...data: any) => Promise<RpCallResponse<T>>;
	client: IWampClient | undefined;
}

export enum MessageCode {
	MSG_WELCOME = 0,
	MSG_PREFIX = 1,
	MSG_CALL = 2,
	MSG_CALL_RESULT = 3,
	MSG_CALL_ERROR = 4,
	MSG_SUBSCRIBE = 5,
	MSG_UNSUBSCRIBE = 6,
	MSG_PUBLISH = 7,
	MSG_EVENT = 8,
}

export type InstallFunction = Plugin & { installed?: boolean; wampClient?: IWampClient };
