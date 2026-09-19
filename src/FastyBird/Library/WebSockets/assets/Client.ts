import { Ref, ref } from 'vue';

import { Logger } from './Logger';
import { RpcCallError } from './RpcCallError';
import {
	IWampClient,
	IWampLogger,
	IWampRpCall,
	IWampSubscription,
	MessageCode,
	OnCloseCallback,
	OnConnectCallback,
	OnDisconnectCallback,
	OnOpenCallback,
	RpCallResponse,
	SubscribeCallback,
} from './types';

export class Client implements IWampClient {
	private _host: string;
	private _logger: IWampLogger;

	private socket: WebSocket | null;
	private sessionId: string | null = null;

	private onOpenEvents: OnOpenCallback[];
	private onCloseEvents: OnCloseCallback[];
	private onConnectEvents: OnConnectCallback[];
	private onDisconnectEvents: OnDisconnectCallback[];

	private subscriptions: IWampSubscription[];
	private rpcCalls: IWampRpCall[];

	public isConnected: Ref<boolean>;
	public isConnecting: Ref<boolean>;
	public isLost: Ref<boolean>;

	constructor(host: string, logger: IWampLogger | null) {
		this._host = host;
		this._logger = logger === null ? new Logger(false) : logger;

		this.socket = null;
		this.sessionId = null;

		this.onOpenEvents = [];
		this.onCloseEvents = [];
		this.onConnectEvents = [];
		this.onDisconnectEvents = [];

		this.subscriptions = [];
		this.rpcCalls = [];

		this.isLost = ref<boolean>(false);
		this.isConnected = ref<boolean>(false);
		this.isConnecting = ref<boolean>(false);
	}

	set host(host: string) {
		if (this.isConnected.value || this.isConnecting.value) {
			throw new Error('WS host for connected client could not be changed');
		}

		this._host = host;
	}

	get host(): string {
		return this._host;
	}

	set logger(logger: IWampLogger) {
		this._logger = logger;
	}

	public open(): void {
		if (this.isConnected.value || this.isConnecting.value) {
			return;
		}

		try {
			// Open connection to WS server
			this.socket = new WebSocket(this._host);
		} catch {
			throw new Error('Connection could not be established');
		}

		this.isConnecting.value = true;

		// Connection established with WS server
		this.socket.addEventListener('open', () => {
			this.onOpenEvents.forEach((eventCallback): void => {
				eventCallback();
			});
		});

		// Connection closed by WS server
		this.socket.addEventListener('close', (event) => {
			if (this.isConnected.value) {
				this.onDisconnectEvents.forEach((eventCallback): void => {
					eventCallback();
				});

				if (event.wasClean) {
					// Connection was closed cleanly (closing HS was performed)
					this.onCloseEvents.forEach((eventCallback): void => {
						eventCallback(0, `WS-${event.code}: ${event.reason}`);
					});
				} else {
					// Connection was closed uncleanly (lost without closing HS)
					this.onCloseEvents.forEach((eventCallback): void => {
						eventCallback(1, null);
					});
				}
			} else {
				// Connection could not be established in the first place
				this.onCloseEvents.forEach((eventCallback): void => {
					eventCallback(3, null);
				});
			}

			this.subscriptions.forEach((subscription, index) => {
				if (subscription.subscribed) {
					this.subscriptions[index].subscribed = false;
				}
			});

			this.isConnected.value = false;
		});

		this.socket.addEventListener('message', (event): void => {
			let message: any[] = [];

			try {
				// Parse received message
				message = JSON.parse(event.data);
			} catch {
				throw new Error(`Received message is not valid JSON`);
			}

			// On first position is message code
			const code: MessageCode = message.shift();

			switch (code) {
				// Welcome
				// [code: number, wamp session: string, wamp version: number, server info: string]
				case MessageCode.MSG_WELCOME: {
					const version: number = message[1];
					const server: string = message[2];

					if (version !== 1) {
						throw new Error(`Server "${server}" uses incompatible protocol version ${version}`);
					}

					this.sessionId = message[0];

					if (this.isLost.value) {
						this._logger.event('opened re-established connection after lost', this.sessionId, version, server);
					} else {
						this._logger.event('opened', this.sessionId, version, server);
					}

					this.isLost.value = false;
					this.isConnecting.value = false;
					this.isConnected.value = true;

					this.onConnectEvents.forEach((eventCallback): void => {
						eventCallback();
					});

					this.subscriptions.forEach((subscription, index) => {
						if (!subscription.subscribed) {
							this.subscriptions[index].subscribed = this.send([MessageCode.MSG_SUBSCRIBE, subscription.topic]);

							if (!this.subscriptions[index].subscribed) {
								this._logger.warn('subscribe failed', subscription.topic);
							} else {
								this._logger.info('subscribed', subscription.topic);
							}
						}
					});
					break;
				}

				// RPC Call result
				case MessageCode.MSG_CALL_RESULT:
					this.rpcCallResult(message, code);
					break;

				// RPC Call error
				case MessageCode.MSG_CALL_ERROR:
					this.rpcCallResult(message, code);
					break;

				// Event
				case MessageCode.MSG_EVENT:
					this.subscriptions
						.filter(({ topic }) => topic === message[0])
						.forEach((subscription): void => {
							subscription.callbacks.forEach((callback) => {
								callback(message[1]);
							});
						});
					break;
			}
		});
	}

