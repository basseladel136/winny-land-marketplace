import { useMemo, useState } from "react";
import { Link, createFileRoute, useParams } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { ChevronLeft, Heart, Minus, Plus, ShoppingCart, Star } from "lucide-react";
import { toast } from "sonner";

import { MarketplaceNav } from "@/components/MarketplaceNav";
import { ProductCard } from "@/components/ProductCard";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { Textarea } from "@/components/ui/textarea";
import { useStore } from "@/lib/store";
import { useAuthStore } from "@/lib/authStore";
import { formatPrice } from "@/lib/utils";

export const Route = createFileRoute("/marketplace/$slug")({
  component: ProductDetailsPage,
});

function ProductDetailsPage() {
  const { slug } = useParams({ from: "/marketplace/$slug" });

  const product = useStore((s) => s.products.find((p) => p.id === slug));
  const products = useStore((s) => s.products);
  const category = useStore((s) => s.categories.find((c) => c.id === product?.categoryId));
  const addToCart = useStore((s) => s.addToCart);
  const toggleWishlist = useStore((s) => s.toggleWishlist);
  const inWishlist = useStore((s) => (product ? s.wishlist.includes(product.id) : false));
  // Select the raw array (stable reference) and derive per-product reviews with
  // useMemo — filtering inside the selector returns a new array every render and
  // sends Zustand into an infinite update loop.
  const allReviews = useStore((s) => s.reviews);

  const [quantity, setQuantity] = useState(1);

  const reviews = useMemo(
    () => allReviews.filter((r) => r.productId === slug),
    [allReviews, slug]
  );

  const related = useMemo(
    () =>
      products
        .filter((p) => p.categoryId === product?.categoryId && p.id !== product?.id)
        .slice(0, 4),
    [products, product]
  );

  const averageRating =
    reviews.length > 0
      ? reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length
      : 0;

  if (!product) {
    return (
      <div className="min-h-screen bg-background">
        <MarketplaceNav />
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-center px-4 py-24 text-center">
          <ShoppingCart className="h-12 w-12 text-muted-foreground/40" />
          <h1 className="mt-4 text-xl font-semibold">Product not found</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            The item you're looking for may have been removed.
          </p>
          <Button asChild className="mt-6">
            <Link to="/marketplace">Back to shop</Link>
          </Button>
        </div>
      </div>
    );
  }

  const handleAddToCart = () => {
    for (let i = 0; i < quantity; i++) addToCart(product.id);
    toast.success(`${product.name} added to cart`);
  };

  return (
    <div className="min-h-screen bg-background">
      <MarketplaceNav />
      <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <Link
          to="/marketplace"
          className="mb-6 inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
          <ChevronLeft className="h-4 w-4" />
          Back to shop
        </Link>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.4 }}
          className="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,400px)_1fr] lg:items-start"
        >
          {/* Image */}
          <div className="relative aspect-square w-full max-w-sm overflow-hidden rounded-2xl border border-border/60 bg-muted lg:sticky lg:top-24">
            <img src={product.image} alt={product.name} className="h-full w-full object-cover" />
          </div>

          {/* Info */}
          <div className="flex flex-col">
            {category && (
              <span className="mb-2 text-sm font-medium text-primary">{category.name}</span>
            )}
            <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{product.name}</h1>

            <div className="mt-3 flex items-center gap-3">
              <StarRating value={averageRating} />
              <span className="text-sm text-muted-foreground">
                {reviews.length > 0
                  ? `${averageRating.toFixed(1)} · ${reviews.length} review${reviews.length === 1 ? "" : "s"}`
                  : "No reviews yet"}
              </span>
            </div>

            <div className="mt-5 text-3xl font-bold text-primary">{formatPrice(product.price)}</div>

            <Separator className="my-6" />

            {/* Quantity + actions */}
            <div className="flex flex-wrap items-center gap-4">
              <div className="flex items-center rounded-md border border-input">
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-9 w-9 rounded-r-none"
                  onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                  disabled={quantity <= 1}
                >
                  <Minus className="h-4 w-4" />
                </Button>
                <span className="w-12 text-center text-sm font-medium">{quantity}</span>
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-9 w-9 rounded-l-none"
                  onClick={() => setQuantity((q) => q + 1)}
                >
                  <Plus className="h-4 w-4" />
                </Button>
              </div>

              <Button
                onClick={() => toggleWishlist(product.id)}
                variant="outline"
                size="icon"
                aria-label="Toggle wishlist"
              >
                <Heart className={`h-4 w-4 ${inWishlist ? "fill-pink text-pink" : ""}`} />
              </Button>
            </div>

            <Button onClick={handleAddToCart} className="mt-4 w-full sm:w-auto">
              <ShoppingCart className="h-4 w-4" />
              Add to Cart
            </Button>

            {product.description && (
              <>
                <Separator className="my-6" />
                <div>
                  <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Description
                  </h2>
                  <p className="whitespace-pre-line text-sm leading-relaxed text-foreground/90">
                    {product.description}
                  </p>
                </div>
              </>
            )}
          </div>
        </motion.div>

        {/* Reviews */}
        <section className="mt-16">
          <h2 className="text-xl font-bold tracking-tight">
            Reviews {reviews.length ? `(${reviews.length})` : ""}
          </h2>

          <ReviewForm productId={product.id} />

          <div className="mt-6 space-y-4">
            {reviews.length === 0 ? (
              <p className="text-sm text-muted-foreground">
                No reviews yet. Be the first to share your experience.
              </p>
            ) : (
              reviews.map((review) => (
                <div key={review.id} className="rounded-xl border border-border/60 bg-card p-5">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                        {review.userName.charAt(0).toUpperCase()}
                      </div>
                      <div>
                        <p className="text-sm font-medium">{review.userName}</p>
                        <p className="text-xs text-muted-foreground">
                          {new Date(review.createdAt).toLocaleDateString()}
                        </p>
                      </div>
                    </div>
                    <StarRating value={review.rating} />
                  </div>
                  {review.body && (
                    <p className="mt-3 text-sm leading-relaxed text-foreground/90">{review.body}</p>
                  )}
                </div>
              ))
            )}
          </div>
        </section>

        {/* Related products */}
        {related.length > 0 && (
          <section className="mt-16">
            <h2 className="mb-6 text-xl font-bold tracking-tight">You may also like</h2>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
              {related.map((p, i) => (
                <ProductCard key={p.id} product={p} index={i} />
              ))}
            </div>
          </section>
        )}
      </div>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Review form
