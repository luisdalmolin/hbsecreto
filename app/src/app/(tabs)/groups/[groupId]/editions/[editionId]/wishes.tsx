import { useLocalSearchParams } from "expo-router";
import { Archive, Plus } from "lucide-react-native";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { ActivityIndicator, Alert, View } from "react-native";

import { normalizeApiError } from "@/api/errors";
import { getEdition } from "@/api/generated/editions/editions";
import type { Wish } from "@/api/generated/models";
import {
  createWish,
  deleteWish,
  getMyWishes,
  reorderWishes,
  updateWish,
} from "@/api/generated/wishes/wishes";
import { AppScreen } from "@/components/common/app-screen";
import { FormField } from "@/components/common/form-field";
import { ScreenState } from "@/components/common/screen-state";
import { WishListItem } from "@/components/wishes/wish-list-item";
import { Button, Card, Text } from "@/components/ui";
import { apiErrorMessage, parseRouteId } from "@/features/shared/presentation";
import { useFocusResource } from "@/hooks/use-focus-resource";
import { useMountedRef } from "@/hooks/use-mounted-ref";
import { palette } from "@/theme/tokens";

type Mutation =
  | { type: "create" }
  | { type: "update"; wishId: number }
  | { type: "delete"; wishId: number }
  | { type: "reorder"; wishId: number; offset: -1 | 1 };

