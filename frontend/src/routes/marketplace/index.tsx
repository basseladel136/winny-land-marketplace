import { createFileRoute } from "@tanstack/react-router";
import { useMemo, useState } from "react";
import { motion } from "framer-motion";
import { MarketplaceNav } from "@/components/MarketplaceNav";
import { ProductCard } from "@/components/ProductCard";
import { useStore } from "@/lib/store";

export const Route = createFileRoute("/marketplace/")({
  head: () => ({
    meta: [
      { title: "Marketplace — Winny Land" },
      { name: "description", content: "Browse perfumes, plush toys and stationery." },
    ],
  }),
  component: Marketplace,
});

function Marketplace() {
  const products = useStore((s) => s.products);
  const categories = useStore((s) => s.categories);
  const [query, setQuery] = useState("");
  const [activeCat, setActiveCat] = useState<string | null>(null);

  const filtered = useMemo(() => {
    return products.filter((p) => {
      const matchQ = query.trim() === "" || p.name.toLowerCase().includes(query.toLowerCase()) || p.description.toLowerCase().includes(query.toLowerCase());
      const matchC = !activeCat || p.categoryId === activeCat;
      return matchQ && matchC;
    });
  }, [products, query, activeCat]);

  return (
    <div className="min-h-screen bg-background">
      <MarketplaceNav query={query} onQuery={setQuery} />

      <section className="mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6">
        <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}>
          <h1 className="font-display text-5xl sm:text-6xl">Today's picks</h1>
          <p className="mt-3 max-w-xl text-muted-foreground">A soft mix of new arrivals and forever favorites.</p>
        </motion.div>

        <div className="mt-8 flex flex-wrap gap-2">
          <button
            onClick={() => setActiveCat(null)}
            className={`rounded-full border px-4 py-1.5 text-xs font-medium transition ${
              !activeCat ? "border-primary bg-primary text-primary-foreground" : "border-border hover-pink"
            }`}
          >
            All
          </button>
          {categories.map((c) => (
            <button
              key={c.id}
              onClick={() => setActiveCat(c.id)}
              className={`rounded-full border px-4 py-1.5 text-xs font-medium transition ${
                activeCat === c.id ? "border-primary bg-primary text-primary-foreground" : "border-border hover-pink"
              }`}
            >
              {c.name}
            </button>
          ))}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 pb-24 sm:px-6">
        {filtered.length === 0 ? (
          <div className="rounded-2xl border border-dashed border-border py-20 text-center text-muted-foreground">
            No products found.
          </div>
        ) : (
          <div className="grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-3 lg:grid-cols-4">
            {filtered.map((p, i) => (
              <ProductCard key={p.id} product={p} index={i} />
            ))}
          </div>
        )}
      </section>
    </div>
  );
}
