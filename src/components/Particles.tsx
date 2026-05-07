import { useEffect, useRef } from "react";

type Particle = {
  x: number;
  y: number;
  r: number;
  vx: number;
  vy: number;
  a: number;
  ad: number;
};

export function Particles({
  density = 0.00012,
  className = "",
}: {
  density?: number;
  className?: string;
}) {
  const ref = useRef<HTMLCanvasElement>(null);

  useEffect(() => {
    const canvas = ref.current;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    let raf = 0;
    let particles: Particle[] = [];
    let dpr = Math.min(window.devicePixelRatio || 1, 2);
    let width = 0;
    let height = 0;

    const isDark = () =>
      document.documentElement.classList.contains("dark") ||
      window.matchMedia("(prefers-color-scheme: dark)").matches;

    const resize = () => {
      const rect = canvas.getBoundingClientRect();
      width = rect.width;
      height = rect.height;
      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const count = Math.max(24, Math.floor(width * height * density));
      particles = new Array(count).fill(0).map(() => ({
        x: Math.random() * width,
        y: Math.random() * height,
        r: Math.random() * 1.6 + 0.4,
        vx: (Math.random() - 0.5) * 0.12,
        vy: (Math.random() - 0.5) * 0.12,
        a: Math.random(),
        ad: (Math.random() * 0.006 + 0.002) * (Math.random() > 0.5 ? 1 : -1),
      }));
    };

    const draw = () => {
      ctx.clearRect(0, 0, width, height);
      const dark = isDark();
      for (const p of particles) {
        p.x += p.vx;
        p.y += p.vy;
        p.a += p.ad;
        if (p.a > 1 || p.a < 0.1) p.ad *= -1;
        if (p.x < 0) p.x = width;
        if (p.x > width) p.x = 0;
        if (p.y < 0) p.y = height;
        if (p.y > height) p.y = 0;

        const alpha = dark ? p.a * 0.9 : p.a * 0.45;
        if (dark) {
          const grad = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 6);
          grad.addColorStop(0, `rgba(255, 200, 220, ${alpha})`);
          grad.addColorStop(1, "rgba(255, 200, 220, 0)");
          ctx.fillStyle = grad;
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.r * 6, 0, Math.PI * 2);
          ctx.fill();
        }
        ctx.fillStyle = dark
          ? `rgba(255, 230, 240, ${alpha})`
          : `rgba(180, 120, 140, ${alpha * 0.7})`;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
      }
      raf = requestAnimationFrame(draw);
    };

    resize();
    draw();
    const ro = new ResizeObserver(resize);
    ro.observe(canvas);
    return () => {
      cancelAnimationFrame(raf);
      ro.disconnect();
    };
  }, [density]);

  return (
    <canvas
      ref={ref}
      aria-hidden
      className={`pointer-events-none absolute inset-0 h-full w-full ${className}`}
    />
  );
}
