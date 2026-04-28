import { createFileRoute } from "@tanstack/react-router";
import { useStore, type Order } from "@/lib/store";

export const Route = createFileRoute("/admin/orders")({
  component: AdminOrders,
});

const statuses: Order["status"][] = ["pending", "processing", "shipped", "delivered", "cancelled"];

function AdminOrders() {
  const orders = useStore((s) => s.orders);
  const updateStatus = useStore((s) => s.updateOrderStatus);

  return (
    <div className="mx-auto max-w-6xl">
      <h1 className="font-display text-4xl">Orders</h1>
      <p className="text-muted-foreground">{orders.length} orders</p>

      {orders.length === 0 ? (
        <div className="mt-12 rounded-2xl border border-dashed border-border py-20 text-center text-muted-foreground">No orders yet.</div>
      ) : (
        <div className="mt-8 space-y-4">
          {orders.map((o) => (
            <div key={o.id} className="rounded-2xl border border-border bg-card p-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <div className="font-display text-xl">Order #{o.id}</div>
                  <div className="text-xs text-muted-foreground">{new Date(o.createdAt).toLocaleString()}</div>
                  <div className="mt-2 text-sm">{o.customer.name} · {o.customer.email}</div>
                  <div className="text-xs text-muted-foreground">{o.customer.address}</div>
                </div>
                <div className="text-right">
                  <div className="font-display text-2xl">${o.total.toFixed(2)}</div>
                  <select
                    value={o.status}
                    onChange={(e) => updateStatus(o.id, e.target.value as Order["status"])}
                    className="mt-2 rounded-full border border-border bg-background px-3 py-1.5 text-xs capitalize outline-none focus:border-pink"
                  >
                    {statuses.map((s) => <option key={s} value={s}>{s}</option>)}
                  </select>
                </div>
              </div>
              <div className="mt-4 border-t border-border pt-3 text-sm">
                {o.items.map((it) => (
                  <div key={it.productId} className="flex justify-between py-1">
                    <span className="text-muted-foreground">{it.name} × {it.quantity}</span>
                    <span>${(it.price * it.quantity).toFixed(2)}</span>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