	public reconnect(): void {
		this.close();
		this.isLost.value = true;
		this.open();
	}

	public close(reason?: number, message?: string): void {
		if (this.socket !== null) {
			this.socket.close(reason, message);

			this.socket = null;
		}

		this.isLost.value = false;
		this.isConnecting.value = false;
		this.isConnected.value = false;
	}

	public subscribe(topic: string, handler: SubscribeCallback): boolean {
		this._logger.event('subscribe', topic);

		if (!this.isSubscribed(topic)) {
			this.subscriptions.push({
				topic,
				subscribed: false,
				callbacks: [],
			});
		}

		const index = this.subscriptions.findIndex((subscription): boolean => subscription.topic === topic);

		if (index !== -1) {
			this.subscriptions[index].callbacks.push(handler);

			if (this.isConnected.value) {
				this.subscriptions[index].subscribed = this.send([MessageCode.MSG_SUBSCRIBE, topic]);

				if (!this.subscriptions[index].subscribed) {
					this._logger.warn('subscribe failed', topic);
				} else {
					this._logger.info('subscribed', topic);
				}

				return this.subscriptions[index].subscribed;
			}
		}

		return false;
	}

	public unsubscribe(topic: string, handler: SubscribeCallback): boolean {
		this._logger.event('unsubscribe', topic);

		for (let i = 0, len = this.subscriptions.length; i < len; i++) {
			if (this.subscriptions[i].topic === topic) {
				const callLen = this.subscriptions[i].callbacks.length;

				for (let j = 0; j < callLen; j++) {
					if (this.subscriptions[i].callbacks[j] === handler) {
						this.subscriptions[i].callbacks.splice(i, 1);

						break;
					}
				}

				if (this.subscriptions[i].callbacks.length === 0 && this.isConnected.value) {
					return this.send([MessageCode.MSG_UNSUBSCRIBE, topic]);
				}
			}
		}

		return true;
	}

	public isSubscribed(topic: string): boolean {
		return this.subscriptions.findIndex((subscription): boolean => subscription.topic === topic) !== -1;
	}

	public publish(topic: string, event: string, exclude?: string[] | null, eligible?: string[] | null): boolean {
		this._logger.event('publish', topic, event, exclude, eligible);

		return this.send([MessageCode.MSG_PUBLISH, event, exclude, eligible]);
	}

