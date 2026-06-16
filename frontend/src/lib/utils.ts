import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

const egpFormatter = new Intl.NumberFormat("en-EG", {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

/** Format a numeric amount as Egyptian Pounds, e.g. `1,250.00 EGP`. */
export function formatPrice(amount: number | string): string {
  const value = typeof amount === "string" ? Number(amount) : amount;
  return `${egpFormatter.format(Number.isFinite(value) ? value : 0)} EGP`;
}
