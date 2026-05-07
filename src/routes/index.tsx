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
        {/* Soft gradient washes */}
        <div className="pointer-events-none absolute inset-0">
          <div
            className="absolute -left-40 top-1/4 h-[28rem] w-[28rem] rounded-full blur-3xl opacity-70"
            style={{ background: "color-mix(in oklab, var(--pink) 50%, transparent)" }}
          />
          <div
            className="absolute -right-32 bottom-0 h-96 w-96 rounded-full blur-3xl opacity-70"
            style={{ background: "color-mix(in oklab, var(--gold-soft) 60%, transparent)" }}
          />
        </div>

        {/* Floating particles */}
        <Particles />

        {/* Big glowing hero star behind the logo */}
        <motion.div
          initial={{ opacity: 0, scale: 0.6, rotate: -8 }}
          animate={{ opacity: 1, scale: 1, rotate: 0 }}
          transition={{ duration: 1.6, ease: [0.16, 1, 0.3, 1] }}
          aria-hidden
          className="pointer-events-none absolute left-[8%] top-1/2 z-[1] -translate-y-1/2"
        >
          <Star
            className="twinkle text-pink"
            style={{
              width: "clamp(220px, 32vw, 460px)",
              height: "clamp(220px, 32vw, 460px)",
              filter:
                "drop-shadow(0 0 30px color-mix(in oklab, var(--glow) 70%, transparent)) drop-shadow(0 0 80px color-mix(in oklab, var(--gold) 50%, transparent))",
              opacity: 0.55,
            }}
            fill="currentColor"
            strokeWidth={0}
          />
        </motion.div>

        {/* Twinkling stars — focused on the left side */}
        <div className="pointer-events-none absolute inset-0 z-[2]">
          {[
            { top: "8%", left: "5%", size: 18, delay: 0, color: "pink" },
            { top: "16%", left: "20%", size: 12, delay: 0.4, color: "gold" },
            { top: "24%", left: "9%", size: 22, delay: 0.9, color: "pink" },
            { top: "34%", left: "28%", size: 14, delay: 0.2, color: "gold" },
            { top: "44%", left: "15%", size: 26, delay: 1.3, color: "pink" },
            { top: "50%", left: "3%", size: 16, delay: 0.7, color: "gold" },
            { top: "58%", left: "26%", size: 20, delay: 1.6, color: "pink" },
            { top: "66%", left: "11%", size: 14, delay: 0.5, color: "gold" },
            { top: "74%", left: "22%", size: 18, delay: 1.1, color: "pink" },
            { top: "82%", left: "7%", size: 22, delay: 0.3, color: "gold" },
            { top: "88%", left: "30%", size: 12, delay: 1.5, color: "pink" },
            { top: "20%", left: "44%", size: 10, delay: 1.8, color: "gold" },
            { top: "60%", left: "42%", size: 11, delay: 0.8, color: "pink" },
            { top: "14%", left: "82%", size: 10, delay: 1.2, color: "gold" },
            { top: "72%", left: "88%", size: 12, delay: 0.6, color: "pink" },
          ].map((s, i) => (
            <Star
              key={i}
              className="twinkle absolute"
              style={{
                top: s.top,
                left: s.left,
                width: s.size,
                height: s.size,
                color: s.color === "gold" ? "var(--gold)" : "var(--pink)",
                animationDelay: `${s.delay}s`,
                filter:
                  "drop-shadow(0 0 8px var(--glow)) drop-shadow(0 0 16px color-mix(in oklab, var(--glow) 60%, transparent))",
              }}
              fill="currentColor"
              strokeWidth={0}
            />
          ))}
        </div>

        <div className="relative z-10 mx-auto flex min-h-screen max-w-6xl flex-col items-center justify-center px-6 text-center">
          <motion.div
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="mb-6 inline-flex items-center gap-2 rounded-full border border-border bg-background/60 px-4 py-1.5 text-xs font-medium backdrop-blur"
          >
            <Sparkles className="h-3.5 w-3.5 text-pink" />
            A little corner of softness
          </motion.div>

          {/* Letter-by-letter reveal — luxury serif */}
          <h1
            className="select-none text-[clamp(3.5rem,11vw,9rem)] leading-[1.05] tracking-tight text-foreground"
            style={{
              fontFamily: '"Playfair Display", serif',
              fontWeight: 500,
              textShadow:
                "0 0 30px color-mix(in oklab, var(--glow) 35%, transparent), 0 0 60px color-mix(in oklab, var(--gold) 25%, transparent)",
            }}
            aria-label="Winny Land"
          >
            {"Winny Land".split("").map((ch, i) => (
              <motion.span
                key={i}
                initial={{ opacity: 0, y: 30, filter: "blur(8px)" }}
                animate={{ opacity: 1, y: 0, filter: "blur(0px)" }}
                transition={{
                  delay: 0.3 + i * 0.12,
                  duration: 0.7,
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
            transition={{ delay: 1.8, duration: 0.8 }}
            className="mt-6 max-w-md text-balance text-lg italic text-muted-foreground"
            style={{ fontFamily: '"Playfair Display", serif' }}
          >
            Plushies, accessories & cozy decor — handpicked with love.
          </motion.p>

          <motion.button
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 2.1, duration: 0.6 }}
            onClick={scrollToShop}
            aria-label="Scroll to shop"
            className="neon-glow group mt-12 inline-flex h-14 w-14 items-center justify-center rounded-full border border-border bg-background/70 backdrop-blur hover:bg-pink hover:border-pink"
          >
            <ChevronDown className="h-6 w-6 animate-bounce group-hover:animate-none" />
          </motion.button>

          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 2.3 }}
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
