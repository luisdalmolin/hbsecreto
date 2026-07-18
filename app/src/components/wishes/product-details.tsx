import { Image } from "expo-image";
import { Package } from "lucide-react-native";
import { View } from "react-native";

import type { Product } from "@/api/generated/models";
import { Text } from "@/components/ui";
import { formatCurrency } from "@/features/shared/presentation";
import { palette } from "@/theme/tokens";

interface ProductDetailsProps {
  product: Product;
}

export function ProductDetails({ product }: ProductDetailsProps) {
  const price = formatCurrency(product.priceCents, product.currency);

  return (
    <View className="flex-row items-center gap-3">
      {product.imageUrl ? (
        <Image
          source={product.imageUrl}
          className="h-16 w-16 rounded-tile bg-cloud"
          contentFit="cover"
          transition={150}
          accessibilityLabel={product.title}
        />
      ) : (
        <View className="h-16 w-16 items-center justify-center rounded-tile bg-mint-tint">
          <Package color={palette.mint} size={25} />
        </View>
      )}
      <View className="min-w-0 flex-1 gap-1">
        <Text variant="bodyBold" numberOfLines={2}>
          {product.title}
        </Text>
        {price ? (
          <Text variant="label" className="text-mint-deep">
            {price}
          </Text>
        ) : null}
      </View>
    </View>
  );
}
