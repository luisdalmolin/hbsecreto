import { useFocusEffect } from "expo-router";
import { useCallback, useEffect, useRef, useState } from "react";

export interface FocusResource<T> {
  data: T | undefined;
  error: unknown;
  isLoading: boolean;
  isRefreshing: boolean;
  refresh(): void;
  setData: React.Dispatch<React.SetStateAction<T | undefined>>;
}

export function useFocusResource<T>(
  load: (signal: AbortSignal) => Promise<T>,
): FocusResource<T> {
  const [data, setData] = useState<T>();
  const [error, setError] = useState<unknown>();
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);
  const hasData = useRef(false);
  const loadRef = useRef(load);

  useEffect(() => {
    loadRef.current = load;
  }, [load]);

  useFocusEffect(
    useCallback(() => {
      void refreshKey;
      const controller = new AbortController();

      if (hasData.current) setIsRefreshing(true);
      else setIsLoading(true);

      setError(undefined);
      void loadRef
        .current(controller.signal)
        .then((value) => {
          if (controller.signal.aborted) return;
          hasData.current = true;
          setData(value);
        })
        .catch((exception: unknown) => {
          if (!controller.signal.aborted) setError(exception);
        })
        .finally(() => {
          if (!controller.signal.aborted) {
            setIsLoading(false);
            setIsRefreshing(false);
          }
        });

      return () => controller.abort();
    }, [refreshKey]),
  );

  return {
    data,
    error,
    isLoading,
    isRefreshing,
    refresh: () => setRefreshKey((value) => value + 1),
    setData,
  };
}
