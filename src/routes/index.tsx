import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { useEffect, useState } from "react";
import { ArrowRight, Sparkles } from "lucide-react";

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
  const navigate = useNavigate();
  const [done, setDone] = useState(false);

  useEffect(() => {
    const t = setTimeout(() => setDone(true), 2200);
    return () => clearTimeout(t);
  }, []);

  return (
    <main className="relative min-h-screen overflow-hidden bg-background">
      {/* Decorative blobs */}
      <div className="pointer-events-none absolute inset-0">
        <div className="absolute -left-32 top-1/4 h-96 w-96 rounded-full bg-pink-soft blur-3xl opacity-70" />
        <div className="absolute -right-32 bottom-0 h-96 w-96 rounded-full bg-beige blur-3xl opacity-80" />
      </div>

      <section className="relative mx-auto flex min-h-screen max-w-6xl flex-col items-center justify-center px-6 text-center">
        <motion.div
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
          className="mb-6 inline-flex items-center gap-2 rounded-full border border-border bg-background/60 px-4 py-1.5 text-xs font-medium backdrop-blur"
        >
          <Sparkles className="h-3.5 w-3.5 text-pink" />
          A little corner of softness
        </motion.div>

        <motion.h1
          initial={{ opacity: 0, scale: 0.92 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }}
          className="font-display text-[clamp(4rem,16vw,12rem)] leading-[0.9] tracking-tight"
        >
          Winny<span className="text-pink">.</span>Land
        </motion.h1>

        <motion.p
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 0.8, duration: 0.8 }}
          className="mt-6 max-w-md text-balance text-lg text-muted-foreground"
        >
          Plushies, accessories & cozy decor — handpicked with love.
        </motion.p>

        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={done ? { opacity: 1, y: 0 } : { opacity: 0, y: 10 }}
          transition={{ duration: 0.6 }}
          className="mt-10"
        >
          <button
            onClick={() => navigate({ to: "/marketplace" })}
            className="group inline-flex items-center gap-2 rounded-full bg-primary px-7 py-3.5 text-sm font-medium text-primary-foreground transition hover:bg-pink hover:text-primary"
          >
            Enter the shop
            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
          </button>
        </motion.div>

        <motion.div
          initial={{ opacity: 0 }}
          animate={done ? { opacity: 1 } : { opacity: 0 }}
          transition={{ delay: 0.4 }}
          className="absolute bottom-8 text-xs uppercase tracking-[0.3em] text-muted-foreground"
        >
          Scroll to explore
        </motion.div>
      </section>
    </main>
  );
}
