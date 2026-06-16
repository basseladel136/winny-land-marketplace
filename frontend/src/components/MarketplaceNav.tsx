import { Link } from "@tanstack/react-router";
import { Heart, ShoppingBag, Search, LayoutDashboard, LogIn, User } from "lucide-react";

function NavStar({ size = 10 }: { size?: number }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="currentColor"
      className="text-pink"
      style={{
        filter:
          "drop-shadow(0 0 5px color-mix(in oklab, var(--glow) 90%, transparent))",
      }}
    >
      <path d="M12 2l2.9 6.9L22 10l-5.5 4.8L18 22l-6-3.6L6 22l1.5-7.2L2 10l7.1-1.1z" />
    </svg>
  );
}
import { motion } from "framer-motion";
import { useStore } from "@/lib/store";
import { useAuthStore } from "@/lib/authStore";
import { ThemeToggle } from "@/components/ThemeToggle";
import { VerificationBanner } from "@/components/VerificationBanner";

export function MarketplaceNav({
  query,
  onQuery,
}: {
  query?: string;
  onQuery?: (s: string) => void;
}) {
  const cartCount = useStore((s) => s.cart.reduce((n, c) => n + c.quantity, 0));
  const wishCount = useStore((s) => s.wishlist.length);

  // Admin access: show a subtle icon (no "Admin" text) — only for admin users
  const { isAdmin, isAuthenticated } = useAuthStore();
  const showDashboard = isAuthenticated && isAdmin();

  return (
    <div className="sticky top-0 z-40">
      <VerificationBanner />
    <header className="border-b border-border bg-background/85 backdrop-blur-xl">
      <div className="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4 sm:px-6">
        <Link to="/marketplace" className="relative font-display text-2xl tracking-tight">
          {/* 3 small elegant glowing sparkles behind the logo */}
          <span aria-hidden className="pointer-events-none absolute inset-0 -z-10">
            {[
              { top: "-10px", left: "-14px", size: 12, delay: 0 },
              { top: "-6px", left: "42%", size: 9, delay: 0.25 },
              { top: "8px", left: "92%", size: 11, delay: 0.5 },
            ].map((s, i) => (
              <motion.span
                key={i}
                initial={{ opacity: 0, scale: 0.5 }}
                animate={{ opacity: 0.85, scale: 1 }}
                transition={{ delay: s.delay, duration: 0.7, ease: [0.16, 1, 0.3, 1] }}
                className="absolute"
                style={{ top: s.top, left: s.left }}
              >
                <span
                  className="orbit inline-block"
                  style={{ animationDelay: `${s.delay}s`, animationDuration: `${7 + i}s` }}
                >
                  <NavStar size={s.size} />
                </span>
              </motion.span>
            ))}
          </span>
          <span className="text-pink">Winny</span>
          <span className="text-foreground"> Land</span>
        </Link>

        {onQuery && (
          <div className="relative ml-4 hidden flex-1 md:block">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input
              value={query ?? ""}
              onChange={(e) => onQuery(e.target.value)}
              placeholder="Search perfumes, plush toys, stationery..."
              className="w-full rounded-full border border-border bg-secondary/50 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-pink focus:bg-background"
            />
          </div>
        )}

        <nav className="ml-auto flex items-center gap-1">
          <ThemeToggle />

          {/* Wishlist */}
          <Link to="/marketplace/wishlist" className="hover-pink relative rounded-full p-2.5">
            <Heart className="h-5 w-5" />
            {wishCount > 0 && (
              <span className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-pink px-1 text-[10px] font-bold text-primary">
                {wishCount}
              </span>
            )}
          </Link>

          {/* Cart */}
          <Link to="/marketplace/cart" className="hover-pink relative rounded-full p-2.5">
            <ShoppingBag className="h-5 w-5" />
            {cartCount > 0 && (
              <span className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-pink px-1 text-[10px] font-bold text-primary">
                {cartCount}
              </span>
            )}
          </Link>

          {/* Sign In — only shown when the user is not authenticated */}
          {!isAuthenticated && (
            <Link
              to="/login"
              className="ml-1 inline-flex items-center gap-1.5 rounded-full border border-border px-4 py-2 text-xs font-medium transition hover:border-pink hover:text-pink"
              aria-label="Sign in"
            >
              <LogIn className="h-3.5 w-3.5" />
              Sign In
            </Link>
          )}

          {/* Profile — only shown when the user is authenticated */}
          {isAuthenticated && (
            <Link
              to="/marketplace/profile"
              className="hover-pink ml-1 rounded-full p-2.5"
              title="Profile"
              aria-label="My profile"
            >
              <User className="h-5 w-5" />
            </Link>
          )}

          {/* Dashboard icon — only visible to admin users, no "Admin" label */}
          {showDashboard && (
            <Link
              to="/admin"
              className="hover-pink ml-1 rounded-full p-2.5"
              title="Dashboard"
              aria-label="Management dashboard"
            >
              <LayoutDashboard className="h-5 w-5" />
            </Link>
          )}
        </nav>
      </div>

      {onQuery && (
        <div className="px-4 pb-3 md:hidden">
          <div className="relative">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input
              value={query ?? ""}
              onChange={(e) => onQuery(e.target.value)}
              placeholder="Search..."
              className="w-full rounded-full border border-border bg-secondary/50 py-2 pl-10 pr-4 text-sm outline-none focus:border-pink"
            />
          </div>
        </div>
      )}
    </header>
    </div>
  );
}
