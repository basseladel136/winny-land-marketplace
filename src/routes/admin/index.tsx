import { createFileRoute } from "@tanstack/react-router";
import { Package, ShoppingCart, DollarSign, Tags } from "lucide-react";
import { useStore } from "@/lib/store";
import { LineChart, Line, ResponsiveContainer, XAxis, YAxis, Tooltip, BarChart, Bar, CartesianGrid } from "recharts";

export const Route = createFileRoute("/admin/")({
  component: Dashboard,
});

function Dashboard() {
  const orders = useStore((s) => s.orders);
  const products = useStore((s) => s.products);
  const categories = useStore((s) => s.categories);

  const revenue = orders.reduce((n, o) => n + o.total, 0);

  const stats = [
    { label: "Revenue", value: `$${revenue.toFixed(2)}`, icon: DollarSign },
    { label: "Orders", value: orders.length, icon: ShoppingCart },
    { label: "Products", value: products.length, icon: Package },
    { label: "Categories", value: categories.length, icon: Tags },
  ];

  // build chart data per day
  const byDay: Record<string, { date: string; revenue: number; orders: number }> = {};
  orders.forEach((o) => {
    const d = new Date(o.createdAt).toLocaleDateString();
    byDay[d] = byDay[d] || { date: d, revenue: 0, orders: 0 };
    byDay[d].revenue += o.total;
    byDay[d].orders += 1;
  });
  const chartData = Object.values(byDay).slice(-14);

  // top products
  const productSales: Record<string, { name: string; sold: number }> = {};
  orders.forEach((o) => o.items.forEach((it) => {
    productSales[it.productId] = productSales[it.productId] || { name: it.name, sold: 0 };
    productSales[it.productId].sold += it.quantity;
  }));
  const topProducts = Object.values(productSales).sort((a, b) => b.sold - a.sold).slice(0, 5);

  return (
    <div className="mx-auto max-w-6xl">
      <h1 className="font-display text-4xl">Dashboard</h1>
      <p className="text-muted-foreground">Welcome back. Here's what's happening today.</p>

      <div className="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        {stats.map((s) => (
          <div key={s.label} className="rounded-2xl border border-border bg-card p-5">
            <div className="flex items-center justify-between">
              <span className="text-xs font-medium uppercase tracking-wider text-muted-foreground">{s.label}</span>
              <s.icon className="h-4 w-4 text-muted-foreground" />
            </div>
            <div className="mt-3 font-display text-3xl">{s.value}</div>
          </div>
        ))}
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-2">
        <div className="rounded-2xl border border-border bg-card p-6">
          <h2 className="font-display text-2xl">Revenue</h2>
          {chartData.length === 0 ? (
            <div className="flex h-64 items-center justify-center text-sm text-muted-foreground">No orders yet</div>
          ) : (
            <div className="h-64 mt-4">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={chartData}>
                  <CartesianGrid stroke="oklch(0.9 0.015 75)" strokeDasharray="3 3" />
                  <XAxis dataKey="date" stroke="oklch(0.45 0.02 40)" fontSize={11} />
                  <YAxis stroke="oklch(0.45 0.02 40)" fontSize={11} />
                  <Tooltip contentStyle={{ background: "oklch(0.99 0.004 80)", border: "1px solid oklch(0.9 0.015 75)", borderRadius: 12 }} />
                  <Line type="monotone" dataKey="revenue" stroke="oklch(0.85 0.09 15)" strokeWidth={2.5} dot={{ r: 3 }} />
                </LineChart>
              </ResponsiveContainer>
            </div>
          )}
        </div>

        <div className="rounded-2xl border border-border bg-card p-6">
          <h2 className="font-display text-2xl">Top products</h2>
          {topProducts.length === 0 ? (
            <div className="flex h-64 items-center justify-center text-sm text-muted-foreground">No sales yet</div>
          ) : (
            <div className="h-64 mt-4">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={topProducts}>
                  <CartesianGrid stroke="oklch(0.9 0.015 75)" strokeDasharray="3 3" />
                  <XAxis dataKey="name" stroke="oklch(0.45 0.02 40)" fontSize={10} />
                  <YAxis stroke="oklch(0.45 0.02 40)" fontSize={11} />
                  <Tooltip contentStyle={{ background: "oklch(0.99 0.004 80)", border: "1px solid oklch(0.9 0.015 75)", borderRadius: 12 }} />
                  <Bar dataKey="sold" fill="oklch(0.85 0.09 15)" radius={[8, 8, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          )}
        </div>
      </div>

      <div className="mt-8 rounded-2xl border border-border bg-card p-6">
        <h2 className="font-display text-2xl">Recent orders</h2>
        {orders.length === 0 ? (
          <p className="mt-4 text-sm text-muted-foreground">No orders yet.</p>
        ) : (
          <div className="mt-4 divide-y divide-border">
            {orders.slice(0, 5).map((o) => (
              <div key={o.id} className="flex items-center justify-between py-3 text-sm">
                <div>
                  <div className="font-medium">#{o.id} · {o.customer.name}</div>
                  <div className="text-xs text-muted-foreground">{new Date(o.createdAt).toLocaleString()}</div>
                </div>
                <div className="text-right">
                  <div className="font-semibold">${o.total.toFixed(2)}</div>
                  <div className="text-xs capitalize text-muted-foreground">{o.status}</div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
