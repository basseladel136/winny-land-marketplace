import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { motion, AnimatePresence } from "framer-motion";
import { useState } from "react";
import { Mail, Lock, User, AlertCircle, Loader2 } from "lucide-react";
import { AuthShell } from "@/components/AuthShell";
import { useAuthStore } from "@/lib/authStore";

export const Route = createFileRoute("/register")({
  head: () => ({
    meta: [
      { title: "Create Account — Winny Land" },
      { name: "description", content: "Join Winny Land and start your dreamy journey." },
    ],
  }),
  component: RegisterPage,
});

function RegisterPage() {
  const navigate = useNavigate();
  const { register, isLoading, error, clearError } = useAuthStore();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirm, setPasswordConfirm] = useState("");

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    clearError();

    if (password !== passwordConfirm) {
      useAuthStore.setState({ error: "Passwords do not match." });
      return;
    }

    try {
      const result = await register({
        name,
        email,
        password,
        password_confirmation: passwordConfirm,
      });
      // Account created but not active yet — send the user to the OTP screen.
      navigate({ to: "/verify-email", search: { email: result.email } });
    } catch {
      // Error is already set in the store
    }
  };

  return (
    <AuthShell title="Create your account" subtitle="Join the dreamy world of Winny Land">
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
          icon={<User className="h-4 w-4" />}
          type="text"
          placeholder="Full name"
          value={name}
          onChange={setName}
          disabled={isLoading}
          autoComplete="name"
        />
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
          placeholder="Password (min. 8 chars, mixed case + numbers)"
          value={password}
          onChange={setPassword}
          disabled={isLoading}
          autoComplete="new-password"
        />
        <Field
          icon={<Lock className="h-4 w-4" />}
          type="password"
          placeholder="Confirm password"
          value={passwordConfirm}
          onChange={setPasswordConfirm}
          disabled={isLoading}
          autoComplete="new-password"
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
              Creating account…
            </>
          ) : (
            "Create account"
          )}
        </motion.button>

        <p className="pt-2 text-center text-xs text-muted-foreground">
          Already have an account?{" "}
          <Link to="/login" className="text-pink underline-offset-4 hover:underline">
            Sign in
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
