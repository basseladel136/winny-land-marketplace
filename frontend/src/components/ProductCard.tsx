import { Heart, Plus } from "lucide-react";
import { motion } from "framer-motion";
import { Link } from "@tanstack/react-router";
import { useStore, type Product } from "@/lib/store";
import { formatPrice } from "@/lib/utils";

export function ProductCard({ product, index = 0 }: { product: Product; index?: number }) {
  const addToCart = useStore((s) => s.addToCart);
  const toggleWishlist = useStore((s) => s.toggleWishlist);
  const inWishlist = useStore((s) => s.wishlist.includes(product.id));
  const category = useStore((s) => s.categories.find((c) => c.id === product.categoryId));

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-50px" }}
      transition={{ duration: 0.5, delay: (index % 8) * 0.05 }}
      className="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card transition hover:border-pink hover:shadow-[0_8px_30px_-12px_oklch(0.85_0.09_15/0.4)]"
    >
      <div className="relative aspect-square overflow-hidden bg-secondary">
        <Link
          to="/marketplace/$slug"
          params={{ slug: product.id }}
          aria-label={`View ${product.name}`}
        >
          <img
            src={product.image}
            alt={product.name}
            loading="lazy"
            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
          />
        </Link>
        <button
          onClick={(e) => {
            e.preventDefault();
            toggleWishlist(product.id);
          }}
          aria-label="Toggle wishlist"
          className="absolute right-3 top-3 rounded-full bg-background/90 p-2 backdrop-blur transition hover:bg-pink"
        >
          <Heart className={`h-4 w-4 ${inWishlist ? "fill-pink text-pink" : ""}`} />
        </button>
      </div>
      <div className="flex flex-1 flex-col gap-2 p-4">
        {category && (
          <span className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
            {category.name}
          </span>
        )}
        <Link to="/marketplace/$slug" params={{ slug: product.id }}>
          <h3 className="font-display text-lg leading-tight transition-colors hover:text-pink">
            {product.name}
          </h3>
        </Link>
        <div className="mt-auto flex items-center justify-between pt-2">
          <span className="text-lg font-semibold">{formatPrice(product.price)}</span>
          <button
            onClick={() => addToCart(product.id)}
            className="hover-pink inline-flex items-center gap-1 rounded-full border border-border px-3 py-1.5 text-xs font-medium"
          >
            <Plus className="h-3.5 w-3.5" /> Add
          </button>
        </div>
      </div>
    </motion.div>
  );
}
