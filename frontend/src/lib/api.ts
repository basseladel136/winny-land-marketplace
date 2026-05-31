/**
 * Winny Land API Client
 *
 * Drop-in fetch wrapper for the Laravel backend.
 * Token is read from authStore (localStorage key "winny-auth").
 *
 * Usage:
 *   import { api } from "@/lib/api";
 *   const data = await api.get("/products");
 *   const order = await api.post("/orders", payload);
 */

const BASE_URL = (import.meta.env.VITE_API_URL as string) ?? "http://localhost:8000/api/v1";

// --------------------------------------------------------------------------
// Token helpers  (reads from the authStore persisted key)
// --------------------------------------------------------------------------
function getToken(): string | null {
  try {
    const raw = localStorage.getItem("winny-auth");
    if (!raw) return null;
    const parsed = JSON.parse(raw) as { state?: { token?: string } };
    return parsed?.state?.token ?? null;
  } catch {
    return null;
  }
}

// --------------------------------------------------------------------------
// Core request helper
// --------------------------------------------------------------------------
type RequestOptions = Omit<RequestInit, "body"> & { body?: unknown };

async function request<T = unknown>(path: string, options: RequestOptions = {}): Promise<T> {
  const token = getToken();

  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...(options.headers as Record<string, string>),
  };

  if (token) {
    headers["Authorization"] = `Bearer ${token}`;
  }

  const locale = localStorage.getItem("locale") ?? "en";
  headers["Accept-Language"] = locale;

  const res = await fetch(`${BASE_URL}${path}`, {
    ...options,
    headers,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  });

  if (!res.ok) {
    const error = await res.json().catch(() => ({ message: res.statusText }));
    const err = Object.assign(new Error(error.message ?? "API Error"), {
      status: res.status,
      errors: error.errors ?? null,
    });
    throw err;
  }

  // 204 No Content
  if (res.status === 204) return undefined as unknown as T;

  return res.json() as Promise<T>;
}

// --------------------------------------------------------------------------
// Convenience methods
// --------------------------------------------------------------------------
export const api = {
  get: <T = unknown>(path: string, options?: RequestOptions) =>
    request<T>(path, { method: "GET", ...options }),

  post: <T = unknown>(path: string, body?: unknown, options?: RequestOptions) =>
    request<T>(path, { method: "POST", body, ...options }),

  patch: <T = unknown>(path: string, body?: unknown, options?: RequestOptions) =>
    request<T>(path, { method: "PATCH", body, ...options }),

  put: <T = unknown>(path: string, body?: unknown, options?: RequestOptions) =>
    request<T>(path, { method: "PUT", body, ...options }),

  delete: <T = unknown>(path: string, options?: RequestOptions) =>
    request<T>(path, { method: "DELETE", ...options }),
};

// --------------------------------------------------------------------------
// Typed endpoint helpers
// --------------------------------------------------------------------------

/* Auth */
export const authApi = {
  register: (data: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    phone?: string;
    locale?: string;
  }) =>
    api.post<{ user: ApiUser; token: string; message: string }>("/auth/register", data),

  login: (data: { email: string; password: string }) =>
    api.post<{ user: ApiUser; token: string }>("/auth/login", data),

  logout: () => api.post("/auth/logout"),

  me: () => api.get<{ user: ApiUser }>("/auth/me"),

  updateProfile: (data: { name?: string; phone?: string; locale?: string }) =>
    api.patch<{ user: ApiUser }>("/auth/me", data),

  /** Resend email verification link */
  resendVerification: () => api.post<{ message: string }>("/auth/email/resend"),
};

/* Products */
export const productsApi = {
  list: (params?: Record<string, string | number>) => {
    const qs = params ? "?" + new URLSearchParams(params as Record<string, string>).toString() : "";
    return api.get<PaginatedResponse<ApiProduct>>(`/products${qs}`);
  },
  get: (slug: string) => api.get<{ data: ApiProduct }>(`/products/${slug}`),
};

/* Categories */
export const categoriesApi = {
  list: () => api.get<{ data: ApiCategory[] }>("/categories"),
};

/* Cart */
export const cartApi = {
  get: () => api.get<{ data: ApiCart }>("/cart"),
  addItem: (productId: number, quantity = 1) =>
    api.post<{ data: ApiCart }>("/cart/items", { productId, quantity }),
  updateItem: (productId: number, quantity: number) =>
    api.patch<{ data: ApiCart }>(`/cart/items/${productId}`, { quantity }),
  removeItem: (productId: number) =>
    api.delete<{ data: ApiCart }>(`/cart/items/${productId}`),
  clear: () => api.delete<{ message: string }>("/cart"),
  sync: (items: { productId: number; quantity: number }[]) =>
    api.post<{ data: ApiCart }>("/cart/sync", { items }),
};

