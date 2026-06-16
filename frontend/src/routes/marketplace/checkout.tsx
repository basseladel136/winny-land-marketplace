import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { useEffect, useMemo, useRef, useState } from "react";
import { Check, Tag } from "lucide-react";
import { MarketplaceNav } from "@/components/MarketplaceNav";
import { useStore } from "@/lib/store";
import { formatPrice } from "@/lib/utils";

export const Route = createFileRoute("/marketplace/checkout")({
  head: () => ({ meta: [{ title: "Checkout — Winny Land" }] }),
  component: Checkout,
});

function Checkout() {
  const navigate = useNavigate();
  const cart = useStore((s) => s.cart);
  const products = useStore((s) => s.products);
  const coupons = useStore((s) => s.coupons);
  const placeOrder = useStore((s) => s.placeOrder);

  const items = cart.map((c) => ({ ...c, product: products.find((p) => p.id === c.productId)! })).filter((i) => i.product);
  const subtotal = useMemo(() => items.reduce((n, i) => n + i.product.price * i.quantity, 0), [items]);

  const [coupon, setCoupon] = useState("");
  const [appliedDiscount, setAppliedDiscount] = useState(0);
  const [couponMsg, setCouponMsg] = useState<string | null>(null);
  const [form, setForm] = useState({ name: "", email: "", address: "" });
  const [placed, setPlaced] = useState<string | null>(null);
  const navTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => () => { if (navTimerRef.current) clearTimeout(navTimerRef.current); }, []);

  const discountAmt = (subtotal * appliedDiscount) / 100;
  const total = Math.max(0, subtotal - discountAmt);

  const applyCoupon = () => {
    const code = coupon.trim().toUpperCase();
    if (coupons[code]) {
      setAppliedDiscount(coupons[code]);
      setCouponMsg(`✓ ${coupons[code]}% off applied`);
    } else {
      setAppliedDiscount(0);
      setCouponMsg("Invalid coupon code");
    }
  };

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!items.length) return;
    const id = placeOrder(form, total);
    setPlaced(id);
    navTimerRef.current = setTimeout(() => navigate({ to: "/marketplace" }), 2200);
  };

  if (placed) {
    return (
      <div className="min-h-screen bg-background">
        <MarketplaceNav />
        <main className="mx-auto flex min-h-[60vh] max-w-md flex-col items-center justify-center px-4 text-center">
          <div className="flex h-16 w-16 items-center justify-center rounded-full bg-pink"><Check className="h-8 w-8" /></div>
          <h1 className="mt-6 font-display text-4xl">Thank you!</h1>
          <p className="mt-2 text-muted-foreground">Order #{placed} placed successfully.</p>
        </main>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <MarketplaceNav />
      <main className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
        <h1 className="font-display text-5xl">Checkout</h1>
        <div className="mt-10 grid gap-8 lg:grid-cols-[1fr_380px]">
          <form onSubmit={submit} className="space-y-4">
            <h2 className="font-display text-2xl">Delivery details</h2>
            {(["name", "email", "address"] as const).map((k) => (
              <div key={k}>
                <label className="mb-1 block text-xs font-medium uppercase tracking-wider text-muted-foreground">{k}</label>
                <input
                  required
                  type={k === "email" ? "email" : "text"}
                  value={form[k]}
                  onChange={(e) => setForm({ ...form, [k]: e.target.value })}
                  className="w-full rounded-xl border border-border bg-card px-4 py-3 outline-none focus:border-pink"
                />
              </div>
            ))}
            <button
              type="submit"
              disabled={!items.length}
              className="mt-4 w-full rounded-full bg-primary py-3.5 text-sm font-medium text-primary-foreground transition hover:bg-pink hover:text-primary disabled:opacity-50"
            >
              Place order — {formatPrice(total)}
            </button>
          </form>

          <aside className="h-fit space-y-6 rounded-2xl border border-border bg-secondary/40 p-6">
            <div>
              <h2 className="font-display text-2xl">Order summary</h2>
              <div className="mt-4 space-y-2 text-sm">
                {items.map((i) => (
                  <div key={i.productId} className="flex justify-between">
                    <span className="text-muted-foreground">{i.product.name} × {i.quantity}</span>
                    <span>{formatPrice(i.product.price * i.quantity)}</span>
                  </div>
                ))}
              </div>
            </div>

            <div>
              <label className="mb-2 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                <Tag className="h-3.5 w-3.5" /> Coupon
              </label>
              <div className="flex gap-2">
                <input
                  value={coupon}
                  onChange={(e) => setCoupon(e.target.value)}
                  placeholder="WINNY10"
                  className="flex-1 rounded-xl border border-border bg-card px-3 py-2 text-sm outline-none focus:border-pink"
                />
                <button type="button" onClick={applyCoupon} className="rounded-xl border border-border px-4 py-2 text-sm hover-pink">
                  Apply
                </button>
              </div>
              {couponMsg && <p className={`mt-2 text-xs ${appliedDiscount ? "text-foreground" : "text-destructive"}`}>{couponMsg}</p>}
            </div>

            <div className="space-y-2 border-t border-border pt-4 text-sm">
              <div className="flex justify-between"><span className="text-muted-foreground">Subtotal</span><span>{formatPrice(subtotal)}</span></div>
              {appliedDiscount > 0 && (
                <div className="flex justify-between text-pink"><span>Discount ({appliedDiscount}%)</span><span>-{formatPrice(discountAmt)}</span></div>
              )}
              <div className="flex justify-between text-base font-semibold pt-2 border-t border-border"><span>Total</span><span>{formatPrice(total)}</span></div>
            </div>
          </aside>
        </div>
      </main>
    </div>
  );
}
