import { cva, type VariantProps } from "class-variance-authority";
import { forwardRef, type ReactNode } from "react";
import { Pressable, type PressableProps, View } from "react-native";

import { cn } from "@/lib/utils";

import { Text } from "./text";

const buttonVariants = cva(
  "min-h-11 flex-row items-center justify-center rounded-full active:opacity-85",
  {
    variants: {
      variant: {
        primary: "bg-mint",
        pink: "bg-pink",
        light: "bg-card",
      },
      size: {
        sm: "gap-1.5 px-[15px] py-[9px]",
        md: "gap-1.5 px-4 py-[10px]",
      },
    },
    defaultVariants: { variant: "primary", size: "md" },
  },
);

const buttonTextVariants = cva("font-display-x", {
  variants: {
    variant: {
      primary: "text-white",
      pink: "text-white",
      light: "text-mint-deep",
    },
    size: {
      sm: "text-[13px] leading-[16px]",
      md: "text-[14px] leading-[18px]",
    },
  },
  defaultVariants: { variant: "primary", size: "md" },
});

export interface ButtonProps
  extends PressableProps, VariantProps<typeof buttonVariants> {
  label: string;
  leftIcon?: ReactNode;
  rightIcon?: ReactNode;
}

export const Button = forwardRef<View, ButtonProps>(function Button(
  { className, variant, size, label, leftIcon, rightIcon, ...props },
  ref,
) {
  return (
    <Pressable
      ref={ref}
      className={cn(buttonVariants({ variant, size }), className)}
      accessibilityRole="button"
      {...props}
    >
      {leftIcon}
      <Text className={buttonTextVariants({ variant, size })}>{label}</Text>
      {rightIcon}
    </Pressable>
  );
});
