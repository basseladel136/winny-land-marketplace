import { createFileRoute } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { useMemo, useRef, useState } from "react";
import { ChevronDown, Sparkles, Star } from "lucide-react";
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

        {/* Big glowing hero star behind the logo (left) */}
        <div
          aria-hidden
          className="pointer-events-none absolute left-[10%] top-1/2 z-[1] -translate-y-1/2 animate-fade-in"
        >
          <Star
            className="twinkle text-pink"
            style={{
              width: "clamp(240px, 34vw, 480px)",
              height: "clamp(240px, 34vw, 480px)",
              filter:
                "drop-shadow(0 0 40px color-mix(in oklab, var(--glow) 75%, transparent)) drop-shadow(0 0 90px color-mix(in oklab, var(--gold) 40%, transparent))",
              opacity: 0.5,
            }}
            fill="currentColor"
            strokeWidth={0}
          />
        </div>

        {/* Minimal accent stars — only around the logo */}
        <div className="pointer-events-none absolute inset-0 z-[2]">
          {[
            { top: "30%", left: "6%", size: 14, delay: 0 },
            { top: "62%", left: "14%", size: 10, delay: 1.2 },
            { top: "22%", left: "22%", size: 8, delay: 0.6 },
            { top: "72%", left: "26%", size: 12, delay: 1.8 },
          ].map((s, i) => (
            <Star
              key={i}
              className="twinkle absolute text-pink"
              style={{
                top: s.top,
                left: s.left,
                width: s.size,
                height: s.size,
                animationDelay: `${s.delay}s`,
                filter: "drop-shadow(0 0 8px var(--glow))",
              }}
              fill="currentColor"
              strokeWidth={0}
            />
          ))}
        </div>

        <div className="relative z-10 mx-auto flex min-h-screen max-w-6xl flex-col items-center justify-center px-6 text-center">
          <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-border bg-background/60 px-4 py-1.5 text-xs font-medium backdrop-blur animate-fade-in">
            <Sparkles className="h-3.5 w-3.5 text-pink" />
            A little corner of softness
          </div>

          {/* Letter-by-letter reveal — luxury serif, constant color */}
          <h1
            className="select-none text-[clamp(3.5rem,11vw,9rem)] leading-[1.05] tracking-tight text-foreground"
            style={{
              fontFamily: '"Playfair Display", serif',
              fontWeight: 500,
              textShadow:
                "0 0 30px color-mix(in oklab, var(--glow) 30%, transparent)",
            }}
            aria-label="Winny Land"
          >
            {"Winny Land".split("").map((ch, i) => (
              <motion.span
                key={i}
                initial={{ opacity: 0, y: 24 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{
                  delay: 0.25 + i * 0.09,
                  duration: 0.55,
                  ease: [0.16, 1, 0.3, 1],
                }}
                className="inline-block"
                style={ch === " " ? { width: "0.4em" } : undefined}
              >
                {ch === " " ? "\u00A0" : ch}
              </motion.span>
            ))}
          </h1>

          <motion.p
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 1.4, duration: 0.6 }}
            className="mt-6 max-w-md text-balance text-lg italic text-muted-foreground"
            style={{ fontFamily: '"Playfair Display", serif' }}
          >
            Plushies, accessories & cozy decor — handpicked with love.
          </motion.p>

          <motion.button
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 1.7, duration: 0.5 }}
            onClick={scrollToShop}
            aria-label="Scroll to shop"
            className="neon-glow group mt-12 inline-flex h-14 w-14 items-center justify-center rounded-full border border-border bg-background/70 backdrop-blur hover:bg-pink hover:border-pink"
          >
            <ChevronDown className="h-6 w-6 animate-bounce group-hover:animate-none" />
          </motion.button>

          <div className="absolute bottom-8 text-xs uppercase tracking-[0.3em] text-muted-foreground opacity-70">
            Scroll to explore
          </div>
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
