import { ApiError } from "./http";

export interface NormalizedApiError {
  kind:
    | "network"
    | "unauthorized"
    | "forbidden"
    | "notFound"
    | "conflict"
    | "rateLimited"
    | "validation"
    | "unknown";
  message?: string;
  fields?: Record<string, string>;
}

export function normalizeApiError(error: unknown): NormalizedApiError {
  if (error instanceof ApiError) {
    const message = readMessage(error.payload);

    if (error.status === 401) return { kind: "unauthorized", message };
    if (error.status === 403) return { kind: "forbidden", message };
    if (error.status === 404) return { kind: "notFound", message };
    if (error.status === 409) return { kind: "conflict", message };
    if (error.status === 429) return { kind: "rateLimited", message };
    if (error.status === 422)
      return { kind: "validation", message, fields: readFields(error.payload) };

    return { kind: "unknown", message };
  }

  if (error instanceof TypeError) {
    return { kind: "network" };
  }

  return { kind: "unknown" };
}

function readMessage(payload: unknown): string | undefined {
  if (
    typeof payload === "object" &&
    payload !== null &&
    "message" in payload &&
    typeof payload.message === "string"
  ) {
    return payload.message;
  }

  return undefined;
}

function readFields(payload: unknown): Record<string, string> | undefined {
  if (
    typeof payload !== "object" ||
    payload === null ||
    !("errors" in payload) ||
    typeof payload.errors !== "object" ||
    payload.errors === null
  ) {
    return undefined;
  }

  return Object.fromEntries(
    Object.entries(payload.errors).flatMap(([key, value]) => {
      const first = Array.isArray(value)
        ? value.find((item): item is string => typeof item === "string")
        : undefined;
      return first ? [[key, first]] : [];
    }),
  );
}
