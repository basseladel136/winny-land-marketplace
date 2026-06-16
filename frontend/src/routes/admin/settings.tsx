import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useRef, useState } from "react";
import { Save } from "lucide-react";

export const Route = createFileRoute("/admin/settings")({
  component: AdminSettings,
});

function AdminSettings() {
  const [form, setForm] = useState({
    storeName: "Winny Land",
    email: "hello@winnyland.shop",
    currency: "EGP",
    tagline: "Soft, dreamy, and forever charming.",
  });
  const [saved, setSaved] = useState(false);
  const savedTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => () => { if (savedTimerRef.current) clearTimeout(savedTimerRef.current); }, []);

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    setSaved(true);
    if (savedTimerRef.current) clearTimeout(savedTimerRef.current);
    savedTimerRef.current = setTimeout(() => setSaved(false), 1800);
  };

  return (
    <div className="mx-auto max-w-3xl">
      <p className="text-[10px] font-medium uppercase tracking-[0.32em] text-muted-foreground">
        Configuration
      </p>
      <h1 className="mt-2 font-display text-5xl">Settings</h1>
      <p className="mt-1 text-muted-foreground">Manage your boutique preferences.</p>

      <form
        onSubmit={submit}
        className="mt-8 space-y-6 rounded-3xl border border-border bg-card p-6 transition hover:border-pink/60"
      >
        <Field label="Store name">
          <input
            value={form.storeName}
            onChange={(e) => setForm({ ...form, storeName: e.target.value })}
            className="input"
          />
        </Field>
        <Field label="Contact email">
          <input
            type="email"
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
            className="input"
          />
        </Field>
        <Field label="Currency">
          <select
            value={form.currency}
            onChange={(e) => setForm({ ...form, currency: e.target.value })}
            className="input"
          >
            <option>EGP</option>
            <option>USD</option>
            <option>EUR</option>
            <option>GBP</option>
            <option>JPY</option>
          </select>
        </Field>
        <Field label="Tagline">
          <textarea
            rows={3}
            value={form.tagline}
            onChange={(e) => setForm({ ...form, tagline: e.target.value })}
            className="input"
          />
        </Field>

        <div className="flex items-center justify-between border-t border-border pt-5">
          <span className="text-xs text-muted-foreground">
            {saved ? "✓ Saved" : "Changes are stored locally."}
          </span>
          <button
            type="submit"
            className="neon-glow inline-flex items-center gap-2 rounded-full bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground hover:bg-pink hover:text-primary"
          >
            <Save className="h-4 w-4" /> Save changes
          </button>
        </div>
      </form>

      <style>{`
        .input {
          width: 100%;
          border-radius: 14px;
          border: 1px solid var(--border);
          background: var(--background);
          padding: 10px 16px;
          outline: none;
          transition: border-color 200ms ease, box-shadow 200ms ease;
        }
        .input:focus { border-color: var(--pink); box-shadow: 0 0 0 3px color-mix(in oklab, var(--glow) 30%, transparent); }
      `}</style>
    </div>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="block">
      <span className="mb-2 block text-[10px] font-medium uppercase tracking-[0.22em] text-muted-foreground">
        {label}
      </span>
      {children}
    </label>
  );
}
