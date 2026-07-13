export type GroupAccent = "mint" | "pink";
export type GroupStatus = "drawn" | "pending";

export interface Group {
  id: string;
  name: string;
  memberCount: number;
  status: GroupStatus;
  /** Drives the icon-tile color treatment. */
  accent: GroupAccent;
}
