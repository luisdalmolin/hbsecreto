import * as Linking from "expo-linking";

import { getOrder } from "@/api/generated/orders/orders";
import type { Order } from "@/api/generated/models";

import { normalizePaymentReturn, type PaymentReturn } from "./payment-return";

const POLL_DELAY_MS = 1_500;
const POLL_ATTEMPTS = 5;

export function parsePaymentReturnUrl(url: string): PaymentReturn | undefined {
  const parsed = Linking.parse(url);
  const rawOrderId = parsed.queryParams?.orderId;

  return normalizePaymentReturn(
    parsed.hostname,
    parsed.path,
    Array.isArray(rawOrderId)
      ? rawOrderId.map(String)
      : rawOrderId === undefined
        ? undefined
        : String(rawOrderId),
  );
}

export async function pollOrder(
  orderId: number,
  signal?: AbortSignal,
): Promise<Order> {
  let order = await getOrder(orderId, { signal });

  for (
    let attempt = 1;
    order.status === "pending" && attempt < POLL_ATTEMPTS;
    attempt += 1
  ) {
    await wait(POLL_DELAY_MS, signal);
    order = await getOrder(orderId, { signal });
  }

  return order;
}

function wait(milliseconds: number, signal?: AbortSignal): Promise<void> {
  return new Promise((resolve, reject) => {
    if (signal?.aborted) {
      reject(signal.reason);
      return;
    }

    const timeout = setTimeout(resolve, milliseconds);
    signal?.addEventListener(
      "abort",
      () => {
        clearTimeout(timeout);
        reject(signal.reason);
      },
      { once: true },
    );
  });
}
