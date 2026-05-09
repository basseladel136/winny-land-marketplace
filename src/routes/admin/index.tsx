import { createFileRoute } from "@tanstack/react-router";
import { Package, ShoppingCart, DollarSign, Tags, TrendingUp } from "lucide-react";
import { useStore } from "@/lib/store";
import {
  LineChart,
  Line,
  ResponsiveContainer,
  XAxis,
  YAxis,
  Tooltip,
  BarChart,
  Bar,
  CartesianGrid,
} from "recharts";

export const Route = createFileRoute("/admin/")({
  component: Dashboard,
});

function Dashboard() {
  const orders = useStore((s) => s.orders);
  const products = useStore((s) => s.products);
  const categories = useStore((s) => s.categories);

  const revenue = orders.reduce((n, o) => n + o.total, 0);

  const stats = [
    { label: "Revenue", value: `$${revenue.toFixed(2)}`, icon: DollarSign, accent: "from-pink to-pink-soft" },
    { label: "Orders", value: orders.length, icon: ShoppingCart, accent: "from-pink-soft to-pink" },
    { label: "Products", value: products.length, icon: Package, accent: "from-pink to-pink-soft" },
    { label: "Categories", value: categories.length, icon: Tags, accent: "from-pink-soft to-pink" },
  ];

  const byDay: Record<string, { date: string; revenue: number; orders: number }> = {};
  orders.forEach((o) => {
    const d = new Date(o.createdAt).toLocaleDateString();
    byDay[d] = byDay[d] || { date: d, revenue: 0, orders: 0 };
    byDay[d].revenue += o.total;
    byDay[d].orders += 1;
  });
  const chartData = Object.values(byDay).slice(-14);

  const productSales: Record<string, { name: string; sold: number }> = {};
  orders.forEach((o) =>
    o.items.forEach((it) => {
      productSales[it.productId] = productSales[it.productId] || { name: it.name, sold: 0 };
      productSales[it.productId].sold += it.quantity;
    }),
  );
  const topProducts = Object.values(productSales).sort((a, b) => b.sold - a.sold).slice(0, 5);

  return (
    <div className="mx-auto max-w-6xl">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-[10px] font-medium uppercase tracking-[0.32em] text-muted-foreground">
            Overview
          </p>
          <h1 className="mt-2 font-display text-5xl">Dashboard</h1>
          <p className="mt-1 text-muted-foreground">
            Welcome back. Here's what's happening today.
          </p>
        </div>
        <div className="hidden items-center gap-2 rounded-full border border-border bg-card px-4 py-2 text-xs text-muted-foreground sm:flex">
          <TrendingUp className="h-3.5 w-3.5 text-pink" />
          Live snapshot
        </div>
      </div>

      <div className="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        {stats.map((s) => (
          <div
            key={s.label}
            className="group relative overflow-hidden rounded-3xl border border-border bg-card p-5 transition-all duration-300 hover:-translate-y-0.5 hover:border-pink hover:shadow-[0_12px_40px_-18px_color-mix(in_oklab,var(--glow)_55%,transparent)]"
          >
            <div
              className={`absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br ${s.accent} opacity-20 blur-2xl transition-opacity duration-300 group-hover:opacity-40`}
            />
            <div className="relative flex items-center justify-between">
              <span className="text-[10px] font-medium uppercase tracking-[0.22em] text-muted-foreground">
                {s.label}
              </span>
              <s.icon className="h-4 w-4 text-pink" />
            </div>
            <div className="relative mt-4 font-display text-3xl">{s.value}</div>
          </div>
        ))}
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-2">
        <div className="rounded-3xl border border-border bg-card p-6 transition-all duration-300 hover:border-pink/60">
          <div className="flex items-center justify-between">
            <h2 className="font-display text-2xl">Revenue</h2>
            <span className="text-xs text-muted-foreground">Last 14 days</span>
          </div>
          {chartData.length === 0 ? (
            <div className="flex h-64 items-center justify-center text-sm text-muted-foreground">
              No orders yet
            </div>
          ) : (
            <div className="mt-4 h-64">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={chartData}>
                  <CartesianGrid stroke="var(--border)" strokeDasharray="3 3" />
                  <XAxis dataKey="date" stroke="var(--muted-foreground)" fontSize={11} />
                  <YAxis stroke="var(--muted-foreground)" fontSize={11} />
                  <Tooltip
                    contentStyle={{
                      background: "var(--card)",
                      border: "1px solid var(--border)",
                      borderRadius: 12,
                      color: "var(--foreground)",
                    }}
                  />
                  <Line
                    type="monotone"
                    dataKey="revenue"
                    stroke="var(--pink)"
                    strokeWidth={2.5}
                    dot={{ r: 3, fill: "var(--pink)" }}
                  />
                </LineChart>
              </ResponsiveContainer>
            </div>
          )}
        </div>

        <div className="rounded-3xl border border-border bg-card p-6 transition-all duration-300 hover:border-pink/60">
          <div className="flex items-center justify-between">
            <h2 className="font-display text-2xl">Top products</h2>
            <span className="text-xs text-muted-foreground">By units sold</span>
          </div>
          {topProducts.length === 0 ? (
            <div className="flex h-64 items-center justify-center text-sm text-muted-foreground">
              No sales yet
            </div>
          ) : (
            <div className="mt-4 h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={topProducts}>
                  <CartesianGrid stroke="var(--border)" strokeDasharray="3 3" />
                  <XAxis dataKey="name" stroke="var(--muted-foreground)" fontSize={10} />
                  <YAxis stroke="var(--muted-foreground)" fontSize={11} />
                  <Tooltip
                    contentStyle={{
                      background: "var(--card)",
                      border: "1px solid var(--border)",
                      borderRadius: 12,
                      color: "var(--foreground)",
                    }}
                  />
                  <Bar dataKey="sold" fill="var(--pink)" radius={[10, 10, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          )}
        </div>
      </div>

      <div className="mt-8 rounded-3xl border border-border bg-card p-6 transition-all duration-300 hover:border-pink/60">
        <div className="flex items-center justify-between">
          <h2 className="font-display text-2xl">Recent orders</h2>
          <span className="text-xs text-muted-foreground">{orders.length} total</span>
        </div>
        {orders.length === 0 ? (
          <p className="mt-4 text-sm text-muted-foreground">No orders yet.</p>
        ) : (
          <div className="mt-4 divide-y divide-border">
            {orders.slice(0, 5).map((o) => (
              <div
                key={o.id}
                className="flex items-center justify-between gap-4 py-3 text-sm transition hover:bg-secondary/40 hover:px-2 rounded-lg"
              >
                <div>
                  <div className="font-medium">
                    #{o.id} · {o.customer.name}
                  </div>
                  <div className="text-xs text-muted-foreground">
                    {new Date(o.createdAt).toLocaleString()}
                  </div>
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
