import { createFileRoute } from "@tanstack/react-router";
import { useMemo } from "react";
import {
  AreaChart,
  Area,
  ResponsiveContainer,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  PieChart,
  Pie,
  Cell,
  Legend,
} from "recharts";
import { useStore } from "@/lib/store";

export const Route = createFileRoute("/admin/analytics")({
  component: Analytics,
});

const PALETTE = ["var(--pink)", "var(--gold)", "var(--accent)", "var(--pink-soft)", "var(--beige)"];

function Analytics() {
  const orders = useStore((s) => s.orders);
  const products = useStore((s) => s.products);
  const categories = useStore((s) => s.categories);

  const series = useMemo(() => {
    const byDay: Record<string, { date: string; revenue: number; orders: number }> = {};
    orders.forEach((o) => {
      const d = new Date(o.createdAt).toLocaleDateString();
      byDay[d] = byDay[d] || { date: d, revenue: 0, orders: 0 };
      byDay[d].revenue += o.total;
      byDay[d].orders += 1;
    });
    return Object.values(byDay).slice(-30);
  }, [orders]);

  const byCategory = useMemo(() => {
    const map: Record<string, number> = {};
    orders.forEach((o) =>
      o.items.forEach((it) => {
        const p = products.find((p) => p.id === it.productId);
        const cat = categories.find((c) => c.id === p?.categoryId)?.name ?? "Other";
        map[cat] = (map[cat] || 0) + it.price * it.quantity;
      }),
    );
    return Object.entries(map).map(([name, value]) => ({ name, value }));
  }, [orders, products, categories]);

  const aov = orders.length ? orders.reduce((n, o) => n + o.total, 0) / orders.length : 0;

  return (
    <div className="mx-auto max-w-6xl">
      <p className="text-[10px] font-medium uppercase tracking-[0.32em] text-muted-foreground">
        Insights
      </p>
      <h1 className="mt-2 font-display text-5xl">Analytics</h1>
      <p className="mt-1 text-muted-foreground">Trends across your boutique.</p>

      <div className="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        {[
          { label: "Total orders", value: orders.length },
          { label: "Avg. order", value: `$${aov.toFixed(2)}` },
          { label: "Products", value: products.length },
          { label: "Categories", value: categories.length },
        ].map((s) => (
          <div
            key={s.label}
            className="rounded-3xl border border-border bg-card p-5 transition hover:border-pink/60"
          >
            <div className="text-[10px] font-medium uppercase tracking-[0.22em] text-muted-foreground">
              {s.label}
            </div>
            <div className="mt-3 font-display text-3xl">{s.value}</div>
          </div>
        ))}
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-3">
        <div className="rounded-3xl border border-border bg-card p-6 lg:col-span-2">
          <h2 className="font-display text-2xl">Revenue trend</h2>
          {series.length === 0 ? (
            <div className="flex h-72 items-center justify-center text-sm text-muted-foreground">
              No data yet
            </div>
          ) : (
            <div className="mt-4 h-72">
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={series}>
                  <defs>
                    <linearGradient id="rev" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor="var(--pink)" stopOpacity={0.6} />
                      <stop offset="100%" stopColor="var(--pink)" stopOpacity={0} />
                    </linearGradient>
                  </defs>
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
                  <Area
                    type="monotone"
                    dataKey="revenue"
                    stroke="var(--pink)"
                    strokeWidth={2.5}
                    fill="url(#rev)"
                  />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          )}
        </div>

        <div className="rounded-3xl border border-border bg-card p-6">
          <h2 className="font-display text-2xl">By category</h2>
          {byCategory.length === 0 ? (
            <div className="flex h-72 items-center justify-center text-sm text-muted-foreground">
              No data yet
            </div>
          ) : (
            <div className="mt-4 h-72">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={byCategory}
                    dataKey="value"
                    nameKey="name"
                    innerRadius={50}
                    outerRadius={85}
                    paddingAngle={3}
                  >
                    {byCategory.map((_, i) => (
                      <Cell key={i} fill={PALETTE[i % PALETTE.length]} stroke="var(--card)" />
                    ))}
                  </Pie>
                  <Tooltip
                    contentStyle={{
                      background: "var(--card)",
                      border: "1px solid var(--border)",
                      borderRadius: 12,
                      color: "var(--foreground)",
                    }}
                  />
                  <Legend wrapperStyle={{ fontSize: 11 }} />
                </PieChart>
              </ResponsiveContainer>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
