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
      { name: "description", content: "Perfumes, plush toys & stationery. Curated, cozy, pink." },
    ],
  }),
  component: Landing,
});

function FilledStar({ size = 12 }: { size?: number }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="currentColor"
      className="text-pink"
      style={{
        filter:
          "drop-shadow(0 0 6px color-mix(in oklab, var(--glow) 90%, transparent)) drop-shadow(0 0 14px color-mix(in oklab, var(--glow) 60%, transparent))",
      }}
    >
      <path d="M12 2l2.9 6.9L22 10l-5.5 4.8L18 22l-6-3.6L6 22l1.5-7.2L2 10l7.1-1.1z" />
    </svg>
  );
}

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

        {/* Filled glowing stars with soft orbit/floating animation */}
        <div className="pointer-events-none absolute inset-0 z-[2]">
          {[
            { top: "20%", left: "18%", size: 14, delay: 0, dur: 7 },
            { top: "30%", left: "10%", size: 10, delay: 0.6, dur: 8 },
            { top: "44%", left: "22%", size: 16, delay: 1.0, dur: 9 },
            { top: "58%", left: "14%", size: 12, delay: 1.6, dur: 7.5 },
            { top: "72%", left: "20%", size: 10, delay: 0.8, dur: 8.5 },
            { top: "26%", left: "78%", size: 12, delay: 0.4, dur: 9 },
            { top: "40%", left: "84%", size: 16, delay: 1.2, dur: 7 },
            { top: "60%", left: "76%", size: 10, delay: 1.8, dur: 8 },
            { top: "74%", left: "82%", size: 14, delay: 0.2, dur: 9.5 },
            { top: "16%", left: "50%", size: 9, delay: 1.4, dur: 8 },
            { top: "84%", left: "48%", size: 11, delay: 0.5, dur: 7.5 },
          ].map((s, i) => (
            <span
              key={i}
              className="orbit absolute"
              style={{
                top: s.top,
                left: s.left,
                animationDelay: `${s.delay}s`,
                animationDuration: `${s.dur}s`,
              }}
            >
              <FilledStar size={s.size} />
            </span>
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
            Where Perfumes, Plush Toys &amp; Stationery
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
