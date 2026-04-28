import { createFileRoute, Link, Outlet } from "@tanstack/react-router";
import { LayoutDashboard, Package, Tags, ShoppingCart, ArrowLeft } from "lucide-react";

export const Route = createFileRoute("/admin")({
  head: () => ({ meta: [{ title: "Admin — Winny Land" }] }),
  component: AdminLayout,
});

const links = [
  { to: "/admin", label: "Dashboard", icon: LayoutDashboard, exact: true },
  { to: "/admin/products", label: "Products", icon: Package },
  { to: "/admin/categories", label: "Categories", icon: Tags },
  { to: "/admin/orders", label: "Orders", icon: ShoppingCart },
] as const;

function AdminLayout() {
  return (
    <div className="flex min-h-screen bg-background">
      <aside className="sticky top-0 hidden h-screen w-64 shrink-0 flex-col border-r border-border bg-sidebar p-6 lg:flex">
        <Link to="/" className="font-display text-2xl">
          Winny<span className="text-pink">.</span>Land
        </Link>
        <p className="mt-1 text-xs uppercase tracking-[0.2em] text-muted-foreground">Admin</p>

        <nav className="mt-10 flex flex-col gap-1">
          {links.map((l) => (
            <Link
              key={l.to}
              to={l.to}
              activeOptions={{ exact: l.exact ?? false }}
              activeProps={{ className: "bg-pink text-primary" }}
              inactiveProps={{ className: "hover:bg-sidebar-accent" }}
              className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
            >
              <l.icon className="h-4 w-4" /> {l.label}
            </Link>
          ))}
        </nav>

        <Link to="/marketplace" className="mt-auto inline-flex items-center gap-2 text-xs text-muted-foreground hover:text-foreground">
          <ArrowLeft className="h-3.5 w-3.5" /> Back to store
        </Link>
      </aside>

      {/* Mobile top bar */}
      <div className="fixed inset-x-0 top-0 z-30 flex items-center gap-1 overflow-x-auto border-b border-border bg-sidebar px-4 py-3 lg:hidden">
        <Link to="/" className="font-display text-lg mr-2">Winny.Land</Link>
        {links.map((l) => (
          <Link
            key={l.to}
            to={l.to}
            activeOptions={{ exact: l.exact ?? false }}
            activeProps={{ className: "bg-pink text-primary" }}
            className="shrink-0 rounded-full px-3 py-1.5 text-xs font-medium"
          >
            {l.label}
          </Link>
        ))}
      </div>

      <main className="flex-1 px-6 py-8 pt-20 lg:pt-8">
        <Outlet />
      </main>
    </div>
  );
}
