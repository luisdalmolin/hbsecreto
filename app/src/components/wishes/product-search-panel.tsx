import { Search } from "lucide-react-native";
import { useTranslation } from "react-i18next";
import { ActivityIndicator, View } from "react-native";

import type { Product } from "@/api/generated/models";
import { FormField } from "@/components/common/form-field";
import { Button, Card, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

import { ProductDetails } from "./product-details";

interface ProductSearchPanelProps {
  query: string;
  results: Product[];
  searching: boolean;
  searched: boolean;
  error?: string;
  onQueryChange(value: string): void;
  onSearch(): void;
  onSelect(product: Product): void;
  onCancel(): void;
}

export function ProductSearchPanel({
  query,
  results,
  searching,
  searched,
  error,
  onQueryChange,
  onSearch,
  onSelect,
  onCancel,
}: ProductSearchPanelProps) {
  const { t } = useTranslation();

  return (
    <View className="gap-3 rounded-tile border border-hairline bg-cloud/60 p-3">
      <FormField
        label={t("products.searchLabel")}
        placeholder={t("products.searchPlaceholder")}
        value={query}
        onChangeText={onQueryChange}
        onSubmitEditing={onSearch}
        returnKeyType="search"
        maxLength={100}
        editable={!searching}
        error={error}
      />
      <View className="flex-row gap-2">
        <Button
          className="flex-1"
          label={searching ? t("products.searching") : t("products.search")}
          leftIcon={
            searching ? (
              <ActivityIndicator color={palette.white} />
            ) : (
              <Search color={palette.white} size={18} />
            )
          }
          disabled={searching}
          onPress={onSearch}
        />
        <Button
          label={t("common.cancel")}
          variant="light"
          disabled={searching}
          onPress={onCancel}
        />
      </View>

      {searched && results.length === 0 && !error ? (
        <Text variant="caption">{t("products.empty")}</Text>
      ) : null}

      {results.map((product) => (
        <Card
          key={product.id}
          shadow="none"
          className="gap-3 border border-hairline p-3"
        >
          <ProductDetails product={product} />
          <Button
            label={t("products.choose")}
            size="sm"
            onPress={() => onSelect(product)}
          />
        </Card>
      ))}
    </View>
  );
}
