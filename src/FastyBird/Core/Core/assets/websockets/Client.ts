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
	ReconnectGuard,
	RpCallResponse,
	SubscribeCallback,
} from './types';

// Delay before the first attempt to re-establish a lost connection; it doubles with every
// further attempt, up to the maximum
export const RECONNECT_BASE_DELAY = 1000;
export const RECONNECT_MAX_DELAY = 60000;

// A connection that stayed open at least this long counts as established, so the delay before
// re-establishing it starts from the base again. One closed sooner keeps the delay growing,
// which is what keeps a server that accepts and then drops the client from being hit in a loop.
export const STABLE_CONNECTION_PERIOD = 10000;

export class Client implements IWampClient {
	private _host: string;
	private _logger: IWampLogger;
	private _autoReestablish = true;

	private socket: WebSocket | null;
	private sessionId: string | null = null;

	// Whether the application wants the connection open: set by open(), cleared by close()
	private wanted = false;
	private reconnectGuard: ReconnectGuard | null = null;
	private reconnectTimer: ReturnType<typeof setTimeout> | null = null;
	private reconnectAttempts = 0;
	// Bumped whenever a pending re-establishment is cancelled, so an attempt which was waiting
	// for its timer or its guard knows it has been superseded
	private reconnectGeneration = 0;
	private connectedAt: number | null = null;

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

	set autoReestablish(autoReestablish: boolean) {
		this._autoReestablish = autoReestablish;
	}

	public setReconnectGuard(guard: ReconnectGuard | null): void {
		this.reconnectGuard = guard;
	}

	public open(): void {
		if (this.isConnected.value || this.isConnecting.value) {
			return;
		}

		this.wanted = true;

		this.cancelReconnect();

		this.connect();
	}

	public reconnect(): void {
		if (!this.wanted) {
			return;
		}

		this._logger.event('reconnect');

		// An explicit reconnect is no evidence the server accepts the client again, so the delay
		// before a following attempt keeps growing -- unless the connection it replaces had been
		// established, which is as good as one closing by itself after the same time
		const attempts = this.isStable() ? 0 : this.reconnectAttempts;

		this.close(1000, 'Reconnecting');

		this.reconnectAttempts = attempts;
		this.wanted = true;
		this.isLost.value = true;

		try {
			this.connect();
		} catch {
			this.scheduleReconnect();
		}
	}

	public close(reason?: number, message?: string): void {
		this.wanted = false;
		this.reconnectAttempts = 0;

		this.cancelReconnect();

		if (this.socket !== null) {
			const socket = this.socket;

			// Cleared first, so the listeners know this socket is no longer the client's
			this.socket = null;

			socket.close(reason, message);
		}

		this.resetConnection();

		this.isLost.value = false;
	}

