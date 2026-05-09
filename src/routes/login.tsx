import { createFileRoute, Link } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { useState } from "react";
import { Mail, Lock } from "lucide-react";
import { AuthShell } from "@/components/AuthShell";

export const Route = createFileRoute("/login")({
  head: () => ({
    meta: [
      { title: "Sign in — Winny Land" },
      { name: "description", content: "Sign in to your Winny Land account." },
    ],
  }),
  component: LoginPage,
});

function LoginPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  return (
    <AuthShell title="Welcome back" subtitle="Sign in to continue your Winny Land journey">
      <form
        onSubmit={(e) => {
          e.preventDefault();
        }}
        className="space-y-4"
      >
        <Field icon={<Mail className="h-4 w-4" />} type="email" placeholder="Email" value={email} onChange={setEmail} />
        <Field icon={<Lock className="h-4 w-4" />} type="password" placeholder="Password" value={password} onChange={setPassword} />

        <motion.button
          whileHover={{ scale: 1.01 }}
          whileTap={{ scale: 0.99 }}
          type="submit"
          className="neon-glow w-full rounded-full bg-primary py-3 text-sm font-medium text-primary-foreground"
        >
          Sign in
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
}: {
  icon: React.ReactNode;
  type: string;
  placeholder: string;
  value: string;
  onChange: (v: string) => void;
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
        className="w-full rounded-full border border-border bg-secondary/40 py-3 pl-11 pr-4 text-sm outline-none transition focus:border-pink focus:bg-background"
      />
    </div>
  );
}
