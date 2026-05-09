import { Link } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { ThemeToggle } from "@/components/ThemeToggle";

function AuthStar({ size = 12 }: { size?: number }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="currentColor"
      className="text-pink"
      style={{
        filter:
          "drop-shadow(0 0 6px color-mix(in oklab, var(--glow) 90%, transparent)) drop-shadow(0 0 14px color-mix(in oklab, var(--glow) 55%, transparent))",
      }}
    >
      <path d="M12 2l2.9 6.9L22 10l-5.5 4.8L18 22l-6-3.6L6 22l1.5-7.2L2 10l7.1-1.1z" />
    </svg>
  );
}

export function AuthShell({
  title,
  subtitle,
  children,
}: {
  title: string;
  subtitle: string;
  children: React.ReactNode;
}) {
  const stars = [
    { top: "12%", left: "16%", size: 14, delay: 0, dur: 7 },
    { top: "26%", left: "82%", size: 12, delay: 0.6, dur: 8 },
    { top: "70%", left: "12%", size: 16, delay: 1.0, dur: 9 },
    { top: "82%", left: "78%", size: 10, delay: 1.4, dur: 8.5 },
    { top: "44%", left: "8%", size: 9, delay: 0.8, dur: 7.5 },
    { top: "58%", left: "88%", size: 11, delay: 0.3, dur: 9.5 },
  ];

  return (
    <main className="relative flex min-h-screen items-center justify-center overflow-hidden bg-background px-4 py-10">
      {/* glow wash */}
      <div
        aria-hidden
        className="pointer-events-none absolute left-1/2 top-1/2 h-[36rem] w-[36rem] -translate-x-1/2 -translate-y-1/2 rounded-full blur-3xl opacity-50"
        style={{ background: "color-mix(in oklab, var(--pink) 40%, transparent)" }}
      />

      {/* floating stars */}
      <div aria-hidden className="pointer-events-none absolute inset-0">
        {stars.map((s, i) => (
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
            <AuthStar size={s.size} />
          </span>
        ))}
      </div>

      {/* top bar */}
      <div className="absolute right-4 top-4">
        <ThemeToggle />
      </div>
      <Link
        to="/"
        className="absolute left-6 top-6 font-display text-xl tracking-tight"
      >
        <span className="text-pink">Winny</span>
        <span className="text-foreground"> Land</span>
      </Link>

      <motion.div
        initial={{ opacity: 0, y: 16 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6, ease: [0.16, 1, 0.3, 1] }}
        className="relative z-10 w-full max-w-md rounded-3xl border border-border bg-card/80 p-8 shadow-xl backdrop-blur-xl sm:p-10"
      >
        <h1
          className="text-center text-4xl"
          style={{ fontFamily: '"Playfair Display", serif', fontWeight: 500 }}
        >
          {title}
        </h1>
        <p className="mt-2 text-center text-sm text-muted-foreground">{subtitle}</p>
        <div className="mt-8">{children}</div>
      </motion.div>
    </main>
  );
}
