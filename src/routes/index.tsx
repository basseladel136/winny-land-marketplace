import { createFileRoute } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { useMemo, useRef, useState } from "react";
import { ChevronDown } from "lucide-react";
import { MarketplaceNav } from "@/components/MarketplaceNav";
import { ProductCard } from "@/components/ProductCard";
import { useStore } from "@/lib/store";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "Winny Land — Sweet things for sweet people" },
      { name: "description", content: "Plushies, accessories, decor & stationery. Curated, cozy, pink." },
    ],
  }),
  component: Landing,
});

function Landing() {
  const shopRef = useRef<HTMLDivElement>(null);
  const products = useStore((s) => s.products);
  const categories = useStore((s) => s.categories);
  const [query, setQuery] = useState("");
  const [activeCat, setActiveCat] = useState<string | null>(null);

  const filtered = useMemo(() => {
    return products.filter((p) => {
      const matchQ =
        query.trim() === "" ||
        p.name.toLowerCase().includes(query.toLowerCase()) ||
        p.description.toLowerCase().includes(query.toLowerCase());
      const matchC = !activeCat || p.categoryId === activeCat;
      return matchQ && matchC;
    });
  }, [products, query, activeCat]);

  const scrollToShop = () => {
    shopRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  return (
    <main className="relative min-h-screen overflow-x-hidden bg-background">
      {/* Hero */}
      <section className="relative min-h-screen overflow-hidden flex items-center justify-center">
        {/* Soft gradient washes */}
        <div className="pointer-events-none absolute inset-0">
          <div
            className="absolute left-1/4 top-1/2 h-[32rem] w-[32rem] -translate-x-1/2 -translate-y-1/2 rounded-full blur-3xl opacity-60"
            style={{ background: "color-mix(in oklab, var(--pink) 45%, transparent)" }}
          />
        </div>

        {/* Soft glowing circle particles (replacing all star shapes) */}
        <div className="pointer-events-none absolute inset-0 z-[2]">
          {[
            { top: "18%", left: "8%", size: 6, delay: 0 },
            { top: "30%", left: "4%", size: 4, delay: 0.4 },
            { top: "44%", left: "2%", size: 5, delay: 1.0 },
            { top: "58%", left: "10%", size: 7, delay: 1.6 },
            { top: "72%", left: "6%", size: 4, delay: 0.8 },
            { top: "82%", left: "18%", size: 6, delay: 1.4 },
            { top: "26%", left: "20%", size: 3, delay: 1.2 },
            { top: "38%", left: "26%", size: 4, delay: 0.6 },
            { top: "66%", left: "24%", size: 5, delay: 2.0 },
            { top: "14%", left: "28%", size: 3, delay: 1.8 },
            { top: "22%", left: "12%", size: 4, delay: 0.2 },
            { top: "34%", left: "16%", size: 3, delay: 0.9 },
            { top: "48%", left: "8%", size: 5, delay: 1.5 },
            { top: "54%", left: "20%", size: 3, delay: 0.5 },
            { top: "62%", left: "4%", size: 4, delay: 1.1 },
            { top: "70%", left: "16%", size: 3, delay: 1.7 },
            { top: "78%", left: "10%", size: 5, delay: 0.3 },
            { top: "40%", left: "14%", size: 3, delay: 2.1 },
          ].map((s, i) => (
            <span
              key={i}
              className="twinkle absolute rounded-full"
              style={{
                top: s.top,
                left: s.left,
                width: s.size,
                height: s.size,
                animationDelay: `${s.delay}s`,
                background:
                  "radial-gradient(circle, color-mix(in oklab, var(--pink) 90%, white) 0%, color-mix(in oklab, var(--pink) 50%, transparent) 60%, transparent 100%)",
                filter:
                  "drop-shadow(0 0 8px color-mix(in oklab, var(--glow) 80%, transparent))",
                opacity: 0.85,
              }}
            />
          ))}
        </div>

        <div className="relative z-10 mx-auto flex min-h-screen max-w-6xl flex-col items-center justify-center px-6 text-center">
          {/* Letter-by-letter reveal — "Winny" pink, "Land" foreground */}
          <h1
            className="select-none text-[clamp(3rem,10vw,8rem)] leading-[1.05] tracking-tight"
            style={{
              fontFamily: '"Playfair Display", serif',
              fontWeight: 500,
              textShadow:
                "0 0 30px color-mix(in oklab, var(--glow) 30%, transparent)",
            }}
            aria-label="Winny Land"
          >
            {"Winny Land".split("").map((ch, i) => {
              const isWinny = i < 5;
              return (
                <motion.span
                  key={i}
                  initial={{ opacity: 0, y: 24 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{
                    delay: 0.2 + i * 0.08,
                    duration: 0.5,
                    ease: [0.16, 1, 0.3, 1],
                  }}
                  className="inline-block"
                  style={{
                    color: isWinny ? "var(--pink)" : "var(--foreground)",
                    ...(ch === " " ? { width: "0.3em" } : {}),
                  }}
                >
                  {ch === " " ? "\u00A0" : ch}
                </motion.span>
              );
            })}
          </h1>

          <motion.p
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 1.2, duration: 0.6 }}
            className="mt-6 max-w-md text-base text-muted-foreground"
          >
            Where Gifts, Fragrance &amp; Accessories
            <br />
            Create Beautiful Moments
          </motion.p>

          <motion.button
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 1.5, duration: 0.5 }}
            onClick={scrollToShop}
            aria-label="Scroll to shop"
            className="group mt-16 inline-flex flex-col items-center gap-1 text-pink"
          >
            <span className="flex flex-col -space-y-3">
              <ChevronDown className="h-7 w-7 animate-bounce" strokeWidth={2.5} />
              <ChevronDown className="h-7 w-7 animate-bounce opacity-60" strokeWidth={2.5} style={{ animationDelay: "0.15s" }} />
            </span>
            <span className="text-[10px] uppercase tracking-[0.3em] text-muted-foreground mt-1">
              Scroll Down
            </span>
          </motion.button>
        </div>
      </section>

      {/* Shop section */}
      <div ref={shopRef}>
        <MarketplaceNav query={query} onQuery={setQuery} />

        <motion.section
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-100px" }}
          transition={{ duration: 0.7, ease: [0.16, 1, 0.3, 1] }}
          className="mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6"
        >
          <h2 className="font-display text-5xl sm:text-6xl">Today's picks</h2>
          <p className="mt-3 max-w-xl text-muted-foreground">
            A soft mix of new arrivals and forever favorites.
          </p>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-50px" }}
            transition={{ duration: 0.6, delay: 0.15 }}
            className="mt-8 flex flex-wrap gap-2"
          >
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
          </motion.div>
        </motion.section>

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
    </main>
  );
}
