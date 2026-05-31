import { createFileRoute, Link } from "@tanstack/react-router";
import { MarketplaceNav } from "@/components/MarketplaceNav";
import { ProductCard } from "@/components/ProductCard";
import { useStore } from "@/lib/store";

export const Route = createFileRoute("/marketplace/wishlist")({
  head: () => ({ meta: [{ title: "Wishlist — Winny Land" }] }),
  component: Wishlist,
});

function Wishlist() {
  const wishlist = useStore((s) => s.wishlist);
  const products = useStore((s) => s.products);
  const items = products.filter((p) => wishlist.includes(p.id));

  return (
    <div className="min-h-screen bg-background">
      <MarketplaceNav />
      <main className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <h1 className="font-display text-5xl">Wishlist</h1>
        {items.length === 0 ? (
          <div className="mt-12 rounded-2xl border border-dashed border-border py-20 text-center">
            <p className="text-muted-foreground">No saved items yet.</p>
            <Link to="/marketplace" className="mt-4 inline-block rounded-full bg-primary px-6 py-2.5 text-sm text-primary-foreground hover:bg-pink hover:text-primary">
              Browse products
            </Link>
          </div>
        ) : (
          <div className="mt-10 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            {items.map((p, i) => <ProductCard key={p.id} product={p} index={i} />)}
          </div>
        )}
      </main>
    </div>
  );
}
