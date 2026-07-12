/**
 * Static mock data for the home screen. Shapes here are intentionally simple
 * and screen-local; they will be replaced by the generated API client
 * (`src/api/generated`) once the screen is wired to the backend.
 */

export interface CurrentUser {
  name: string;
  initials: string;
}

export interface DrawResult {
  recipientName: string;
  groupName: string;
  wishlist: string[];
  /** Human-readable budget value, e.g. "até R$ 100". */
  budget: string;
}

export type GroupAccent = 'mint' | 'pink';
export type GroupStatus = 'drawn' | 'pending';

export interface Group {
  id: string;
  name: string;
  memberCount: number;
  status: GroupStatus;
  /** Drives the icon-tile color treatment. */
  accent: GroupAccent;
}

export const currentUser: CurrentUser = {
  name: 'Marina Ribeiro',
  initials: 'MR',
};

export const daysUntilChristmas = 12;

export const drawResult: DrawResult = {
  recipientName: 'Rafael Souza',
  groupName: 'Natal da Família',
  wishlist: ['Fone bluetooth', 'Livro de receitas'],
  budget: 'até R$ 100',
};

export const groups: Group[] = [
  { id: '1', name: 'Natal da Família', memberCount: 8, status: 'drawn', accent: 'mint' },
  { id: '2', name: 'Amigo Secreto do Trampo', memberCount: 12, status: 'pending', accent: 'pink' },
];
