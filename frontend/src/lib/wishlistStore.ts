/**
 * Wishlist Store
 *
 * Single source of truth for the user's wishlist. Persisted to localStorage
 * so the count survives page refreshes. When the user is authenticated,
 * every toggle also calls the backend so the server-side count stays accurate.
 *
 * Products in the marketplace are seed items with string IDs ("p1" … "p6").
 * Real backend products use numeric IDs. Both are stored as strings here.
 * Backend calls are only made for IDs that are valid positive integers.
 */

import { create } from "zustand";
import { persist } from "zustand/middleware";
import { wishlistApi, type ApiProduct } from "./api";

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function toInt(id: string): number | null {
  const n = parseInt(id, 10);
  return Number.isFinite(n) && n > 0 && String(n) === id ? n : null;
}

function getAuthToken(): string | null {
  try {
    const raw = localStorage.getItem("winny-auth");
    if (!raw) return null;
    const parsed = JSON.parse(raw) as { state?: { token?: string } };
    return parsed?.state?.token ?? null;
  } catch {
    return null;
  }
}

// ---------------------------------------------------------------------------
// State shape
// ---------------------------------------------------------------------------

interface WishlistState {
  /** All wishlisted product IDs stored as strings for compat with seed IDs */
  ids: string[];
  /** Full ApiProduct objects fetched from backend (for WishlistTab display) */
  apiItems: ApiProduct[];

  // Computed
  count: number;
  isIn: (id: string) => boolean;

  // Actions
  toggle: (productId: string) => Promise<void>;
  /** Fetch backend wishlist and merge into local state */
  hydrate: () => Promise<void>;
  /** Remove all items — called on logout */
  clear: () => void;
  /** Set local ids without a backend call — used after successful hydration */
  setIds: (ids: string[]) => void;
}

// ---------------------------------------------------------------------------
// Store
// ---------------------------------------------------------------------------

export const useWishlistStore = create<WishlistState>()(
  persist(
    (set, get) => ({
      ids: [],
      apiItems: [],
      count: 0,

      isIn: (id: string) => get().ids.includes(id),

      toggle: async (productId: string) => {
        const numericId = toInt(productId);

        // Seed/demo products have non-numeric IDs ("p1"…). They can never be
        // stored in the backend, so they must not affect the persisted count
        // (which must always equal the backend's row count for this user).
        if (numericId === null) return;

        const ids = get().ids;
        const alreadyIn = ids.includes(productId);

        // Optimistic update
        const next = alreadyIn
          ? ids.filter((x) => x !== productId)
          : [...ids, productId];
        set({ ids: next, count: next.length });

        if (getAuthToken()) {
          try {
            await wishlistApi.toggle(numericId);
          } catch {
            // Rollback on failure
            set({ ids, count: ids.length });
          }
        }
      },

      hydrate: async () => {
        if (!getAuthToken()) return;
        try {
          const res = await wishlistApi.list();
          const products = res.data;
          const newIds = products.map((p) => String(p.id));
          set({
            apiItems: products,
            ids: newIds,
            count: newIds.length,
          });
        } catch {
          // Keep current local state if backend unreachable
        }
      },

      clear: () => set({ ids: [], apiItems: [], count: 0 }),

      setIds: (ids: string[]) => set({ ids, count: ids.length }),
    }),
    {
      name: "winny-wishlist",
      // Only persist ids and count; apiItems are re-fetched on hydrate
      partialize: (state) => ({ ids: state.ids, count: state.count }),
      // Recompute count from ids after rehydration
      onRehydrateStorage: () => (state) => {
        if (state) {
          state.count = state.ids.length;
        }
      },
    }
  )
);
