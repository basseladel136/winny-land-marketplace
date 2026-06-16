import { createFileRoute, Link } from "@tanstack/react-router";
import { Minus, Plus, Trash2, ArrowRight } from "lucide-react";
import { MarketplaceNav } from "@/components/MarketplaceNav";
import { useStore } from "@/lib/store";
import { formatPrice } from "@/lib/utils";

export const Route = createFileRoute("/marketplace/cart")({
  head: () => ({ meta: [{ title: "Cart — Winny Land" }] }),
  component: CartPage,
});

function CartPage() {
  const cart = useStore((s) => s.cart);
  const products = useStore((s) => s.products);
  const updateQty = useStore((s) => s.updateCartQty);
  const remove = useStore((s) => s.removeFromCart);

  const items = cart.map((c) => ({ ...c, product: products.find((p) => p.id === c.productId)! })).filter((i) => i.product);
  const subtotal = items.reduce((n, i) => n + i.product.price * i.quantity, 0);

  return (
    <div className="min-h-screen bg-background">
      <MarketplaceNav />
      <main className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
        <h1 className="font-display text-5xl">Your cart</h1>

        {items.length === 0 ? (
          <div className="mt-12 rounded-2xl border border-dashed border-border py-20 text-center">
            <p className="text-muted-foreground">Your cart is empty.</p>
            <Link to="/marketplace" className="mt-4 inline-block rounded-full bg-primary px-6 py-2.5 text-sm text-primary-foreground hover:bg-pink hover:text-primary">
              Start shopping
            </Link>
          </div>
        ) : (
          <div className="mt-10 grid gap-8 lg:grid-cols-[1fr_360px]">
            <div className="space-y-4">
              {items.map((i) => (
                <div key={i.productId} className="flex gap-4 rounded-2xl border border-border bg-card p-4">
                  <img src={i.product.image} alt={i.product.name} className="h-24 w-24 rounded-xl object-cover" />
                  <div className="flex flex-1 flex-col">
                    <h3 className="font-display text-lg">{i.product.name}</h3>
                    <p className="text-sm text-muted-foreground">{formatPrice(i.product.price)}</p>
                    <div className="mt-auto flex items-center justify-between">
                      <div className="inline-flex items-center gap-2 rounded-full border border-border">
                        <button onClick={() => updateQty(i.productId, i.quantity - 1)} className="p-2 hover-pink rounded-l-full">
                          <Minus className="h-3.5 w-3.5" />
                        </button>
                        <span className="w-6 text-center text-sm">{i.quantity}</span>
                        <button onClick={() => updateQty(i.productId, i.quantity + 1)} className="p-2 hover-pink rounded-r-full">
                          <Plus className="h-3.5 w-3.5" />
                        </button>
                      </div>
                      <button onClick={() => remove(i.productId)} className="text-muted-foreground hover:text-destructive">
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                  <div className="font-semibold">{formatPrice(i.product.price * i.quantity)}</div>
                </div>
              ))}
            </div>

            <aside className="h-fit rounded-2xl border border-border bg-secondary/40 p-6">
              <h2 className="font-display text-2xl">Summary</h2>
              <div className="mt-4 space-y-2 text-sm">
                <div className="flex justify-between"><span className="text-muted-foreground">Subtotal</span><span>{formatPrice(subtotal)}</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">Shipping</span><span>Free</span></div>
                <div className="my-3 border-t border-border" />
                <div className="flex justify-between text-base font-semibold"><span>Total</span><span>{formatPrice(subtotal)}</span></div>
              </div>
              <Link
                to="/marketplace/checkout"
                className="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary py-3 text-sm font-medium text-primary-foreground transition hover:bg-pink hover:text-primary"
              >
                Checkout <ArrowRight className="h-4 w-4" />
              </Link>
            </aside>
          </div>
        )}
      </main>
    </div>
  );
}
