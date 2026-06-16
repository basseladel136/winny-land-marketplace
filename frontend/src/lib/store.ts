import { create } from "zustand";
import { persist } from "zustand/middleware";

export type Category = { id: string; name: string; slug: string };
export type Product = {
  id: string;
  name: string;
  price: number;
  description: string;
  image: string;
  categoryId: string;
};
export type CartItem = { productId: string; quantity: number };
export type Review = {
  id: string;
  productId: string;
  userName: string;
  rating: number; // 1–5
  body: string;
  createdAt: string;
};
export type Order = {
  id: string;
  items: { productId: string; name: string; price: number; quantity: number }[];
  total: number;
  status: "pending" | "processing" | "shipped" | "delivered" | "cancelled";
  customer: { name: string; email: string; address: string };
  createdAt: string;
};

const seedCategories: Category[] = [
  { id: "c1", name: "Perfumes", slug: "perfumes" },
  { id: "c2", name: "Plush Toys", slug: "plush" },
  { id: "c3", name: "Stationery", slug: "stationery" },
];

const img = (seed: string) =>
  `https://images.unsplash.com/photo-${seed}?w=800&auto=format&fit=crop&q=70`;

const seedProducts: Product[] = [
  { id: "p1", name: "Rose Petal Eau de Parfum", price: 60, description: "Delicate rose and peony eau de parfum, 50ml.", image: img("1541643600914-78b084683601"), categoryId: "c1" },
  { id: "p2", name: "Vanilla Musk Perfume", price: 55, description: "Warm vanilla and soft musk fragrance, long lasting.", image: img("1592945403244-b3fbafd7f539"), categoryId: "c1" },
  { id: "p3", name: "Bunny Cloud Plush", price: 28, description: "Ultra-soft pastel bunny plush, perfect for cuddles.", image: img("1535632787350-4e68ef0ac584"), categoryId: "c2" },
  { id: "p4", name: "Strawberry Bear", price: 32, description: "Sweet strawberry-themed teddy with embroidered details.", image: img("1559563458-527698bf5295"), categoryId: "c2" },
  { id: "p5", name: "Floral Notebook", price: 16, description: "Hardcover lined notebook with floral print.", image: img("1531346878377-a5be20888e57"), categoryId: "c3" },
  { id: "p6", name: "Pastel Pen Set", price: 18, description: "Set of 8 gel pens in pastel colors.", image: img("1583485088034-697b5bc36b92"), categoryId: "c3" },
];

type StoreState = {
  products: Product[];
  categories: Category[];
  cart: CartItem[];
  wishlist: string[];
  reviews: Review[];
  orders: Order[];
  coupons: Record<string, number>; // code -> percent
  addProduct: (p: Omit<Product, "id">) => void;
  updateProduct: (id: string, p: Partial<Product>) => void;
  deleteProduct: (id: string) => void;
  addCategory: (name: string) => void;
  updateCategory: (id: string, name: string) => void;
  deleteCategory: (id: string) => void;
  addToCart: (productId: string) => void;
  updateCartQty: (productId: string, qty: number) => void;
  removeFromCart: (productId: string) => void;
  clearCart: () => void;
  toggleWishlist: (productId: string) => void;
  addReview: (review: Omit<Review, "id" | "createdAt">) => void;
  placeOrder: (customer: Order["customer"], total: number) => string;
  updateOrderStatus: (id: string, status: Order["status"]) => void;
};

const id = () => Math.random().toString(36).slice(2, 9);
const slugify = (s: string) => s.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");

export const useStore = create<StoreState>()(
  persist(
    (set, get) => ({
      products: seedProducts,
      categories: seedCategories,
      cart: [],
      wishlist: [],
      reviews: [],
      orders: [],
      coupons: { WINNY10: 10, LOVE20: 20 },
      addProduct: (p) => set((s) => ({ products: [...s.products, { ...p, id: id() }] })),
      updateProduct: (pid, p) =>
        set((s) => ({ products: s.products.map((x) => (x.id === pid ? { ...x, ...p } : x)) })),
      deleteProduct: (pid) => set((s) => ({ products: s.products.filter((x) => x.id !== pid) })),
      addCategory: (name) =>
        set((s) => ({ categories: [...s.categories, { id: id(), name, slug: slugify(name) }] })),
      updateCategory: (cid, name) =>
        set((s) => ({
          categories: s.categories.map((c) => (c.id === cid ? { ...c, name, slug: slugify(name) } : c)),
        })),
      deleteCategory: (cid) => set((s) => ({ categories: s.categories.filter((c) => c.id !== cid) })),
      addToCart: (productId) =>
        set((s) => {
          const existing = s.cart.find((c) => c.productId === productId);
          if (existing) {
            return { cart: s.cart.map((c) => (c.productId === productId ? { ...c, quantity: c.quantity + 1 } : c)) };
          }
          return { cart: [...s.cart, { productId, quantity: 1 }] };
        }),
      updateCartQty: (productId, qty) =>
        set((s) => ({
          cart: qty <= 0 ? s.cart.filter((c) => c.productId !== productId) : s.cart.map((c) => (c.productId === productId ? { ...c, quantity: qty } : c)),
        })),
      removeFromCart: (productId) => set((s) => ({ cart: s.cart.filter((c) => c.productId !== productId) })),
      clearCart: () => set({ cart: [] }),
      toggleWishlist: (productId) =>
        set((s) => ({
          wishlist: s.wishlist.includes(productId)
            ? s.wishlist.filter((x) => x !== productId)
            : [...s.wishlist, productId],
        })),
      addReview: (review) =>
        set((s) => ({
          reviews: [
            { ...review, id: id(), createdAt: new Date().toISOString() },
            ...s.reviews,
          ],
        })),
      placeOrder: (customer, total) => {
        const oid = id();
        const items = get().cart.map((c) => {
          const p = get().products.find((x) => x.id === c.productId)!;
          return { productId: p.id, name: p.name, price: p.price, quantity: c.quantity };
        });
        set((s) => ({
          orders: [
            { id: oid, items, total, status: "pending", customer, createdAt: new Date().toISOString() },
            ...s.orders,
          ],
          cart: [],
        }));
        return oid;
      },
      updateOrderStatus: (oid, status) =>
        set((s) => ({ orders: s.orders.map((o) => (o.id === oid ? { ...o, status } : o)) })),
    }),
    {
      name: "winny-land-store",
      // Bumped to v2 when the catalogue moved to Perfumes / Plush Toys /
      // Stationery. Older persisted state is dropped so stale categories
      // (Accessories, Home Decor) don't linger in returning browsers.
      version: 2,
    }
  )
);