export default function WishesScreen() {
  const { t } = useTranslation();
  const params = useLocalSearchParams<{ groupId: string; editionId: string }>();
  const groupId = parseRouteId(params.groupId);
  const editionId = parseRouteId(params.editionId);
  const [description, setDescription] = useState("");
  const [editingId, setEditingId] = useState<number>();
  const [editingDescription, setEditingDescription] = useState("");
  const [mutation, setMutation] = useState<Mutation>();
  const [mutationError, setMutationError] = useState<unknown>();
  const [localFieldError, setLocalFieldError] = useState<string>();
  const mounted = useMountedRef();
  const load = async (signal: AbortSignal) => {
    if (!groupId || !editionId) throw new Error(t("common.errors.notFound"));
    const [edition, wishes] = await Promise.all([
      getEdition(groupId, editionId, { signal }),
      getMyWishes(groupId, editionId, { signal }),
    ]);
    return { edition, wishes: wishes.data };
  };
  const resource = useFocusResource(load);
  const fieldError =
    localFieldError ?? normalizeApiError(mutationError).fields?.description;

  function clearErrors(): void {
    setLocalFieldError(undefined);
    setMutationError(undefined);
  }

  function validate(value: string): string | undefined {
    return value.trim() ? undefined : t("wishes.required");
  }

  async function create(): Promise<void> {
    if (!groupId || !editionId || mutation) return;
    const value = description.trim();
    const validationError = validate(value);
    if (validationError) {
      setLocalFieldError(validationError);
      return;
    }

    clearErrors();
    setMutation({ type: "create" });
    try {
      const wish = await createWish(groupId, editionId, {
        description: value,
      });
      if (!mounted.current) return;
      resource.setData((current) =>
        current ? { ...current, wishes: [...current.wishes, wish] } : current,
      );
      setDescription("");
    } catch (exception) {
      if (!mounted.current) return;
      setMutationError(exception);
      resource.refresh();
    }
    if (mounted.current) setMutation(undefined);
  }

  function beginEdit(wish: Wish): void {
    clearErrors();
    setEditingId(wish.id);
    setEditingDescription(wish.description);
  }

  function cancelEdit(): void {
    clearErrors();
    setEditingId(undefined);
    setEditingDescription("");
  }

  async function saveEdit(wishId: number): Promise<void> {
    if (!groupId || !editionId || mutation) return;
    const value = editingDescription.trim();
    const validationError = validate(value);
    if (validationError) {
      setLocalFieldError(validationError);
      return;
    }

    clearErrors();
    setMutation({ type: "update", wishId });
    try {
      const updated = await updateWish(groupId, editionId, wishId, {
        description: value,
      });
      if (!mounted.current) return;
      resource.setData((current) =>
        current
          ? {
              ...current,
              wishes: current.wishes.map((wish) =>
                wish.id === wishId ? updated : wish,
              ),
            }
          : current,
      );
      setEditingId(undefined);
      setEditingDescription("");
    } catch (exception) {
      if (!mounted.current) return;
      setMutationError(exception);
      resource.refresh();
    }
    if (mounted.current) setMutation(undefined);
  }

  function confirmDelete(wish: Wish): void {
    Alert.alert(t("wishes.deleteConfirmTitle"), t("wishes.deleteConfirmBody"), [
      { text: t("common.cancel"), style: "cancel" },
      {
        text: t("wishes.delete"),
        style: "destructive",
        onPress: () => void remove(wish.id),
      },
    ]);
  }

  async function remove(wishId: number): Promise<void> {
    if (!groupId || !editionId || mutation) return;
    clearErrors();
    setMutation({ type: "delete", wishId });
    try {
      await deleteWish(groupId, editionId, wishId);
      if (!mounted.current) return;
      resource.setData((current) =>
        current
          ? {
              ...current,
              wishes: current.wishes.filter((wish) => wish.id !== wishId),
            }
          : current,
      );
    } catch (exception) {
      if (!mounted.current) return;
      setMutationError(exception);
      resource.refresh();
    }
    if (mounted.current) setMutation(undefined);
  }

  async function move(wishId: number, offset: -1 | 1): Promise<void> {
    if (!groupId || !editionId || mutation || !resource.data) return;
    const index = resource.data.wishes.findIndex((wish) => wish.id === wishId);
    const targetIndex = index + offset;
    if (
      index < 0 ||
      targetIndex < 0 ||
      targetIndex >= resource.data.wishes.length
    )
      return;

    const reordered = [...resource.data.wishes];
    const target = reordered[targetIndex];
    if (!target) return;
    reordered[targetIndex] = reordered[index]!;
    reordered[index] = target;

    clearErrors();
    setMutation({ type: "reorder", wishId, offset });
    try {
      const wishes = await reorderWishes(groupId, editionId, {
        wishIds: reordered.map((wish) => wish.id),
      });
      if (!mounted.current) return;
      resource.setData((current) =>
        current ? { ...current, wishes: wishes.data } : current,
      );
    } catch (exception) {
      if (!mounted.current) return;
      setMutationError(exception);
      resource.refresh();
    }
    if (mounted.current) setMutation(undefined);
  }

  if (!resource.data) {
    return (
      <AppScreen title={t("wishes.title")} back>
        <ScreenState
          kind={resource.isLoading ? "loading" : "error"}
          title={
            resource.isLoading ? t("common.loading") : t("wishes.loadError")
          }
          message={
            resource.error ? apiErrorMessage(resource.error, t) : undefined
          }
          retryLabel={t("common.retry")}
          onRetry={resource.refresh}
        />
      </AppScreen>
    );
  }

  const locked = resource.data.edition.status === "archived";
  const controlsDisabled = Boolean(mutation) || editingId !== undefined;

  return (
    <AppScreen
      title={t("wishes.title")}
      subtitle={t("wishes.subtitle")}
      back
      refreshing={resource.isRefreshing}
      onRefresh={mutation ? undefined : resource.refresh}
    >
      {locked ? (
        <ArchivedEditionNotice />
      ) : (
        <Card className="gap-3 p-5">
          <FormField
            label={t("wishes.field")}
            placeholder={t("wishes.placeholder")}
            value={description}
            onChangeText={(value) => {
              setDescription(value);
              clearErrors();
            }}
            maxLength={500}
            multiline
            editable={!mutation}
            error={editingId === undefined ? fieldError : undefined}
          />
          <Button
            label={
              mutation?.type === "create" ? t("wishes.adding") : t("wishes.add")
            }
            leftIcon={
              mutation?.type === "create" ? (
                <ActivityIndicator color={palette.white} />
              ) : (
                <Plus color={palette.white} size={18} />
              )
            }
            disabled={Boolean(mutation) || editingId !== undefined}
            onPress={() => void create()}
          />
        </Card>
      )}

      <Text variant="section">{t("wishes.listTitle")}</Text>

      {resource.data.wishes.length === 0 ? (
        <ScreenState
          kind="empty"
          title={t("wishes.empty")}
          message={locked ? undefined : t("wishes.emptyHint")}
        />
      ) : (
        <View className="gap-3">
          {resource.data.wishes.map((wish, index) => (
            <WishListItem
              key={wish.id}
              wish={wish}
              index={index}
              count={resource.data!.wishes.length}
              locked={locked}
              editing={editingId === wish.id}
              editingDescription={editingDescription}
              fieldError={fieldError}
              controlsDisabled={controlsDisabled}
              busy={busyState(mutation, wish.id)}
              onEditingDescriptionChange={(value) => {
                setEditingDescription(value);
                clearErrors();
              }}
              onBeginEdit={() => beginEdit(wish)}
              onCancelEdit={cancelEdit}
              onSaveEdit={() => void saveEdit(wish.id)}
              onDelete={() => confirmDelete(wish)}
              onMoveUp={() => void move(wish.id, -1)}
              onMoveDown={() => void move(wish.id, 1)}
            />
          ))}
        </View>
      )}

      {mutationError ? (
        <Text className="text-pink-deep" accessibilityRole="alert">
          {apiErrorMessage(mutationError, t)}
        </Text>
      ) : null}
      {resource.error ? (
        <Text className="text-pink-deep" accessibilityRole="alert">
          {apiErrorMessage(resource.error, t)}
        </Text>
      ) : null}
    </AppScreen>
  );
}

function busyState(
  mutation: Mutation | undefined,
  wishId: number,
): "up" | "down" | "update" | "delete" | undefined {
  if (!mutation || !("wishId" in mutation) || mutation.wishId !== wishId)
    return undefined;
  if (mutation.type === "reorder")
    return mutation.offset === -1 ? "up" : "down";
  return mutation.type;
}

function ArchivedEditionNotice() {
  const { t } = useTranslation();

  return (
    <Card className="flex-row gap-3 p-5">
      <Archive color={palette.mint} size={22} />
      <View className="flex-1 gap-1">
        <Text variant="cardTitle">{t("wishes.readOnlyTitle")}</Text>
        <Text variant="caption">{t("wishes.readOnlyBody")}</Text>
      </View>
    </Card>
  );
}