	public call<T>(topic: string, ...data: any): Promise<RpCallResponse<T>> {
		this._logger.event('call', topic);

		const callId = Math.random().toString(36).substring(2);

		return new Promise((resolve, reject) => {
			const result = this.send([MessageCode.MSG_CALL, callId, topic].concat(data));

			if (result) {
				this.rpcCalls.push({
					id: callId,
					resolve,
					reject,
				});
			} else {
				reject(new Error('RPC not processed'));
			}
		});
	}

	public onOpenEvent(listener: OnOpenCallback): void {
		this.onOpenEvents.push(listener);
	}

	public onCloseEvent(listener: OnCloseCallback): void {
		this.onCloseEvents.push(listener);
	}

	public onConnectEvent(listener: OnConnectCallback): void {
		this.onConnectEvents.push(listener);
	}

	public onDisconnectEvent(listener: OnDisconnectCallback): void {
		this.onDisconnectEvents.push(listener);
	}

	public offOpenEvent(listener: OnOpenCallback): void {
		for (let i = 0, len = this.onOpenEvents.length; i < len; i++) {
			if (this.onOpenEvents[i] === listener) {
				this.onOpenEvents.splice(i, 1);

				break;
			}
		}
	}

	public offCloseEvent(listener: OnCloseCallback): void {
		for (let i = 0, len = this.onCloseEvents.length; i < len; i++) {
			if (this.onCloseEvents[i] === listener) {
				this.onCloseEvents.splice(i, 1);

				break;
			}
		}
	}

	public offConnectEvent(listener: OnConnectCallback): void {
		for (let i = 0, len = this.onConnectEvents.length; i < len; i++) {
			if (this.onConnectEvents[i] === listener) {
				this.onConnectEvents.splice(i, 1);

				break;
			}
		}
	}

	public offDisconnectEvent(listener: OnDisconnectCallback): void {
		for (let i = 0, len = this.onDisconnectEvents.length; i < len; i++) {
			if (this.onDisconnectEvents[i] === listener) {
				this.onDisconnectEvents.splice(i, 1);

				break;
			}
		}
	}

	/**
	 * Send data via websockets
	 *
	 * @param {any[]} message
	 *
	 * @return boolean
	 *
	 * @private
	 */
	private send(message: any[]): boolean {
		if (this.socket === null) {
			this._logger.error('not.connected');

			return false;
		} else if (this.isConnecting.value) {
			this._logger.error('connecting');

			return false;
		} else if (!this.isConnected.value) {
			this._logger.error('lost');

			return false;
		} else {
			try {
				this.socket.send(JSON.stringify(message));

				return true;
			} catch {
				this._logger.error('send.error');

				return false;
			}
		}
	}

	/**
	 * Handle RPC result
	 *
	 * @param {any[]} message
	 * @param {number} code
	 *
	 * @private
	 */
	private rpcCallResult(message: any[], code: number): void {
		const rpcCall = this.rpcCalls.find(({ id }): boolean => id === message[0]);

		if (typeof rpcCall === 'undefined') {
			return;
		}

		const index = this.rpcCalls.findIndex(({ id }): boolean => id === message[0]);

		if (index !== -1) {
			this.rpcCalls.splice(index, 1);
		} else {
			this.rpcCalls = [];
		}

		if (code === MessageCode.MSG_CALL_ERROR) {
			const error = new RpcCallError(message[1], message[2]);

			if (message.length === 4) {
				error.details = message[3];
			}

			if (Object.prototype.hasOwnProperty.call(rpcCall, 'reject') && typeof rpcCall.reject !== 'undefined') {
				rpcCall.reject(error);
			}
		} else if (
			code === MessageCode.MSG_CALL_RESULT &&
			Object.prototype.hasOwnProperty.call(rpcCall, 'resolve') &&
			typeof rpcCall.resolve !== 'undefined'
		) {
			rpcCall.resolve({
				data: message[1],
			});
		}
	}
}

export function createInstance(host: string, debug = false): IWampClient {
	return new Client(host, new Logger(debug));
}

const wampClient = createInstance('ws://localhost:8080');

export default wampClient;
