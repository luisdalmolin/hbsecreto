import { ChevronDown, ChevronUp, Pencil, Trash2 } from "lucide-react-native";
import { useTranslation } from "react-i18next";
import { ActivityIndicator, View } from "react-native";

import type { Wish } from "@/api/generated/models";
import { FormField } from "@/components/common/form-field";
import { Button, Card, IconButton, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

interface WishListItemProps {
  wish: Wish;
  index: number;
  count: number;
  locked: boolean;
  editing: boolean;
  editingDescription: string;
  fieldError?: string;
  controlsDisabled: boolean;
  busy?: "up" | "down" | "update" | "delete";
  onEditingDescriptionChange(value: string): void;
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
  fieldError,
  controlsDisabled,
  busy,
  onEditingDescriptionChange,
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
