import type { DashboardEdition } from "@/api/generated/models";
import { formatCurrency, formatDate } from "@/features/shared/presentation";

export function activeEditionLabels(edition: DashboardEdition): {
  eventDate?: string;
  budget?: string;
} {
  return {
    eventDate: formatDate(edition.eventDate),
    budget: formatCurrency(edition.budgetCents, edition.currency),
  };
}
