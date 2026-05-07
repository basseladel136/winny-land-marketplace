import { createFileRoute } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { useMemo, useRef, useState } from "react";
import { ChevronDown, Sparkles, Star } from "lucide-react";
import { MarketplaceNav } from "@/components/MarketplaceNav";
import { ProductCard } from "@/components/ProductCard";
import { Particles } from "@/components/Particles";
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
      <section className="relative min-h-screen overflow-hidden">
        <div className="pointer-events-none absolute inset-0">
          <div className="absolute -left-32 top-1/4 h-96 w-96 rounded-full bg-pink-soft blur-3xl opacity-70" />
          <div className="absolute -right-32 bottom-0 h-96 w-96 rounded-full bg-beige blur-3xl opacity-80" />
        </div>

        {/* Animated particles background */}
        <Particles />

        {/* Twinkling stars on the left side */}
        <div className="pointer-events-none absolute inset-y-0 left-0 hidden w-1/2 sm:block">
          {[
            { top: "12%", left: "8%", size: 14, delay: 0 },
            { top: "22%", left: "28%", size: 10, delay: 0.6 },
            { top: "38%", left: "12%", size: 18, delay: 1.1 },
            { top: "55%", left: "32%", size: 12, delay: 0.3 },
            { top: "68%", left: "10%", size: 16, delay: 1.4 },
            { top: "78%", left: "26%", size: 9, delay: 0.9 },
            { top: "30%", left: "42%", size: 8, delay: 1.7 },
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
                filter: "drop-shadow(0 0 6px var(--glow))",
              }}
              fill="currentColor"
              strokeWidth={0}
            />
          ))}
        </div>

        <div className="relative mx-auto flex min-h-screen max-w-6xl flex-col items-center justify-center px-6 text-center">
          <motion.div
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="mb-6 inline-flex items-center gap-2 rounded-full border border-border bg-background/60 px-4 py-1.5 text-xs font-medium backdrop-blur"
          >
            <Sparkles className="h-3.5 w-3.5 text-pink" />
            A little corner of softness
          </motion.div>

          {/* Animated SVG drawing of Winny.Land */}
          <div className="relative w-full max-w-[900px]">
            <svg
              viewBox="0 0 900 200"
              className="h-auto w-full"
              aria-label="Winny.Land"
            >
              <defs>
                <linearGradient id="winnyStroke" x1="0" x2="1" y1="0" y2="0">
                  <stop offset="0%" stopColor="var(--foreground)" />
                  <stop offset="100%" stopColor="var(--foreground)" />
                </linearGradient>
              </defs>
              <motion.text
                x="50%"
                y="55%"
                textAnchor="middle"
                dominantBaseline="middle"
                fontFamily='"Abril Fatface", serif'
                fontSize="170"
                fill="var(--foreground)"
                stroke="var(--foreground)"
                strokeWidth={1}
                strokeDasharray="1200"
                style={{ filter: "drop-shadow(0 0 14px color-mix(in oklab, var(--glow) 45%, transparent))" }}
                initial={{ strokeDashoffset: 1200, fillOpacity: 0 }}
                animate={{ strokeDashoffset: 0, fillOpacity: 1 }}
                transition={{
                  strokeDashoffset: { duration: 2.4, ease: [0.16, 1, 0.3, 1] },
                  fillOpacity: { delay: 1.6, duration: 1.2 },
                }}
              >
                Winny.Land
              </motion.text>
            </svg>
          </div>

          <motion.p
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 1.6, duration: 0.8 }}
            className="mt-6 max-w-md text-balance text-lg text-muted-foreground"
          >
            Plushies, accessories & cozy decor — handpicked with love.
          </motion.p>

          <motion.button
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 2, duration: 0.6 }}
            onClick={scrollToShop}
            aria-label="Scroll to shop"
            className="neon-glow group mt-12 inline-flex h-14 w-14 items-center justify-center rounded-full border border-border bg-background/70 backdrop-blur hover:bg-pink hover:border-pink"
          >
            <ChevronDown className="h-6 w-6 animate-bounce group-hover:animate-none" />
          </motion.button>

          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 2.2 }}
            className="absolute bottom-8 text-xs uppercase tracking-[0.3em] text-muted-foreground"
          >
            Scroll to explore
          </motion.div>
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
