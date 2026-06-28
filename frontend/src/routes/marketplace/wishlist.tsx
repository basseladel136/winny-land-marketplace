import { useEffect, useState } from "react";
import { createFileRoute, Link } from "@tanstack/react-router";
import { MarketplaceNav } from "@/components/MarketplaceNav";
import { ProductCard } from "@/components/ProductCard";
import { useStore } from "@/lib/store";
import { useWishlistStore } from "@/lib/wishlistStore";
import { useAuthStore } from "@/lib/authStore";
import { wishlistApi, type ApiProduct } from "@/lib/api";

export const Route = createFileRoute("/marketplace/wishlist")({
  head: () => ({ meta: [{ title: "Wishlist — Winny Land" }] }),
  component: Wishlist,
});

function Wishlist() {
  const { isAuthenticated } = useAuthStore();
  const { apiItems, hydrate, count } = useWishlistStore();

  // Seed products for unauthenticated / local fallback
  const localWishlist = useStore((s) => s.wishlist);
  const localProducts = useStore((s) => s.products);

  const [apiLoaded, setApiLoaded] = useState(false);

  useEffect(() => {
    if (!isAuthenticated) return;
    if (apiItems.length > 0) {
      setApiLoaded(true);
      return;
    }
    hydrate().then(() => setApiLoaded(true));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated]);

  // For authenticated users: show real backend items (refreshed above)
  // For guests: show local seed items
  const showApi = isAuthenticated;
  const items = showApi
    ? apiItems
    : localProducts.filter((p) => localWishlist.includes(p.id));

  return (
    <div className="min-h-screen bg-background">
      <MarketplaceNav />
      <main className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <h1 className="font-display text-5xl">Wishlist</h1>
        {items.length === 0 ? (
          <div className="mt-12 rounded-2xl border border-dashed border-border py-20 text-center">
            <p className="text-muted-foreground">No saved items yet.</p>
            <Link
              to="/marketplace"
              className="mt-4 inline-block rounded-full bg-primary px-6 py-2.5 text-sm text-primary-foreground hover:bg-pink hover:text-primary"
            >
              Browse products
            </Link>
          </div>
        ) : showApi ? (
          <ApiWishlistGrid items={apiItems} />
        ) : (
          <div className="mt-10 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            {items.map((p, i) => (
              <ProductCard key={p.id} product={p} index={i} />
            ))}
          </div>
        )}
      </main>
    </div>
  );
}

function ApiWishlistGrid({ items }: { items: ApiProduct[] }) {
  const { toggle } = useWishlistStore();

  return (
    <div className="mt-10 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
      {items.map((product) => (
        <div
          key={product.id}
          className="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card transition hover:border-pink hover:shadow-[0_8px_30px_-12px_oklch(0.85_0.09_15/0.4)]"
        >
          <div className="relative aspect-square overflow-hidden bg-secondary">
            <Link to="/marketplace/$slug" params={{ slug: product.slug }}>
              {product.image ? (
                <img
                  src={product.image}
                  alt={product.name}
                  loading="lazy"
                  className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
              ) : (
                <div className="h-full w-full bg-muted" />
              )}
            </Link>
          </div>
          <div className="flex flex-1 flex-col gap-2 p-4">
            <Link to="/marketplace/$slug" params={{ slug: product.slug }}>
              <h3 className="font-display text-lg leading-tight transition-colors hover:text-pink">
                {product.name}
              </h3>
            </Link>
            <div className="mt-auto flex items-center justify-between pt-2">
              <span className="text-lg font-semibold">
                {product.price.toLocaleString("ar-EG", { style: "currency", currency: "EGP" })}
              </span>
              <button
                onClick={() => void toggle(String(product.id))}
                className="inline-flex items-center gap-1 rounded-full border border-destructive/40 px-3 py-1.5 text-xs font-medium text-destructive hover:bg-destructive/10"
              >
                Remove
              </button>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}