/* Orders */
export const ordersApi = {
  list: () => api.get<PaginatedResponse<ApiOrder>>("/orders"),
  place: (data: {
    customerName: string;
    customerEmail: string;
    shippingAddress: string;
    paymentMethod: "cod" | "paymob";
    couponCode?: string;
    notes?: string;
  }) => api.post<{ data: ApiOrder }>("/orders", data),
  get: (orderNumber: string) => api.get<{ data: ApiOrder }>(`/orders/${orderNumber}`),
};

/* Coupons */
export const couponsApi = {
  validate: (code: string, orderTotal: number) =>
    api.post<{ data: { code: string; type: string; value: number; discountAmount: number } }>(
      "/coupons/validate",
      { code, orderTotal }
    ),
};

/* Wishlist */
export const wishlistApi = {
  list: () => api.get<{ data: ApiProduct[] }>("/wishlist"),
  toggle: (productId: number) =>
    api.post<{ added: boolean; message: string }>(`/wishlist/${productId}/toggle`),
  remove: (productId: number) =>
    api.delete<{ message: string }>(`/wishlist/${productId}`),
};

/* Reviews */
export const reviewsApi = {
  list: (productId: number) =>
    api.get<PaginatedResponse<ApiReview>>(`/products/${productId}/reviews`),
  create: (productId: number, data: { rating: number; body?: string }) =>
    api.post<{ data: ApiReview }>(`/products/${productId}/reviews`, data),
  update: (productId: number, data: { rating?: number; body?: string }) =>
    api.patch<{ data: ApiReview }>(`/products/${productId}/reviews`, data),
  delete: (productId: number) =>
    api.delete(`/products/${productId}/reviews`),
};

/* Payments */
export const paymentsApi = {
  initiate: (orderNumber: string) =>
    api.post<{ data: { paymentKey: string; iframeUrl: string } }>(
      `/payments/${orderNumber}/initiate`
    ),
};

// --------------------------------------------------------------------------
// Type definitions (matching backend API resources)
// --------------------------------------------------------------------------
export interface ApiUser {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: "customer" | "admin";
  locale: "en" | "ar";
  avatar: string | null;
  isActive: boolean;
  emailVerifiedAt: string | null;   // null = unverified
  createdAt: string;
}

export interface ApiCategory {
  id: number;
  name: string;
  nameEn: string;
  nameAr: string;
  slug: string;
  isActive: boolean;
  sortOrder: number;
  productCount?: number;
}

export interface ApiProduct {
  id: number;
  categoryId: number;
  category?: ApiCategory;
  name: string;
  nameEn: string;
  nameAr: string;
  description: string | null;
  slug: string;
  price: number;
  comparePrice: number | null;
  stock: number;
  sku: string | null;
  image: string | null;
  images: string[];
  isActive: boolean;
  isFeatured: boolean;
  averageRating: number | null;
  reviewCount: number;
  createdAt: string;
}

export interface ApiCart {
  id: number;
  items: ApiCartItem[];
  itemCount: number;
  subtotal: number;
}

export interface ApiCartItem {
  productId: number;
  quantity: number;
  product?: ApiProduct;
  lineTotal: number;
}

export interface ApiOrder {
  id: number;
  orderNumber: string;
  status: "pending" | "processing" | "shipped" | "delivered" | "cancelled";
  subtotal: number;
  discountAmount: number;
  total: number;
  couponCode: string | null;
  paymentStatus: "pending" | "paid" | "failed" | "refunded";
  paymentMethod: "cod" | "paymob";
  paymentReference: string | null;
  customerName: string;
  customerEmail: string;
  customerPhone: string | null;
  shippingAddress: string;
  notes: string | null;
  items?: ApiOrderItem[];
  createdAt: string;
  updatedAt: string;
}

export interface ApiOrderItem {
  id: number;
  productId: number | null;
  productName: string;
  productImage: string | null;
  price: number;
  quantity: number;
  subtotal: number;
}

export interface ApiReview {
  id: number;
  userId: number;
  productId: number;
  rating: number;
  body: string | null;
  user?: Pick<ApiUser, "id" | "name" | "avatar">;
  createdAt: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
  };
}
