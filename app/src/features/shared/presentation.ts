import type { TFunction } from "i18next";

import { normalizeApiError } from "@/api/errors";

const brlFormatter = new Intl.NumberFormat("pt-BR", {
  style: "currency",
  currency: "BRL",
});
const decimalFormatter = new Intl.NumberFormat("pt-BR", {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});
const dateFormatter = new Intl.DateTimeFormat("pt-BR", { dateStyle: "medium" });

export function parseRouteId(
  value: string | string[] | undefined,
): number | undefined {
  const parsed = Number(Array.isArray(value) ? value[0] : value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
}

export function initials(name: string): string {
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? "")
    .join("");
}

export function formatCurrency(
  cents: number | null | undefined,
  currency = "BRL",
): string | undefined {
  if (cents === null || cents === undefined) return undefined;
  return currency === "BRL"
    ? brlFormatter.format(cents / 100)
    : `${currency} ${decimalFormatter.format(cents / 100)}`;
}

export function formatDate(
  value: string | null | undefined,
): string | undefined {
  if (!value) return undefined;
  const date = new Date(value.includes("T") ? value : `${value}T12:00:00`);
  return Number.isNaN(date.valueOf()) ? value : dateFormatter.format(date);
}

export function apiErrorMessage(error: unknown, t: TFunction): string {
  const normalized = normalizeApiError(error);
  if (normalized.message) return normalized.message;
  if (normalized.kind === "network") return t("common.errors.network");
  if (normalized.kind === "forbidden") return t("common.errors.forbidden");
  if (normalized.kind === "notFound") return t("common.errors.notFound");
  if (normalized.kind === "conflict") return t("common.errors.conflict");
  if (normalized.kind === "rateLimited") return t("common.errors.rateLimited");
  return t("common.errors.generic");
}
