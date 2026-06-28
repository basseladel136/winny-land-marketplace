/**
 * Winny Land Auth Store
 *
 * Zustand store for authentication state, persisted to localStorage.
 * Works alongside the existing store.ts (which handles cart, wishlist, etc.)
 *
 * Usage:
 *   import { useAuthStore } from "@/lib/authStore";
 *   const { user, token, login, logout, isAdmin, isEmailVerified } = useAuthStore();
 */

import { create } from "zustand";
import { persist } from "zustand/middleware";
import { authApi, type ApiUser } from "./api";
import { useWishlistStore } from "./wishlistStore";

interface AuthState {
  user: ApiUser | null;
  token: string | null;
  isLoading: boolean;
  error: string | null;

  // Computed helpers
  isAuthenticated: boolean;
  isAdmin: () => boolean;
  isEmailVerified: () => boolean;

  // Actions
  login: (email: string, password: string) => Promise<void>;
  register: (data: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    phone?: string;
    locale?: string;
  }) => Promise<{ email: string; message: string }>;
  verifyOtp: (email: string, otp: string) => Promise<void>;
  resendOtp: (email: string) => Promise<void>;
  logout: () => Promise<void>;
  fetchMe: () => Promise<void>;
  setUser: (user: ApiUser) => void;
  clearError: () => void;
  resendVerification: () => Promise<void>;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isLoading: false,
      error: null,
      isAuthenticated: false,

      /** True only if the authenticated user has the admin role */
      isAdmin: () => get().user?.role === "admin",

      /** True only if the authenticated user has verified their email */
      isEmailVerified: () => !!get().user?.emailVerifiedAt,

      login: async (email, password) => {
        set({ isLoading: true, error: null });
        try {
          const result = await authApi.login({ email, password });
          set({
            user: result.user,
            token: result.token,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          });
          // Hydrate wishlist from backend after login
          void useWishlistStore.getState().hydrate();
        } catch (err: unknown) {
          const apiErr = err as {
            errors?: { email?: string[] };
            message?: string;
            data?: { email_unverified?: boolean };
          };
          // Unverified accounts are not an error to display — the page redirects
          // the user to the OTP screen instead.
          if (apiErr?.data?.email_unverified) {
            set({ isLoading: false, isAuthenticated: false });
            throw err;
          }
          const message =
            apiErr?.errors?.email?.[0] ??
            apiErr?.message ??
            "Login failed. Please try again.";
          set({ isLoading: false, error: message, isAuthenticated: false });
          throw err;
        }
      },

      register: async (data) => {
        set({ isLoading: true, error: null });
        try {
          // No token is returned — the user must verify the OTP before signing in.
          const result = await authApi.register(data);
          set({ isLoading: false, error: null });
          return { email: result.email, message: result.message };
        } catch (err: unknown) {
          const apiErr = err as { errors?: Record<string, string[]>; message?: string };
          // Surface the first validation error or generic message
          const firstError = apiErr?.errors
            ? Object.values(apiErr.errors).flat()[0]
            : undefined;
          const message = firstError ?? apiErr?.message ?? "Registration failed. Please try again.";
          set({ isLoading: false, error: message, isAuthenticated: false });
          throw err;
        }
      },

      verifyOtp: async (email, otp) => {
        set({ isLoading: true, error: null });
        try {
          const result = await authApi.verifyOtp({ email, otp });
          set({
            user: result.user,
            token: result.token,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          });
          // Hydrate wishlist from backend after OTP verification (first login)
          void useWishlistStore.getState().hydrate();
        } catch (err: unknown) {
          const apiErr = err as { errors?: Record<string, string[]>; message?: string };
          const firstError = apiErr?.errors
            ? Object.values(apiErr.errors).flat()[0]
            : undefined;
          const message = firstError ?? apiErr?.message ?? "Verification failed. Please try again.";
          set({ isLoading: false, error: message });
          throw err;
        }
      },

      resendOtp: async (email) => {
        await authApi.resendOtp(email);
      },

      logout: async () => {
        try {
          await authApi.logout();
        } catch {
          // Ignore network errors — clear state regardless
        } finally {
          set({ user: null, token: null, isAuthenticated: false, error: null });
          useWishlistStore.getState().clear();
        }
      },

      fetchMe: async () => {
        const { token } = get();
        if (!token) return;
        set({ isLoading: true });
        try {
          const result = await authApi.me();
          set({ user: result.user, isAuthenticated: true, isLoading: false });
          void useWishlistStore.getState().hydrate();
        } catch {
          // Token invalid — clear auth state
          set({ user: null, token: null, isAuthenticated: false, isLoading: false });
          useWishlistStore.getState().clear();
        }
      },

      setUser: (user) => set({ user }),

      clearError: () => set({ error: null }),

      resendVerification: async () => {
        set({ isLoading: true, error: null });
        try {
          await authApi.resendVerification();
          set({ isLoading: false });
        } catch (err: unknown) {
          const message = (err as { message?: string })?.message ?? "Failed to resend.";
          set({ isLoading: false, error: message });
          throw err;
        }
      },
    }),
    {
      name: "winny-auth",
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);

// ---------------------------------------------------------------------------
// Convenience hooks
// ---------------------------------------------------------------------------

/** Returns true if the user is authenticated AND is an admin */
export const useRequireAdmin = () => {
  const { isAuthenticated, isAdmin } = useAuthStore();
  return isAuthenticated && isAdmin();
};

/** Returns true if the user is authenticated AND has verified their email */
export const useRequireVerified = () => {
  const { isAuthenticated, isEmailVerified } = useAuthStore();
  return isAuthenticated && isEmailVerified();
};
