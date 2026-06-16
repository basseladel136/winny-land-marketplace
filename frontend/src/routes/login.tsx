import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { motion, AnimatePresence } from "framer-motion";
import { useState } from "react";
import { Mail, Lock, AlertCircle, Loader2 } from "lucide-react";
import { AuthShell } from "@/components/AuthShell";
import { useAuthStore } from "@/lib/authStore";

export const Route = createFileRoute("/login")({
  head: () => ({
    meta: [
      { title: "Sign In — Winny Land" },
      { name: "description", content: "Sign in to your Winny Land account." },
    ],
  }),
  component: LoginPage,
});

function LoginPage() {
  const navigate = useNavigate();
  const { login, isLoading, error, clearError } = useAuthStore();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    clearError();

    try {
      await login(email, password);

      // Redirect based on role — admin goes to management panel, customers to marketplace
      const { user } = useAuthStore.getState();
      if (user?.role === "admin") {
        navigate({ to: "/admin" });
      } else {
        navigate({ to: "/marketplace" });
      }
    } catch (err) {
      // Unverified accounts get a fresh OTP — route them to the verification screen.
      const apiErr = err as { data?: { email_unverified?: boolean } };
      if (apiErr?.data?.email_unverified) {
        navigate({ to: "/verify-email", search: { email } });
      }
      // Otherwise the error is already set in the store
    }
  };

  return (
    <AuthShell title="Welcome back" subtitle="Sign in to continue your Winny Land journey">
      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Error banner */}
        <AnimatePresence>
          {error && (
            <motion.div
              initial={{ opacity: 0, y: -8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -8 }}
              className="flex items-start gap-2 rounded-2xl border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive"
            >
              <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
              <span>{error}</span>
            </motion.div>
          )}
        </AnimatePresence>

        <Field
          icon={<Mail className="h-4 w-4" />}
          type="email"
          placeholder="Email"
          value={email}
          onChange={setEmail}
          disabled={isLoading}
          autoComplete="email"
        />
        <Field
          icon={<Lock className="h-4 w-4" />}
          type="password"
          placeholder="Password"
          value={password}
          onChange={setPassword}
          disabled={isLoading}
          autoComplete="current-password"
        />

        <motion.button
          whileHover={{ scale: 1.01 }}
          whileTap={{ scale: 0.99 }}
          type="submit"
          disabled={isLoading}
          className="neon-glow flex w-full items-center justify-center gap-2 rounded-full bg-primary py-3 text-sm font-medium text-primary-foreground disabled:cursor-not-allowed disabled:opacity-70"
        >
          {isLoading ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              Signing in…
            </>
          ) : (
            "Sign in"
          )}
        </motion.button>

        <p className="pt-2 text-center text-xs text-muted-foreground">
          New to Winny Land?{" "}
          <Link to="/register" className="text-pink underline-offset-4 hover:underline">
            Create an account
          </Link>
        </p>
      </form>
    </AuthShell>
  );
}

function Field({
  icon,
  type,
  placeholder,
  value,
  onChange,
  disabled,
  autoComplete,
}: {
  icon: React.ReactNode;
  type: string;
  placeholder: string;
  value: string;
  onChange: (v: string) => void;
  disabled?: boolean;
  autoComplete?: string;
}) {
  return (
    <div className="relative">
      <span className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground">
        {icon}
      </span>
      <input
        type={type}
        required
        placeholder={placeholder}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
        autoComplete={autoComplete}
        className="w-full rounded-full border border-border bg-secondary/40 py-3 pl-11 pr-4 text-sm outline-none transition focus:border-pink focus:bg-background disabled:cursor-not-allowed disabled:opacity-60"
      />
    </div>
  );
}
