import { Link } from "@tanstack/react-router";
import { Heart, ShoppingBag, Search, Sparkles } from "lucide-react";
import { motion } from "framer-motion";
import { useStore } from "@/lib/store";
import { ThemeToggle } from "@/components/ThemeToggle";

export function MarketplaceNav({
  query,
  onQuery,
}: {
  query?: string;
  onQuery?: (s: string) => void;
}) {
  const cartCount = useStore((s) => s.cart.reduce((n, c) => n + c.quantity, 0));
  const wishCount = useStore((s) => s.wishlist.length);

  return (
    <header className="sticky top-0 z-40 border-b border-border bg-background/85 backdrop-blur-xl">
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
                <Sparkles
                  className="twinkle text-pink"
                  style={{
                    width: s.size,
                    height: s.size,
                    animationDelay: `${s.delay}s`,
                    filter:
                      "drop-shadow(0 0 6px color-mix(in oklab, var(--glow) 80%, transparent))",
                  }}
                  strokeWidth={1.25}
                />
              </motion.span>
            ))}
          </span>
          Winny<span className="text-pink">.</span>Land
        </Link>

        {onQuery && (
          <div className="relative ml-4 hidden flex-1 md:block">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input
              value={query ?? ""}
              onChange={(e) => onQuery(e.target.value)}
              placeholder="Search plushies, accessories, decor..."
              className="w-full rounded-full border border-border bg-secondary/50 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-pink focus:bg-background"
            />
          </div>
        )}

        <nav className="ml-auto flex items-center gap-1">
          <ThemeToggle />
          <Link to="/marketplace/wishlist" className="hover-pink relative rounded-full p-2.5">
            <Heart className="h-5 w-5" />
            {wishCount > 0 && (
              <span className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-pink px-1 text-[10px] font-bold text-primary">
                {wishCount}
              </span>
            )}
          </Link>
          <Link to="/marketplace/cart" className="hover-pink relative rounded-full p-2.5">
            <ShoppingBag className="h-5 w-5" />
            {cartCount > 0 && (
              <span className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-pink px-1 text-[10px] font-bold text-primary">
                {cartCount}
              </span>
            )}
          </Link>
          <Link
            to="/admin"
            className="ml-2 hidden rounded-full border border-border px-4 py-2 text-xs font-medium hover-pink sm:block"
          >
            Admin
          </Link>
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
  );
}
