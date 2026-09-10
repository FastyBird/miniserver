import { DBSchema, IDBPDatabase, openDB } from 'idb';

import { IDashboardDatabaseRecord } from '../models/dashboards/types';
import { IGroupDatabaseRecord } from '../models/groups/types';
import { ITabDatabaseRecord } from '../models/tabs/types';
import { IWidgetDataSourceDatabaseRecord } from '../models/widgets-data-sources/types';
import { IWidgetDisplayDatabaseRecord } from '../models/widgets-display/types';
import { IWidgetDatabaseRecord } from '../models/widgets/types';

const DB_NAME = 'widgets_module';
const DB_VERSION = 1;

export const DB_TABLE_DASHBOARDS = 'dashboards';
export const DB_TABLE_TABS = 'tabs';
export const DB_TABLE_GROUPS = 'groups';
export const DB_TABLE_WIDGETS = 'widgets';
export const DB_TABLE_WIDGETS_DISPLAY = 'widgets_display';
export const DB_TABLE_WIDGETS_DATA_SOURCES = 'widgets_data_sources';

type StoreName =
	| typeof DB_TABLE_DASHBOARDS
	| typeof DB_TABLE_TABS
	| typeof DB_TABLE_GROUPS
	| typeof DB_TABLE_WIDGETS
	| typeof DB_TABLE_WIDGETS_DISPLAY
	| typeof DB_TABLE_WIDGETS_DATA_SOURCES;

interface StorageDbSchema extends DBSchema {
	[DB_TABLE_DASHBOARDS]: {
		key: string;
		value: IDashboardDatabaseRecord;
	};
	[DB_TABLE_TABS]: {
		key: string;
		value: ITabDatabaseRecord;
	};
	[DB_TABLE_GROUPS]: {
		key: string;
		value: IGroupDatabaseRecord;
	};
	[DB_TABLE_WIDGETS]: {
		key: string;
		value: IWidgetDatabaseRecord;
	};
	[DB_TABLE_WIDGETS_DISPLAY]: {
		key: string;
		value: IWidgetDisplayDatabaseRecord;
	};
	[DB_TABLE_WIDGETS_DATA_SOURCES]: {
		key: string;
		value: IWidgetDataSourceDatabaseRecord;
	};
}

type DatabaseRecord =
	| IDashboardDatabaseRecord
	| ITabDatabaseRecord
	| IGroupDatabaseRecord
	| IWidgetDatabaseRecord
	| IWidgetDisplayDatabaseRecord
	| IWidgetDataSourceDatabaseRecord;

export const initDB = async (): Promise<IDBPDatabase<StorageDbSchema>> => {
	return openDB(DB_NAME, DB_VERSION, {
		upgrade(db): void {
			// List all store names you expect
			const storeNames: StoreName[] = [
				DB_TABLE_DASHBOARDS,
				DB_TABLE_TABS,
				DB_TABLE_GROUPS,
				DB_TABLE_WIDGETS,
				DB_TABLE_WIDGETS_DISPLAY,
				DB_TABLE_WIDGETS_DATA_SOURCES,
			];

			// Create stores if they do not exist
			storeNames.forEach((storeName) => {
				if (!db.objectStoreNames.contains(storeName)) {
					db.createObjectStore(storeName, { keyPath: 'id' });
				}
			});
		},
	});
};

export const doesStoreExist = async (storeName: StoreName): Promise<boolean> => {
	const db = await initDB();

	return db.objectStoreNames.contains(storeName);
};

export const addRecord = async <T extends DatabaseRecord>(record: T, storeName: StoreName): Promise<void> => {
	const db = await initDB();
	const tx = db.transaction(storeName, 'readwrite');

	await tx.objectStore(storeName).put(record);

	await tx.done;
};

export const getRecord = async <T extends DatabaseRecord>(id: string, storeName: StoreName): Promise<T | undefined> => {
	const db = await initDB();

	return (await db.transaction(storeName).objectStore(storeName).get(id)) as T | undefined;
};

export const getAllRecords = async <T extends DatabaseRecord>(storeName: StoreName): Promise<T[]> => {
	const db = await initDB();

	return (await db.transaction(storeName).objectStore(storeName).getAll()) as T[];
};

export const removeRecord = async (id: string, storeName: StoreName): Promise<void> => {
	const db = await initDB();
	const tx = db.transaction(storeName, 'readwrite');

	await tx.objectStore(storeName).delete(id);

	await tx.done;
};