// ---------------------------------------------------------------------------
function ReviewForm({ productId }: { productId: string }) {
  const { isAuthenticated, user } = useAuthStore();
  const addReview = useStore((s) => s.addReview);

  const [rating, setRating] = useState(5);
  const [body, setBody] = useState("");

  if (!isAuthenticated) {
    return (
      <div className="mt-6 rounded-xl border border-border/60 bg-card p-5 text-sm text-muted-foreground">
        Please{" "}
        <Link to="/login" className="font-medium text-pink underline-offset-4 hover:underline">
          sign in
        </Link>{" "}
        to write a review.
      </div>
    );
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    addReview({
      productId,
      userName: user?.name ?? "Anonymous",
      rating,
      body: body.trim(),
    });
    toast.success("Thank you for your review!");
    setBody("");
    setRating(5);
  };

  return (
    <form onSubmit={handleSubmit} className="mt-6 rounded-xl border border-border/60 bg-card p-5">
      <h3 className="text-sm font-semibold">Write a review</h3>
      <div className="mt-3 flex items-center gap-1">
        {Array.from({ length: 5 }).map((_, i) => (
          <button
            key={i}
            type="button"
            onClick={() => setRating(i + 1)}
            aria-label={`Rate ${i + 1} stars`}
          >
            <Star
              className={`h-6 w-6 transition-colors ${
                i < rating ? "fill-amber-400 text-amber-400" : "text-muted-foreground/40"
              }`}
            />
          </button>
        ))}
      </div>
      <Textarea
        value={body}
        onChange={(e) => setBody(e.target.value)}
        placeholder="Share your thoughts about this product (optional)"
        className="mt-3"
        rows={3}
        maxLength={2000}
      />
      <Button type="submit" className="mt-3">
        Submit Review
      </Button>
    </form>
  );
}

function StarRating({ value }: { value: number }) {
  return (
    <div className="flex items-center gap-0.5">
      {Array.from({ length: 5 }).map((_, i) => (
        <Star
          key={i}
          className={`h-4 w-4 ${
            i < Math.round(value) ? "fill-amber-400 text-amber-400" : "text-muted-foreground/30"
          }`}
        />
      ))}
    </div>
  );
}
