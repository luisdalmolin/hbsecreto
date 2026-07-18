export interface PaymentReturn {
  orderId: number;
  result: "success" | "pending" | "failure";
}

const RETURN_RESULTS = new Set<PaymentReturn["result"]>([
  "success",
  "pending",
  "failure",
]);

export function normalizePaymentReturn(
  hostname: string | null,
  path: string | null,
  rawOrderId: string | string[] | undefined,
): PaymentReturn | undefined {
  const pathParts = [
    hostname,
    ...(path?.split("/").filter(Boolean) ?? []),
  ].filter((part): part is string => Boolean(part));
  const result = pathParts[0] === "payments" ? pathParts[1] : undefined;
  const orderId = Number(
    Array.isArray(rawOrderId) ? rawOrderId[0] : rawOrderId,
  );

  if (
    !result ||
    !RETURN_RESULTS.has(result as PaymentReturn["result"]) ||
    !Number.isInteger(orderId) ||
    orderId < 1
  ) {
    return undefined;
  }

  return { orderId, result: result as PaymentReturn["result"] };
}

export function isSafeCheckoutUrl(value: string): boolean {
  try {
    const url = new URL(value);

    return (
      url.protocol === "https:" &&
      Boolean(url.hostname) &&
      url.username === "" &&
      url.password === ""
    );
  } catch {
    return false;
  }
}
