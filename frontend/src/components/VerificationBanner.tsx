/**
 * VerificationBanner
 *
 * Shows a banner for signed-in users who haven't verified their email yet.
 * Includes a "Resend email" button with loading/success states.
 *
 * Usage:
 *   <VerificationBanner />
 *   // Place at the top of any page that requires email verification.
 */

import { useState } from "react";
import { MailWarning, CheckCircle2, Loader2, X } from "lucide-react";
import { useAuthStore } from "@/lib/authStore";

export function VerificationBanner() {
  const { isAuthenticated, isEmailVerified, resendVerification } = useAuthStore();
  const [status, setStatus] = useState<"idle" | "sending" | "sent" | "error">("idle");
  const [dismissed, setDismissed] = useState(false);

  // Only show for signed-in, unverified users
  if (!isAuthenticated || isEmailVerified() || dismissed) return null;

  const handleResend = async () => {
    setStatus("sending");
    try {
      await resendVerification();
      setStatus("sent");
    } catch {
      setStatus("error");
    }
  };

  return (
    <div className="relative z-50 flex items-center justify-between gap-4 border-b border-amber-200/40 bg-amber-50/90 px-4 py-3 text-sm backdrop-blur dark:border-amber-800/40 dark:bg-amber-900/30">
      <div className="flex items-center gap-2 text-amber-800 dark:text-amber-200">
        <MailWarning className="h-4 w-4 shrink-0" />
        <span>
          Please verify your email address to unlock all features.
        </span>
      </div>

      <div className="flex items-center gap-3 shrink-0">
        {status === "idle" && (
          <button
            onClick={handleResend}
            className="rounded-full border border-amber-600/40 px-3 py-1 text-xs font-medium text-amber-700 transition hover:bg-amber-100 dark:text-amber-300 dark:hover:bg-amber-800/40"
          >
            Resend email
          </button>
        )}
        {status === "sending" && (
          <span className="flex items-center gap-1.5 text-xs text-amber-700 dark:text-amber-300">
            <Loader2 className="h-3 w-3 animate-spin" />
            Sending…
          </span>
        )}
        {status === "sent" && (
          <span className="flex items-center gap-1.5 text-xs text-green-700 dark:text-green-400">
            <CheckCircle2 className="h-3 w-3" />
            Sent! Check your inbox.
          </span>
        )}
        {status === "error" && (
          <span className="text-xs text-destructive">
            Failed to send. Try again later.
          </span>
        )}

        <button
          onClick={() => setDismissed(true)}
          className="rounded-full p-1 text-amber-600 transition hover:bg-amber-100 dark:text-amber-400 dark:hover:bg-amber-800/40"
          aria-label="Dismiss"
        >
          <X className="h-3.5 w-3.5" />
        </button>
      </div>
    </div>
  );
}
