import { StateTree, Store } from 'pinia';

import { IStoresManager, StoreInjectionKey } from './types';

export class StoresManager implements IStoresManager {
	private stores: Map<StoreInjectionKey<string, any, any, any>, Store<string, any, any, any>> = new Map();

	public addStore<Id extends string = string, S extends StateTree = object, G = object, A = object>(
		key: StoreInjectionKey<Id, S, G, A>,
		store: Store<Id, S, G, A>
	): void {
		this.stores.set(key, store);
	}

	public getStore<Id extends string = string, S extends StateTree = object, G = object, A = object>(
		key: StoreInjectionKey<Id, S, G, A>
	): Store<Id, S, G, A> {
		if (!this.stores.has(key)) {
			throw new Error('Something went wrong, store is not registered');
		}

		return this.stores.get(key) as Store<Id, S, G, A>;
	}
}
