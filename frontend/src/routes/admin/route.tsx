import { createFileRoute, Link, Outlet, redirect } from "@tanstack/react-router";
import {
  LayoutDashboard,
  Package,
  Tags,
  ShoppingCart,
  ArrowLeft,
  Users,
  Settings,
  BarChart3,
  Ticket,
} from "lucide-react";
import { ThemeToggle } from "@/components/ThemeToggle";
import { useAuthStore } from "@/lib/authStore";

export const Route = createFileRoute("/admin")({
  // ── Auth guard: runs before the component mounts ─────────────────────────
  // Redirects to /login if not authenticated, or to /marketplace if authenticated
  // but not an admin. This prevents any leakage of the admin panel.
  beforeLoad: () => {
    const state = useAuthStore.getState();

    if (!state.isAuthenticated || !state.token) {
      throw redirect({ to: "/login" });
    }

    if (!state.isAdmin()) {
      // Authenticated but not admin — send to marketplace silently
      throw redirect({ to: "/marketplace" });
    }
  },
  head: () => ({
    // SECURITY: No "Admin" in the page title to avoid exposing admin status
    meta: [{ title: "Dashboard — Winny Land" }],
  }),
  component: DashboardLayout,
});

const links: {
  to:
    | "/admin"
    | "/admin/products"
    | "/admin/categories"
    | "/admin/orders"
    | "/admin/users"
    | "/admin/settings"
    | "/admin/analytics"
    | "/admin/coupons";
  label: string;
  icon: typeof LayoutDashboard;
  exact?: boolean;
}[] = [
  { to: "/admin",            label: "Overview",    icon: LayoutDashboard, exact: true },
  { to: "/admin/products",   label: "Products",    icon: Package },
  { to: "/admin/categories", label: "Categories",  icon: Tags },
  { to: "/admin/orders",     label: "Orders",      icon: ShoppingCart },
  { to: "/admin/users",      label: "Users",       icon: Users },
  { to: "/admin/coupons",    label: "Coupons",     icon: Ticket },
  { to: "/admin/analytics",  label: "Analytics",   icon: BarChart3 },
  { to: "/admin/settings",   label: "Settings",    icon: Settings },
];

function DashboardLayout() {
  return (
    <div className="flex min-h-screen bg-background">
      {/* Desktop sidebar */}
      <aside className="sticky top-0 hidden h-screen w-72 shrink-0 flex-col border-r border-sidebar-border bg-sidebar p-7 lg:flex">
        <Link to="/" className="font-display text-2xl tracking-tight">
          <span className="text-pink">Winny</span>
          <span className="opacity-90">Land</span>
        </Link>
        {/* "Admin Suite" removed — no admin labels in UI */}
        <p className="mt-1 text-[10px] font-medium uppercase tracking-[0.32em] text-muted-foreground">
          Management
        </p>

        <nav className="mt-10 flex flex-col gap-1.5">
          {links.map((l) => (
            <Link
              key={l.to}
              to={l.to}
              activeOptions={{ exact: l.exact ?? false }}
              activeProps={{
                className:
                  "bg-pink text-primary shadow-[0_8px_30px_-12px_color-mix(in_oklab,var(--glow)_60%,transparent)]",
              }}
              inactiveProps={{
                className:
                  "text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground",
              }}
              className="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200"
            >
              <l.icon className="h-4 w-4 transition-transform duration-200 group-hover:scale-110" />
              {l.label}
            </Link>
          ))}
        </nav>

        <div className="mt-auto space-y-3">
          <div className="flex items-center justify-between rounded-2xl border border-sidebar-border bg-card/40 px-4 py-3">
            <span className="text-xs font-medium text-muted-foreground">Theme</span>
            <ThemeToggle />
          </div>
          <Link
            to="/marketplace"
            className="inline-flex items-center gap-2 text-xs text-muted-foreground transition hover:text-foreground"
          >
            <ArrowLeft className="h-3.5 w-3.5" /> Back to store
          </Link>
        </div>
      </aside>

      {/* Mobile top bar */}
      <div className="fixed inset-x-0 top-0 z-30 flex items-center gap-1 overflow-x-auto border-b border-sidebar-border bg-sidebar/95 px-4 py-3 backdrop-blur lg:hidden">
        <Link to="/" className="mr-2 font-display text-lg">
          <span className="text-pink">Winny</span>Land
        </Link>
        {links.map((l) => (
          <Link
            key={l.to}
            to={l.to}
            activeOptions={{ exact: l.exact ?? false }}
            activeProps={{ className: "bg-pink text-primary" }}
            className="shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition hover:bg-sidebar-accent"
          >
            {l.label}
          </Link>
        ))}
        <div className="ml-1 shrink-0">
          <ThemeToggle />
        </div>
      </div>

      <main className="flex-1 px-6 py-8 pt-24 lg:px-10 lg:pt-10">
        <Outlet />
      </main>
    </div>
  );
}
