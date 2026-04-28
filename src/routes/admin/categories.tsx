import { createFileRoute } from "@tanstack/react-router";
import { useState } from "react";
import { Plus, Pencil, Trash2, Check, X } from "lucide-react";
import { useStore } from "@/lib/store";

export const Route = createFileRoute("/admin/categories")({
  component: AdminCategories;
});

function AdminCategories() {
  const categories = useStore((s) => s.categories);
  const products = useStore((s) => s.products);
  const addCategory = useStore((s) => s.addCategory);
  const updateCategory = useStore((s) => s.updateCategory);
  const deleteCategory = useStore((s) => s.deleteCategory);

  const [name, setName] = useState("");
  const [editId, setEditId] = useState<string | null>(null);
  const [editName, setEditName] = useState("");

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) return;
    addCategory(name.trim());
    setName("");
  };

  return (
    <div className="mx-auto max-w-3xl">
      <h1 className="font-display text-4xl">Categories</h1>
      <p className="text-muted-foreground">Sync with marketplace and product form.</p>

      <form onSubmit={submit} className="mt-8 flex gap-2">
        <input value={name} onChange={(e) => setName(e.target.value)} placeholder="New category name" className="flex-1 rounded-xl border border-border bg-card px-4 py-2.5 outline-none focus:border-pink" />
        <button className="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-pink hover:text-primary">
          <Plus className="h-4 w-4" /> Add
        </button>
      </form>

      <div className="mt-6 overflow-hidden rounded-2xl border border-border bg-card">
        {categories.map((c) => {
          const count = products.filter((p) => p.categoryId === c.id).length;
          const editing = editId === c.id;
          return (
            <div key={c.id} className="flex items-center gap-3 border-b border-border p-4 last:border-0">
              {editing ? (
                <>
                  <input value={editName} onChange={(e) => setEditName(e.target.value)} className="flex-1 rounded-lg border border-border bg-background px-3 py-1.5 outline-none focus:border-pink" />
                  <button onClick={() => { updateCategory(c.id, editName); setEditId(null); }} className="rounded-lg p-2 hover-pink"><Check className="h-4 w-4" /></button>
                  <button onClick={() => setEditId(null)} className="rounded-lg p-2 hover-pink"><X className="h-4 w-4" /></button>
                </>
              ) : (
                <>
                  <div className="flex-1">
                    <div className="font-medium">{c.name}</div>
                    <div className="text-xs text-muted-foreground">{count} products · /{c.slug}</div>
                  </div>
                  <button onClick={() => { setEditId(c.id); setEditName(c.name); }} className="rounded-lg p-2 hover-pink"><Pencil className="h-4 w-4" /></button>
                  <button onClick={() => deleteCategory(c.id)} className="rounded-lg p-2 hover:bg-destructive/10 hover:text-destructive"><Trash2 className="h-4 w-4" /></button>
                </>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