	/**
	 * Opens a new socket. Its listeners act only while it is the client's current socket, so a
	 * socket closed by close() or replaced by reconnect() can no longer change the client's state.
	 *
	 * @private
	 */
	private connect(): void {
		let socket: WebSocket;

		try {
			// Open connection to WS server
			socket = new WebSocket(this._host);
		} catch {
			throw new Error('Connection could not be established');
		}

		this.socket = socket;

		this.isConnecting.value = true;

		// Connection established with WS server
		socket.addEventListener('open', () => {
			if (socket !== this.socket) {
				return;
			}

			this.onOpenEvents.forEach((eventCallback): void => {
				eventCallback();
			});
		});

		// Connection closed by WS server, or lost
		socket.addEventListener('close', (event) => {
			if (socket !== this.socket) {
				// Closed by close(), or replaced by another socket meanwhile
				return;
			}

			this.socket = null;

			const wasStable = this.isStable();

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

			this.resetConnection();

			if (wasStable) {
				this.reconnectAttempts = 0;
			}

			if (this.wanted && this._autoReestablish) {
				this.isLost.value = true;

				this.scheduleReconnect();
			} else {
				this.wanted = false;
				this.isLost.value = false;
			}
		});

		socket.addEventListener('message', (event): void => {
			if (socket !== this.socket) {
				return;
			}

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

					this.connectedAt = Date.now();

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

		const index = this.subscriptions.findIndex((subscription): boolean => subscription.topic === topic);

		if (index === -1) {
			return true;
		}

		const subscription = this.subscriptions[index];
		const callbackIndex = subscription.callbacks.indexOf(handler);

		if (callbackIndex !== -1) {
			subscription.callbacks.splice(callbackIndex, 1);
		}

		if (subscription.callbacks.length > 0) {
			return true;
		}

		// No handler left: the topic is forgotten, so the next connection does not subscribe it again
		this.subscriptions.splice(index, 1);

		if (this.isConnected.value) {
			return this.send([MessageCode.MSG_UNSUBSCRIBE, topic]);
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
	 * Waits, then re-establishes the lost connection. The wait doubles with every attempt up to
	 * RECONNECT_MAX_DELAY, and a random part of it (at most half) spreads the clients of a
	 * restarted server apart; it is never shorter than half of RECONNECT_BASE_DELAY.
	 *
	 * @private
	 */
	private scheduleReconnect(): void {
		const ceiling = Math.min(RECONNECT_MAX_DELAY, RECONNECT_BASE_DELAY * 2 ** Math.min(this.reconnectAttempts, 16));
		const delay = Math.round(ceiling / 2 + Math.random() * (ceiling / 2));

		this.reconnectAttempts++;

		const generation = this.reconnectGeneration;

		this._logger.info('reconnect scheduled', this.reconnectAttempts, delay);

		this.reconnectTimer = setTimeout((): void => {
			void this.attemptReconnect(generation);
		}, delay);
	}

	/**
	 * Asks the guard whether the connection may be re-established (it may refresh the session the
	 * connection signs in with), then connects. A guard answering false stops re-establishing it,
	 * one failing is asked again after the next wait.
	 *
	 * @private
	 */
	private async attemptReconnect(generation: number): Promise<void> {
		this.reconnectTimer = null;

		if (generation !== this.reconnectGeneration || !this.wanted) {
			return;
		}

		if (this.reconnectGuard !== null) {
			let proceed: boolean;

			try {
				proceed = await this.reconnectGuard();
			} catch (e: unknown) {
				if (generation === this.reconnectGeneration && this.wanted) {
					this._logger.warn('reconnect guard failed', e);

					this.scheduleReconnect();
				}

				return;
			}

			// Opened, closed or re-established by the application while the guard was asked
			if (generation !== this.reconnectGeneration || !this.wanted) {
				return;
			}

			if (!proceed) {
				this._logger.info('reconnect refused by guard');

				this.wanted = false;
				this.isLost.value = false;

				return;
			}
		}

		try {
			this.connect();
		} catch {
			this.scheduleReconnect();
		}
	}

	/**
	 * Whether the current connection has been open for at least STABLE_CONNECTION_PERIOD
	 *
	 * @private
	 */
	private isStable(): boolean {
		return this.isConnected.value && this.connectedAt !== null && Date.now() - this.connectedAt >= STABLE_CONNECTION_PERIOD;
	}

	/**
	 * Cancels a pending re-establishment, including one already waiting for its guard
	 *
	 * @private
	 */
	private cancelReconnect(): void {
		this.reconnectGeneration++;

		if (this.reconnectTimer !== null) {
			clearTimeout(this.reconnectTimer);

			this.reconnectTimer = null;
		}
	}

	/**
	 * Forgets the state of the connection which has just ended: its topics have to be subscribed
	 * again on the next one, and its pending RPC calls can no longer be answered
	 *
	 * @private
	 */
	private resetConnection(): void {
		this.subscriptions.forEach((subscription, index) => {
			if (subscription.subscribed) {
				this.subscriptions[index].subscribed = false;
			}
		});

		const rpcCalls = this.rpcCalls;

		this.rpcCalls = [];

		rpcCalls.forEach((rpcCall): void => {
			if (typeof rpcCall.reject === 'function') {
				rpcCall.reject(new Error('Connection closed before the RPC call was answered'));
			}
		});

		this.sessionId = null;
		this.connectedAt = null;

		this.isConnecting.value = false;
		this.isConnected.value = false;
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
