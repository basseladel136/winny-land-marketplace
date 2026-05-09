import { createFileRoute } from "@tanstack/react-router";
import { useMemo } from "react";
import { Mail, ShoppingBag } from "lucide-react";
import { useStore } from "@/lib/store";

export const Route = createFileRoute("/admin/users")({
  component: AdminUsers,
});

function AdminUsers() {
  const orders = useStore((s) => s.orders);

  const users = useMemo(() => {
    const map = new Map<
      string,
      { name: string; email: string; orders: number; spent: number; last: string }
    >();
    orders.forEach((o) => {
      const key = o.customer.email.toLowerCase();
      const existing = map.get(key);
      if (existing) {
        existing.orders += 1;
        existing.spent += o.total;
        if (new Date(o.createdAt) > new Date(existing.last)) existing.last = o.createdAt;
      } else {
        map.set(key, {
          name: o.customer.name,
          email: o.customer.email,
          orders: 1,
          spent: o.total,
          last: o.createdAt,
        });
      }
    });
    return Array.from(map.values()).sort((a, b) => b.spent - a.spent);
  }, [orders]);

  return (
    <div className="mx-auto max-w-6xl">
      <p className="text-[10px] font-medium uppercase tracking-[0.32em] text-muted-foreground">
        Customers
      </p>
      <h1 className="mt-2 font-display text-5xl">Users</h1>
      <p className="mt-1 text-muted-foreground">{users.length} customers</p>

      {users.length === 0 ? (
        <div className="mt-12 rounded-3xl border border-dashed border-border py-20 text-center text-muted-foreground">
          No customers yet.
        </div>
      ) : (
        <div className="mt-8 grid gap-4 md:grid-cols-2">
          {users.map((u) => (
            <div
              key={u.email}
              className="group rounded-3xl border border-border bg-card p-5 transition-all duration-300 hover:-translate-y-0.5 hover:border-pink hover:shadow-[0_12px_40px_-18px_color-mix(in_oklab,var(--glow)_55%,transparent)]"
            >
              <div className="flex items-center gap-4">
                <div className="grid h-12 w-12 place-items-center rounded-full bg-pink-soft font-display text-lg text-primary">
                  {u.name.charAt(0).toUpperCase()}
                </div>
                <div className="min-w-0 flex-1">
                  <div className="font-display text-xl truncate">{u.name}</div>
                  <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <Mail className="h-3 w-3" /> <span className="truncate">{u.email}</span>
                  </div>
                </div>
              </div>
              <div className="mt-4 flex items-center justify-between border-t border-border pt-3 text-sm">
                <div className="flex items-center gap-1 text-muted-foreground">
                  <ShoppingBag className="h-3.5 w-3.5" /> {u.orders} orders
                </div>
                <div className="font-semibold">${u.spent.toFixed(2)}</div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
