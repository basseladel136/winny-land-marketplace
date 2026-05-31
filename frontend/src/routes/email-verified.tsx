/**
 * Email Verification Result Page
 *
 * The backend redirects here after the user clicks the verification link in
 * their email. The `status` query parameter carries the result:
 *   - success           → email verified successfully
 *   - already_verified  → was already verified
 *   - invalid           → link expired or tampered
 */
import { createFileRoute, Link, useSearch } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { CheckCircle2, XCircle, MailCheck, Loader2 } from "lucide-react";
import { useEffect, useState } from "react";
import { useAuthStore } from "@/lib/authStore";
import { AuthShell } from "@/components/AuthShell";

type VerifyStatus = "success" | "already_verified" | "invalid" | "loading";

export const Route = createFileRoute("/email-verified")({
  validateSearch: (search: Record<string, unknown>) => ({
    status: (search.status as string) ?? "loading",
  }),
  head: () => ({
    meta: [{ title: "Email Verified — Winny Land" }],
  }),
  component: EmailVerifiedPage,
});

function EmailVerifiedPage() {
  const { status } = useSearch({ from: "/email-verified" });
  const { fetchMe, isAuthenticated } = useAuthStore();
  const [refreshed, setRefreshed] = useState(false);

  // Refresh the user profile so the frontend knows email is now verified
  useEffect(() => {
    if ((status === "success" || status === "already_verified") && isAuthenticated && !refreshed) {
      fetchMe().finally(() => setRefreshed(true));
    }
  }, [status, isAuthenticated, refreshed, fetchMe]);

  const resolvedStatus: VerifyStatus =
    ["success", "already_verified", "invalid"].includes(status)
      ? (status as VerifyStatus)
      : "loading";

  return (
    <AuthShell title={getTitle(resolvedStatus)} subtitle={getSubtitle(resolvedStatus)}>
      <motion.div
        initial={{ opacity: 0, y: 12 }}
        animate={{ opacity: 1, y: 0 }}
        className="flex flex-col items-center gap-6 text-center"
      >
        {resolvedStatus === "loading" && (
          <Loader2 className="h-12 w-12 animate-spin text-pink" />
        )}
        {(resolvedStatus === "success" || resolvedStatus === "already_verified") && (
          <div className="flex h-20 w-20 items-center justify-center rounded-full bg-green-500/10">
            <CheckCircle2 className="h-10 w-10 text-green-500" />
          </div>
        )}
        {resolvedStatus === "invalid" && (
          <div className="flex h-20 w-20 items-center justify-center rounded-full bg-destructive/10">
            <XCircle className="h-10 w-10 text-destructive" />
          </div>
        )}

        <div className="flex flex-col gap-3 w-full">
          {(resolvedStatus === "success" || resolvedStatus === "already_verified") && (
            <Link
              to="/marketplace"
              className="neon-glow w-full rounded-full bg-primary py-3 text-sm font-medium text-primary-foreground text-center block"
            >
              Start shopping
            </Link>
          )}
          {resolvedStatus === "invalid" && (
            <>
              <p className="text-sm text-muted-foreground">
                Verification links expire after 24 hours. Request a new one after signing in.
              </p>
              <Link
                to="/login"
                className="w-full rounded-full border border-border py-3 text-sm font-medium text-center block hover:bg-secondary/40 transition"
              >
                Sign in to resend
              </Link>
            </>
          )}
          {resolvedStatus !== "invalid" && (
            <Link
              to="/login"
              className="text-xs text-muted-foreground underline-offset-4 hover:underline"
            >
              Back to sign in
            </Link>
          )}
        </div>
      </motion.div>
    </AuthShell>
  );
}

function MailIcon() {
  return (
    <div className="flex h-20 w-20 items-center justify-center rounded-full bg-pink/10">
      <MailCheck className="h-10 w-10 text-pink" />
    </div>
  );
}
void MailIcon; // suppress unused warning

function getTitle(status: VerifyStatus): string {
  switch (status) {
    case "success":          return "Email verified!";
    case "already_verified": return "Already verified";
    case "invalid":          return "Link expired";
    default:                 return "Verifying…";
  }
}

function getSubtitle(status: VerifyStatus): string {
  switch (status) {
    case "success":          return "Your account is now active. Welcome to Winny Land!";
    case "already_verified": return "Your email was already verified. You're all set.";
    case "invalid":          return "This verification link is invalid or has expired.";
    default:                 return "Please wait while we verify your email.";
  }
}
