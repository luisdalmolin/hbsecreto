import assert from "node:assert/strict";
import test from "node:test";

import {
  isSafeCheckoutUrl,
  normalizePaymentReturn,
} from "../src/features/orders/payment-return.ts";

test("normalizes the Expo Linking host form used by Mercado Pago callbacks", () => {
  assert.deepEqual(normalizePaymentReturn("payments", "success", "42"), {
    orderId: 42,
    result: "success",
  });
});

test("normalizes the equivalent triple-slash path form", () => {
  assert.deepEqual(normalizePaymentReturn(null, "payments/pending", "42"), {
    orderId: 42,
    result: "pending",
  });
});

test("rejects unrecognized callback paths and invalid order identifiers", () => {
  assert.equal(normalizePaymentReturn("payments", "unknown", "42"), undefined);
  assert.equal(
    normalizePaymentReturn("payments", "failure", "invalid"),
    undefined,
  );
});

test("allows only valid credential-free HTTPS checkout URLs", () => {
  assert.equal(
    isSafeCheckoutUrl(
      "https://www.mercadopago.com.br/checkout/v1/redirect?id=42",
    ),
    true,
  );
  assert.equal(
    isSafeCheckoutUrl("http://www.mercadopago.com.br/checkout"),
    false,
  );
  assert.equal(isSafeCheckoutUrl("javascript:alert(1)"), false);
  assert.equal(
    isSafeCheckoutUrl("https://user:secret@example.test/checkout"),
    false,
  );
  assert.equal(isSafeCheckoutUrl("not a URL"), false);
});
