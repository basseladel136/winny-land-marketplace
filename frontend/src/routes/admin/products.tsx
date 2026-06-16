import { createFileRoute } from "@tanstack/react-router";
import { useState, useRef } from "react";
import { Plus, Pencil, Trash2, X, Upload, FileSpreadsheet, CheckCircle2, AlertCircle, Loader2 } from "lucide-react";
import { useStore, type Product } from "@/lib/store";
import { BASE_URL } from "@/lib/api";
import { formatPrice } from "@/lib/utils";

export const Route = createFileRoute("/admin/products")({
  component: AdminProducts,
});

const empty = { name: "", price: 0, description: "", image: "", categoryId: "" };

// Columns the uploaded .xlsx must contain (mirrors the backend importer).
const REQUIRED_COLUMNS = ["name", "category", "description", "image", "price"] as const;

// ---------------------------------------------------------------------------
// Types for import summary
// ---------------------------------------------------------------------------
interface ImportFailedRow {
  row: number;
  reasons: string[];
}
interface ImportResult {
  inserted: number;
  failed: ImportFailedRow[];
  message: string;
}

// ---------------------------------------------------------------------------
// Excel Import Panel
// ---------------------------------------------------------------------------
function ImportPanel() {
  const fileRef = useRef<HTMLInputElement>(null);
  const [file, setFile]       = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [result, setResult]   = useState<ImportResult | null>(null);
  const [error, setError]     = useState<string | null>(null);

  const onFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const f = e.target.files?.[0] ?? null;
    setFile(f);
    setResult(null);
    setError(null);
  };

  const handleImport = async () => {
    if (!file) return;
    setLoading(true);
    setResult(null);
    setError(null);

    try {
      const token = (() => {
        try {
          const raw = localStorage.getItem("winny-auth");
          if (!raw) return null;
          return (JSON.parse(raw) as { state?: { token?: string } })?.state?.token ?? null;
        } catch { return null; }
      })();

      const formData = new FormData();
      formData.append("file", file);

      const res = await fetch(
        `${BASE_URL}/admin/products/import`,
        {
          method: "POST",
          headers: {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            Accept: "application/json",
          },
          body: formData,
        }
      );

      const data = await res.json();
      if (!res.ok) throw new Error(data.message ?? "Import failed");
      setResult(data as ImportResult);
    } catch (err: unknown) {
      setError((err as Error).message ?? "An unexpected error occurred.");
    } finally {
      setLoading(false);
    }
  };

  const reset = () => {
    setFile(null);
    setResult(null);
    setError(null);
    if (fileRef.current) fileRef.current.value = "";
  };

  return (
    <div className="mt-10 rounded-3xl border border-border bg-card p-6 transition-all duration-300 hover:border-pink/60">
      <div className="flex items-center gap-3">
        <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-pink/10">
          <FileSpreadsheet className="h-5 w-5 text-pink" />
        </div>
        <div>
          <h2 className="font-display text-xl">Import Products</h2>
          <p className="text-xs text-muted-foreground">
            Upload an Excel (.xlsx) file. The first row must be a header row.
          </p>
        </div>
      </div>

      {/* Required columns notice */}
      <div className="mt-4 rounded-2xl border border-pink/30 bg-pink/5 px-4 py-3">
        <p className="text-sm font-medium">The sheet must contain these columns:</p>
        <div className="mt-2 flex flex-wrap gap-2">
          {REQUIRED_COLUMNS.map((col) => (
            <span
              key={col}
              className="inline-flex items-center gap-1 rounded-full border border-pink/40 bg-background px-3 py-1 font-mono text-xs"
            >
              <span className="text-pink">*</span>
              {col}
            </span>
          ))}
        </div>
        <p className="mt-2 text-xs text-muted-foreground">
          Optional: <span className="font-mono">stock</span> (defaults to 0). New categories are created
          automatically if they don't already exist.
        </p>
      </div>

      <div className="mt-5 flex flex-wrap items-center gap-3">
        {/* Hidden file input */}
        <input
          ref={fileRef}
          type="file"
          accept=".xlsx,.xls"
          className="hidden"
          onChange={onFileChange}
          id="xlsx-upload"
        />
        <label
          htmlFor="xlsx-upload"
          className="inline-flex cursor-pointer items-center gap-2 rounded-full border border-border bg-secondary/40 px-4 py-2 text-sm transition hover:border-pink hover:bg-secondary/80"
        >
          <Upload className="h-4 w-4" />
          {file ? file.name : "Choose .xlsx file"}
        </label>

        {file && !result && (
          <button
            onClick={handleImport}
            disabled={loading}
            className="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2 text-sm font-medium text-primary-foreground transition hover:bg-pink hover:text-primary disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin" /> Importing…
              </>
            ) : (
              <>
                <FileSpreadsheet className="h-4 w-4" /> Run Import
              </>
            )}
          </button>
        )}

        {result && (
          <button onClick={reset} className="text-xs text-muted-foreground underline-offset-4 hover:underline">
            Import another file
          </button>
        )}
      </div>

      {/* Error */}
      {error && (
        <div className="mt-4 flex items-start gap-2 rounded-2xl border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
          <span>{error}</span>
        </div>
      )}

      {/* Success summary */}
      {result && (
        <div className="mt-4 space-y-3">
          <div className="flex items-center gap-2 rounded-2xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-700 dark:text-green-400">
            <CheckCircle2 className="h-4 w-4 shrink-0" />
            <span>{result.message}</span>
          </div>

          {result.failed.length > 0 && (
            <div className="overflow-hidden rounded-2xl border border-border">
              <div className="bg-secondary/60 px-4 py-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                Skipped rows ({result.failed.length})
              </div>
              <div className="divide-y divide-border">
                {result.failed.map((f, i) => (
                  <div key={i} className="flex items-start gap-3 px-4 py-3 text-sm">
                    <span className="shrink-0 font-medium text-muted-foreground">Row {f.row}</span>
                    <ul className="list-disc pl-4 text-destructive">
                      {f.reasons.map((r, j) => <li key={j}>{r}</li>)}
                    </ul>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------
function AdminProducts() {
  const products = useStore((s) => s.products);
  const categories = useStore((s) => s.categories);
  const addProduct = useStore((s) => s.addProduct);
  const updateProduct = useStore((s) => s.updateProduct);
  const deleteProduct = useStore((s) => s.deleteProduct);

  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Product | null>(null);
  const [form, setForm] = useState<typeof empty>(empty);

  const openNew = () => { setEditing(null); setForm({ ...empty, categoryId: categories[0]?.id ?? "" }); setOpen(true); };
  const openEdit = (p: Product) => { setEditing(p); setForm({ name: p.name, price: p.price, description: p.description, image: p.image, categoryId: p.categoryId }); setOpen(true); };

  const onImage = (e: React.ChangeEvent<HTMLInputElement>) => {
    const f = e.target.files?.[0];
    if (!f) return;
    const r = new FileReader();
    r.onload = () => setForm((s) => ({ ...s, image: r.result as string }));
    r.readAsDataURL(f);
  };

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editing) updateProduct(editing.id, form);
    else addProduct(form);
    setOpen(false);
  };

  return (
    <div className="mx-auto max-w-6xl">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display text-4xl">Products</h1>
          <p className="text-muted-foreground">{products.length} products</p>
        </div>
        <button onClick={openNew} className="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-pink hover:text-primary">
          <Plus className="h-4 w-4" /> New product
        </button>
      </div>

      <div className="mt-8 overflow-hidden rounded-2xl border border-border bg-card">
        <table className="w-full text-sm">
          <thead className="bg-secondary/60 text-left text-xs uppercase tracking-wider text-muted-foreground">
            <tr>
              <th className="p-4">Product</th>
              <th className="p-4 hidden md:table-cell">Category</th>
              <th className="p-4">Price</th>
              <th className="p-4"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {products.map((p) => {
              const cat = categories.find((c) => c.id === p.categoryId);
              return (
                <tr key={p.id} className="hover:bg-secondary/30">
                  <td className="p-4">
                    <div className="flex items-center gap-3">
                      <img src={p.image} alt={p.name} className="h-12 w-12 rounded-lg object-cover" />
                      <span className="font-medium">{p.name}</span>
                    </div>
                  </td>
                  <td className="p-4 hidden md:table-cell text-muted-foreground">{cat?.name ?? "—"}</td>
                  <td className="p-4 font-semibold">{formatPrice(p.price)}</td>
                  <td className="p-4">
                    <div className="flex justify-end gap-1">
                      <button onClick={() => openEdit(p)} className="rounded-lg p-2 hover-pink"><Pencil className="h-4 w-4" /></button>
                      <button onClick={() => deleteProduct(p.id)} className="rounded-lg p-2 hover:bg-destructive/10 hover:text-destructive"><Trash2 className="h-4 w-4" /></button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {/* ── Excel Import Panel ─────────────────────────────────────────────── */}
      <ImportPanel />

      {open && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-primary/20 p-4 backdrop-blur-sm" onClick={() => setOpen(false)}>
          <form onClick={(e) => e.stopPropagation()} onSubmit={submit} className="w-full max-w-lg rounded-2xl border border-border bg-card p-6 shadow-2xl">
            <div className="flex items-center justify-between">
              <h2 className="font-display text-2xl">{editing ? "Edit product" : "New product"}</h2>
              <button type="button" onClick={() => setOpen(false)} className="rounded-full p-1 hover-pink"><X className="h-4 w-4" /></button>
            </div>
            <div className="mt-4 space-y-3">
              <input required placeholder="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="w-full rounded-xl border border-border bg-background px-4 py-2.5 outline-none focus:border-pink" />
              <textarea placeholder="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="w-full rounded-xl border border-border bg-background px-4 py-2.5 outline-none focus:border-pink" rows={3} />
              <div className="grid grid-cols-2 gap-3">
                <input required type="number" min="0" step="0.01" placeholder="Price" value={form.price || ""} onChange={(e) => setForm({ ...form, price: parseFloat(e.target.value) || 0 })} className="rounded-xl border border-border bg-background px-4 py-2.5 outline-none focus:border-pink" />
                <select required value={form.categoryId} onChange={(e) => setForm({ ...form, categoryId: e.target.value })} className="rounded-xl border border-border bg-background px-4 py-2.5 outline-none focus:border-pink">
                  <option value="">Category</option>
                  {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
              </div>
              <div>
                <label className="mb-2 block text-xs font-medium uppercase tracking-wider text-muted-foreground">Image</label>
                <input type="file" accept="image/*" onChange={onImage} className="text-sm" />
                <input placeholder="Or paste image URL" value={form.image} onChange={(e) => setForm({ ...form, image: e.target.value })} className="mt-2 w-full rounded-xl border border-border bg-background px-4 py-2.5 text-sm outline-none focus:border-pink" />
                {form.image && <img src={form.image} alt="" className="mt-2 h-24 w-24 rounded-lg object-cover" />}
              </div>
            </div>
            <button type="submit" className="mt-6 w-full rounded-full bg-primary py-3 text-sm font-medium text-primary-foreground hover:bg-pink hover:text-primary">
              {editing ? "Save changes" : "Create product"}
            </button>
          </form>
        </div>
      )}
    </div>
  );
}
