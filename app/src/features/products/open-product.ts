import * as WebBrowser from "expo-web-browser";

import type { Product } from "@/api/generated/models";

export async function openProduct(product: Product): Promise<boolean> {
  const value = product.affiliateUrl ?? product.url;

  try {
    const url = new URL(value);

    if (url.protocol !== "https:") return false;

    await WebBrowser.openBrowserAsync(url.toString(), {
      enableBarCollapsing: true,
      enableDefaultShareMenuItem: true,
    });

    return true;
  } catch {
    return false;
  }
}
