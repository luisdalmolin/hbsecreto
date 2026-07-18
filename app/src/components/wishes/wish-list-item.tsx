import {
  ChevronDown,
  ChevronUp,
  ExternalLink,
  Pencil,
  Search,
  Trash2,
  X,
} from "lucide-react-native";
import type { ReactNode } from "react";
import { useTranslation } from "react-i18next";
import { ActivityIndicator, View } from "react-native";

import type { Product, Wish } from "@/api/generated/models";
import { FormField } from "@/components/common/form-field";
import { Button, Card, IconButton, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

import { ProductDetails } from "./product-details";

interface WishListItemProps {
  wish: Wish;
  index: number;
  count: number;
  locked: boolean;
  editing: boolean;
  editingDescription: string;
  editingProduct: Product | null;
  productSearchPanel?: ReactNode;
  fieldError?: string;
  controlsDisabled: boolean;
  busy?: "up" | "down" | "update" | "delete";
  onEditingDescriptionChange(value: string): void;
  onChooseProduct(): void;
  onRemoveEditingProduct(): void;
  onOpenProduct(product: Product): void;
  onBeginEdit(): void;
  onCancelEdit(): void;
  onSaveEdit(): void;
  onDelete(): void;
  onMoveUp(): void;
  onMoveDown(): void;
}

export function WishListItem({
  wish,
  index,
  count,
  locked,
  editing,
  editingDescription,
  editingProduct,
  productSearchPanel,
  fieldError,
  controlsDisabled,
  busy,
  onEditingDescriptionChange,
  onChooseProduct,
  onRemoveEditingProduct,
  onOpenProduct,
  onBeginEdit,
  onCancelEdit,
  onSaveEdit,
  onDelete,
  onMoveUp,
  onMoveDown,
}: WishListItemProps) {
  const { t } = useTranslation();

  return (
    <Card className="gap-3 p-4">
      {!locked && editing ? (
        <>
          <FormField
            label={t("wishes.edit")}
            value={editingDescription}
            onChangeText={onEditingDescriptionChange}
            maxLength={500}
            multiline
            editable={busy !== "update"}
            error={fieldError}
          />
          {editingProduct ? (
            <View className="gap-3 rounded-tile border border-hairline p-3">
              <ProductDetails product={editingProduct} />
              <View className="flex-row flex-wrap gap-2">
                <Button
                  className="flex-1"
                  label={t("products.change")}
                  variant="light"
                  size="sm"
                  leftIcon={<Search color={palette.mintDeep} size={16} />}
                  disabled={Boolean(busy)}
                  onPress={onChooseProduct}
                />
                <IconButton
                  accessibilityLabel={t("products.remove")}
                  disabled={Boolean(busy)}
                  onPress={onRemoveEditingProduct}
                >
                  <X color={palette.pink} size={18} />
                </IconButton>
              </View>
            </View>
          ) : (
            <Button
              label={t("products.addOptional")}
              variant="light"
              size="sm"
              leftIcon={<Search color={palette.mintDeep} size={16} />}
              disabled={Boolean(busy)}
              onPress={onChooseProduct}
            />
          )}
          {productSearchPanel}
          <View className="flex-row gap-2">
            <Button
              className="flex-1"
              label={t("common.cancel")}
              variant="light"
              disabled={Boolean(busy)}
              onPress={onCancelEdit}
            />
            <Button
              className="flex-1"
              label={t("common.save")}
              leftIcon={
                busy === "update" ? (
                  <ActivityIndicator color={palette.white} />
                ) : undefined
              }
              disabled={Boolean(busy)}
              onPress={onSaveEdit}
            />
          </View>
        </>
      ) : (
        <>
          <Text>{wish.description}</Text>
          {wish.product ? (
            <View className="gap-3 rounded-tile bg-cloud/70 p-3">
              <ProductDetails product={wish.product} />
              <Button
                label={t("products.open")}
                variant="light"
                size="sm"
                leftIcon={<ExternalLink color={palette.mintDeep} size={16} />}
                onPress={() => {
                  if (wish.product) onOpenProduct(wish.product);
                }}
              />
            </View>
          ) : null}
          {!locked ? (
            <View className="flex-row justify-end gap-2">
              <IconButton
                accessibilityLabel={t("wishes.moveUp")}
                disabled={controlsDisabled || index === 0}
                className={
                  controlsDisabled || index === 0 ? "opacity-40" : undefined
                }
                onPress={onMoveUp}
              >
                {busy === "up" ? (
                  <ActivityIndicator color={palette.mintDeep} />
                ) : (
                  <ChevronUp color={palette.mintDeep} size={20} />
                )}
              </IconButton>
              <IconButton
                accessibilityLabel={t("wishes.moveDown")}
                disabled={controlsDisabled || index === count - 1}
                className={
                  controlsDisabled || index === count - 1
                    ? "opacity-40"
                    : undefined
                }
                onPress={onMoveDown}
              >
                {busy === "down" ? (
                  <ActivityIndicator color={palette.mintDeep} />
                ) : (
                  <ChevronDown color={palette.mintDeep} size={20} />
                )}
              </IconButton>
              <IconButton
                accessibilityLabel={t("wishes.edit")}
                disabled={controlsDisabled}
                className={controlsDisabled ? "opacity-40" : undefined}
                onPress={onBeginEdit}
              >
                <Pencil color={palette.mintDeep} size={18} />
              </IconButton>
              <IconButton
                accessibilityLabel={t("wishes.delete")}
                disabled={controlsDisabled}
                className={controlsDisabled ? "opacity-40" : undefined}
                onPress={onDelete}
              >
                {busy === "delete" ? (
                  <ActivityIndicator color={palette.pink} />
                ) : (
                  <Trash2 color={palette.pink} size={18} />
                )}
              </IconButton>
            </View>
          ) : null}
        </>
      )}
    </Card>
  );
}
