/**
 * IndexedDB via Dexie untuk draft order lokal (PRD §5.2, §11).
 *
 * Draft order disimpan lokal di IndexedDB, di-flush ke server saat online/berhasil.
 * Pembayaran tidak pernah dianggap final sampai dikonfirmasi server.
 */

import Dexie, { type Table } from 'dexie';
import type { DraftOrder } from '@/types';

class PoshoesDb extends Dexie {
    draftOrders!: Table<DraftOrder, number>;

    constructor() {
        super('poshoes');
        this.version(1).stores({
            // keyPath `id` auto; index branch/updated_at untuk penyortiran
            draftOrders: '++id, updatedAt',
        });
    }
}

export const db = new PoshoesDb();

export async function saveDraft(order: Partial<DraftOrder>): Promise<number> {
    const record: DraftOrder = {
        id: order.id as number | undefined,
        customerName: order.customerName ?? '',
        customerPhone: order.customerPhone ?? '',
        notes: order.notes ?? '',
        items: order.items ?? [],
        createdAt: order.createdAt ?? Date.now(),
        updatedAt: Date.now(),
        synced: false,
    };
    if (record.id) {
        await db.draftOrders.put(record);
        return record.id;
    }
    return db.draftOrders.add(record);
}

export async function getDraft(id: number): Promise<DraftOrder | undefined> {
    return db.draftOrders.get(id);
}

export async function listDrafts(): Promise<DraftOrder[]> {
    return db.draftOrders.orderBy('updatedAt').reverse().toArray();
}

export async function deleteDraft(id: number): Promise<void> {
    await db.draftOrders.delete(id);
}

export async function markSynced(id: number): Promise<void> {
    await db.draftOrders.update(id, { synced: true, syncedAt: Date.now() });
}

export async function clearAllDrafts(): Promise<void> {
    await db.draftOrders.clear();
}