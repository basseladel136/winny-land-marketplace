import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { motion, AnimatePresence } from "framer-motion";
import { useEffect, useRef, useState } from "react";
import { AlertCircle, Loader2, MailCheck } from "lucide-react";
import { AuthShell } from "@/components/AuthShell";
import { useAuthStore } from "@/lib/authStore";

const OTP_LENGTH = 6;
const RESEND_COOLDOWN = 30; // seconds

export const Route = createFileRoute("/verify-email")({
  head: () => ({
    meta: [
      { title: "Verify Your Email — Winny Land" },
      { name: "description", content: "Enter the verification code we sent to your email." },
    ],
  }),
  // Read the email from the ?email= query param.
  validateSearch: (search: Record<string, unknown>): { email?: string } => ({
    email: typeof search.email === "string" ? search.email : undefined,
  }),
  component: VerifyEmailPage,
});

function VerifyEmailPage() {
  const navigate = useNavigate();
  const { email } = Route.useSearch();
  const { verifyOtp, resendOtp, isLoading, error, clearError } = useAuthStore();

  const [digits, setDigits] = useState<string[]>(Array(OTP_LENGTH).fill(""));
  const [cooldown, setCooldown] = useState(0);
  const [resent, setResent] = useState(false);
  const inputs = useRef<(HTMLInputElement | null)[]>([]);

  // No email in the URL → nothing to verify, send them to register.
  useEffect(() => {
    if (!email) navigate({ to: "/register" });
  }, [email, navigate]);

  // Resend cooldown timer.
  useEffect(() => {
    if (cooldown <= 0) return;
    const t = setTimeout(() => setCooldown((c) => c - 1), 1000);
    return () => clearTimeout(t);
  }, [cooldown]);

  const setDigit = (index: number, value: string) => {
    const clean = value.replace(/\D/g, "");
    if (!clean) {
      setDigits((prev) => prev.map((d, i) => (i === index ? "" : d)));
      return;
    }
    // Support paste of the whole code into any box.
    if (clean.length > 1) {
      const chars = clean.slice(0, OTP_LENGTH).split("");
      const next = Array(OTP_LENGTH).fill("");
      chars.forEach((c, i) => (next[i] = c));
      setDigits(next);
      const last = Math.min(chars.length, OTP_LENGTH) - 1;
      inputs.current[last]?.focus();
      return;
    }
    setDigits((prev) => prev.map((d, i) => (i === index ? clean : d)));
    if (index < OTP_LENGTH - 1) inputs.current[index + 1]?.focus();
  };

  const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Backspace" && !digits[index] && index > 0) {
      inputs.current[index - 1]?.focus();
    }
  };

  const submit = async (e?: React.FormEvent) => {
    e?.preventDefault();
    clearError();
    const code = digits.join("");
    if (code.length !== OTP_LENGTH || !email) return;
    try {
      await verifyOtp(email, code);
      navigate({ to: "/marketplace" });
    } catch {
      // Error is set in the store; reset the inputs for another try.
      setDigits(Array(OTP_LENGTH).fill(""));
      inputs.current[0]?.focus();
    }
  };

  const handleResend = async () => {
    if (!email || cooldown > 0) return;
    clearError();
    try {
      await resendOtp(email);
      setResent(true);
      setCooldown(RESEND_COOLDOWN);
    } catch {
      // ignore — backend always returns success for privacy
    }
  };

  const complete = digits.every((d) => d !== "");

  return (
    <AuthShell title="Verify your email" subtitle="Enter the 6-digit code we emailed you">
      <form onSubmit={submit} className="space-y-5">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-pink/10">
          <MailCheck className="h-8 w-8 text-pink" />
        </div>

        <p className="text-center text-sm text-muted-foreground">
          We sent a code to{" "}
          <span className="font-medium text-foreground">{email}</span>.
        </p>

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

        <div className="flex justify-center gap-2" dir="ltr">
          {digits.map((digit, i) => (
            <input
              key={i}
              ref={(el) => {
                inputs.current[i] = el;
              }}
              type="text"
              inputMode="numeric"
              autoComplete={i === 0 ? "one-time-code" : "off"}
              maxLength={OTP_LENGTH}
              value={digit}
              onChange={(e) => setDigit(i, e.target.value)}
              onKeyDown={(e) => handleKeyDown(i, e)}
              disabled={isLoading}
              autoFocus={i === 0}
              className="h-14 w-12 rounded-2xl border border-border bg-secondary/40 text-center text-xl font-semibold outline-none transition focus:border-pink focus:bg-background disabled:opacity-60"
            />
          ))}
        </div>

        <motion.button
          whileHover={{ scale: 1.01 }}
          whileTap={{ scale: 0.99 }}
          type="submit"
          disabled={isLoading || !complete}
          className="neon-glow flex w-full items-center justify-center gap-2 rounded-full bg-primary py-3 text-sm font-medium text-primary-foreground disabled:cursor-not-allowed disabled:opacity-70"
        >
          {isLoading ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              Verifying…
            </>
          ) : (
            "Verify & continue"
          )}
        </motion.button>

        <div className="text-center text-xs text-muted-foreground">
          {resent && cooldown > 0 && (
            <p className="mb-1 text-green-500">A new code is on its way.</p>
          )}
          Didn't get a code?{" "}
          {cooldown > 0 ? (
            <span>Resend in {cooldown}s</span>
          ) : (
            <button
              type="button"
              onClick={handleResend}
              className="text-pink underline-offset-4 hover:underline"
            >
              Resend code
            </button>
          )}
        </div>

        <p className="text-center text-xs text-muted-foreground">
          Wrong email?{" "}
          <Link to="/register" className="text-pink underline-offset-4 hover:underline">
            Start over
          </Link>
        </p>
      </form>
    </AuthShell>
  );
}
