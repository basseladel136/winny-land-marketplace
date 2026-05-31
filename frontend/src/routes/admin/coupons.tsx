import { createFileRoute } from "@tanstack/react-router";
import { useState, useEffect, useCallback } from "react";
import {
  Plus, Pencil, Trash2, X, Loader2, ToggleLeft, ToggleRight,
  Ticket, AlertCircle, CheckCircle2,
} from "lucide-react";
import { api } from "@/lib/api";

export const Route = createFileRoute("/admin/coupons")({
  component: AdminCoupons,
});

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------
interface ApiCoupon {
  id: number;
  code: string;
  type: "percent" | "fixed";
  value: number;
  minOrderAmount: number | null;
  maxUses: number | null;
  usesCount: number;
  isActive: boolean;
  expiresAt: string | null;
  createdAt: string;
}

interface CouponForm {
  code: string;
  type: "percent" | "fixed";
  value: string;
  minOrderAmount: string;
  maxUses: string;
  isActive: boolean;
  expiresAt: string;
}

const emptyForm: CouponForm = {
  code: "",
  type: "percent",
  value: "",
  minOrderAmount: "",
  maxUses: "",
  isActive: true,
  expiresAt: "",
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function fmtDate(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString();
}

function fmtDiscount(c: ApiCoupon) {
  if (c.type === "percent") return `${c.value}%`;
  return `$${c.value.toFixed(2)}`;
}

// ---------------------------------------------------------------------------
// Coupon Form Modal
// ---------------------------------------------------------------------------
function CouponModal({
  editing,
  onClose,
  onSaved,
}: {
  editing: ApiCoupon | null;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [form, setForm] = useState<CouponForm>(
    editing
      ? {
          code:           editing.code,
          type:           editing.type,
          value:          String(editing.value),
          minOrderAmount: editing.minOrderAmount != null ? String(editing.minOrderAmount) : "",
          maxUses:        editing.maxUses != null ? String(editing.maxUses) : "",
          isActive:       editing.isActive,
          expiresAt:      editing.expiresAt ? editing.expiresAt.slice(0, 10) : "",
        }
      : emptyForm
  );
  const [saving, setSaving]   = useState(false);
  const [error, setError]     = useState<string | null>(null);

  const set = <K extends keyof CouponForm>(k: K, v: CouponForm[K]) =>
    setForm((f) => ({ ...f, [k]: v }));

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);

    const payload: Record<string, unknown> = {
      type:    form.type,
      value:   parseFloat(form.value) || 0,
      isActive: form.isActive,
      ...(form.minOrderAmount ? { minOrderAmount: parseFloat(form.minOrderAmount) } : {}),
      ...(form.maxUses        ? { maxUses: parseInt(form.maxUses, 10) }             : {}),
      ...(form.expiresAt      ? { expiresAt: form.expiresAt }                       : {}),
    };

    try {
      if (editing) {
        await api.put(`/admin/coupons/${editing.id}`, payload);
      } else {
        await api.post("/admin/coupons", { ...payload, code: form.code.toUpperCase() });
      }
      onSaved();
    } catch (err: unknown) {
      const e = err as { errors?: Record<string, string[]>; message?: string };
      const first = e.errors ? Object.values(e.errors).flat()[0] : undefined;
      setError(first ?? e.message ?? "Failed to save coupon.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-primary/20 p-4 backdrop-blur-sm"
      onClick={onClose}
    >
      <form
        onClick={(e) => e.stopPropagation()}
        onSubmit={submit}
        className="w-full max-w-lg rounded-2xl border border-border bg-card p-6 shadow-2xl"
      >
        <div className="flex items-center justify-between">
          <h2 className="font-display text-2xl">{editing ? "Edit Coupon" : "New Coupon"}</h2>
          <button type="button" onClick={onClose} className="rounded-full p-1 hover-pink">
            <X className="h-4 w-4" />
          </button>
        </div>

        {error && (
          <div className="mt-3 flex items-start gap-2 rounded-xl border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
            <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
            {error}
          </div>
        )}

        <div className="mt-4 space-y-3">
          {/* Code */}
          {!editing && (
            <input
              required
              placeholder="Coupon code (e.g. SUMMER20)"
              value={form.code}
              onChange={(e) => set("code", e.target.value.toUpperCase())}
              className="w-full rounded-xl border border-border bg-background px-4 py-2.5 font-mono uppercase outline-none focus:border-pink"
            />
          )}

          {/* Type + Value */}
          <div className="grid grid-cols-2 gap-3">
            <select
              value={form.type}
              onChange={(e) => set("type", e.target.value as "percent" | "fixed")}
              className="rounded-xl border border-border bg-background px-4 py-2.5 outline-none focus:border-pink"
            >
              <option value="percent">Percentage (%)</option>
              <option value="fixed">Fixed amount ($)</option>
            </select>
            <input
              required
              type="number"
              min="0"
              step="0.01"
              placeholder={form.type === "percent" ? "Discount %" : "Discount $"}
              value={form.value}
              onChange={(e) => set("value", e.target.value)}
              className="rounded-xl border border-border bg-background px-4 py-2.5 outline-none focus:border-pink"
            />
          </div>

          {/* Min order + Max uses */}
          <div className="grid grid-cols-2 gap-3">
            <input
              type="number"
              min="0"
              step="0.01"
              placeholder="Min order amount ($)"
              value={form.minOrderAmount}
              onChange={(e) => set("minOrderAmount", e.target.value)}
              className="rounded-xl border border-border bg-background px-4 py-2.5 outline-none focus:border-pink"
            />
            <input
              type="number"
              min="1"
              step="1"
              placeholder="Usage limit (optional)"
              value={form.maxUses}
              onChange={(e) => set("maxUses", e.target.value)}
              className="rounded-xl border border-border bg-background px-4 py-2.5 outline-none focus:border-pink"
            />
          </div>

          {/* Expiry date */}
          <input
            type="date"
            placeholder="Expiry date (optional)"
            value={form.expiresAt}
            onChange={(e) => set("expiresAt", e.target.value)}
            className="w-full rounded-xl border border-border bg-background px-4 py-2.5 outline-none focus:border-pink"
          />

          {/* Active toggle */}
          <label className="flex cursor-pointer items-center gap-3 rounded-xl border border-border bg-background px-4 py-2.5">
            <span className="flex-1 text-sm">Active</span>
            <button
              type="button"
              onClick={() => set("isActive", !form.isActive)}
              className={`transition ${form.isActive ? "text-pink" : "text-muted-foreground"}`}
              aria-label="Toggle active"
            >
              {form.isActive ? (
                <ToggleRight className="h-6 w-6" />
              ) : (
                <ToggleLeft className="h-6 w-6" />
              )}
            </button>
          </label>
        </div>

        <button
          type="submit"
          disabled={saving}
          className="mt-6 flex w-full items-center justify-center gap-2 rounded-full bg-primary py-3 text-sm font-medium text-primary-foreground hover:bg-pink hover:text-primary disabled:cursor-not-allowed disabled:opacity-60"
        >
          {saving ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" /> Saving…
            </>
          ) : editing ? (
            "Save changes"
          ) : (
            "Create coupon"
          )}
        </button>
      </form>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------
function AdminCoupons() {
  const [coupons, setCoupons] = useState<ApiCoupon[]>([]);
  const [loading, setLoading] = useState(true);
  const [fetchError, setFetchError] = useState<string | null>(null);
  const [modalOpen, setModalOpen]   = useState(false);
  const [editing, setEditing]       = useState<ApiCoupon | null>(null);
  const [toast, setToast]           = useState<string | null>(null);
  const [deleting, setDeleting]     = useState<number | null>(null);

  // ── Fetch coupons ──────────────────────────────────────────────────────────
  const loadCoupons = useCallback(async () => {
    setLoading(true);
    setFetchError(null);
    try {
      const res = await api.get<{ data: ApiCoupon[] }>("/admin/coupons?perPage=100");
      setCoupons(res.data);
    } catch (err: unknown) {
      setFetchError((err as Error).message ?? "Failed to load coupons.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { loadCoupons(); }, [loadCoupons]);

  // ── Toast helper ───────────────────────────────────────────────────────────
  const showToast = (msg: string) => {
    setToast(msg);
    setTimeout(() => setToast(null), 3000);
  };

  // ── CRUD handlers ──────────────────────────────────────────────────────────
  const openNew  = () => { setEditing(null); setModalOpen(true); };
  const openEdit = (c: ApiCoupon) => { setEditing(c); setModalOpen(true); };

  const onSaved = () => {
    setModalOpen(false);
    showToast(editing ? "Coupon updated." : "Coupon created.");
    loadCoupons();
  };

  const toggleActive = async (c: ApiCoupon) => {
    try {
      await api.put(`/admin/coupons/${c.id}`, { isActive: !c.isActive });
      setCoupons((prev) =>
        prev.map((x) => (x.id === c.id ? { ...x, isActive: !x.isActive } : x))
      );
      showToast(`Coupon ${!c.isActive ? "enabled" : "disabled"}.`);
    } catch {
      showToast("Failed to update status.");
    }
  };

  const deleteCoupon = async (id: number) => {
    if (!confirm("Delete this coupon?")) return;
    setDeleting(id);
    try {
      await api.delete(`/admin/coupons/${id}`);
      setCoupons((prev) => prev.filter((c) => c.id !== id));
      showToast("Coupon deleted.");
    } catch {
      showToast("Failed to delete coupon.");
    } finally {
      setDeleting(null);
    }
  };

  // ── Render ─────────────────────────────────────────────────────────────────
  return (
    <div className="mx-auto max-w-6xl">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display text-4xl">Coupons</h1>
          <p className="text-muted-foreground">{coupons.length} coupon{coupons.length !== 1 ? "s" : ""}</p>
        </div>
        <button
          onClick={openNew}
          className="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-pink hover:text-primary"
        >
          <Plus className="h-4 w-4" /> New coupon
        </button>
      </div>

      {/* Loading */}
      {loading && (
        <div className="mt-12 flex justify-center">
          <Loader2 className="h-8 w-8 animate-spin text-pink" />
        </div>
      )}

      {/* Error */}
      {fetchError && (
        <div className="mt-6 flex items-center gap-2 rounded-2xl border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          <AlertCircle className="h-4 w-4 shrink-0" /> {fetchError}
        </div>
      )}

      {/* Empty state */}
      {!loading && !fetchError && coupons.length === 0 && (
        <div className="mt-12 flex flex-col items-center gap-3 text-center text-muted-foreground">
          <Ticket className="h-12 w-12 opacity-30" />
          <p className="text-sm">No coupons yet. Create your first one!</p>
        </div>
      )}

      {/* Table */}
      {!loading && coupons.length > 0 && (
        <div className="mt-8 overflow-hidden rounded-2xl border border-border bg-card">
          <table className="w-full text-sm">
            <thead className="bg-secondary/60 text-left text-xs uppercase tracking-wider text-muted-foreground">
              <tr>
                <th className="p-4">Code</th>
                <th className="p-4">Discount</th>
                <th className="p-4 hidden md:table-cell">Expiry</th>
                <th className="p-4 hidden lg:table-cell">Usage</th>
                <th className="p-4">Status</th>
                <th className="p-4"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {coupons.map((c) => (
                <tr key={c.id} className="hover:bg-secondary/30">
                  <td className="p-4">
                    <span className="rounded-lg bg-secondary/60 px-2.5 py-1 font-mono text-xs font-semibold tracking-wide">
                      {c.code}
                    </span>
                  </td>
                  <td className="p-4 font-semibold text-pink">{fmtDiscount(c)}</td>
                  <td className="p-4 hidden md:table-cell text-muted-foreground">{fmtDate(c.expiresAt)}</td>
                  <td className="p-4 hidden lg:table-cell text-muted-foreground">
                    {c.usesCount}{c.maxUses ? ` / ${c.maxUses}` : ""}
                  </td>
                  <td className="p-4">
                    <button
                      onClick={() => toggleActive(c)}
                      title={c.isActive ? "Disable" : "Enable"}
                      className="transition"
                    >
                      {c.isActive ? (
                        <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                          <ToggleRight className="h-5 w-5" />
                          <span className="text-xs">On</span>
                        </span>
                      ) : (
                        <span className="flex items-center gap-1 text-muted-foreground">
                          <ToggleLeft className="h-5 w-5" />
                          <span className="text-xs">Off</span>
                        </span>
                      )}
                    </button>
                  </td>
                  <td className="p-4">
                    <div className="flex justify-end gap-1">
                      <button
                        onClick={() => openEdit(c)}
                        className="rounded-lg p-2 hover-pink"
                        title="Edit"
                      >
                        <Pencil className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => deleteCoupon(c.id)}
                        disabled={deleting === c.id}
                        className="rounded-lg p-2 hover:bg-destructive/10 hover:text-destructive disabled:opacity-50"
                        title="Delete"
                      >
                        {deleting === c.id ? (
                          <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                          <Trash2 className="h-4 w-4" />
                        )}
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Modal */}
      {modalOpen && (
        <CouponModal
          editing={editing}
          onClose={() => setModalOpen(false)}
          onSaved={onSaved}
        />
      )}

      {/* Toast */}
      {toast && (
        <div className="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 flex items-center gap-2 rounded-full bg-card border border-border px-5 py-3 text-sm shadow-lg">
          <CheckCircle2 className="h-4 w-4 text-pink" />
          {toast}
        </div>
      )}
    </div>
  );
}
