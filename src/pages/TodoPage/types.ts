export interface Todo {
  id: number;
  user_id: number;
  text: string;
  completed: boolean;
  completed_by?: number;
  created_at: string;
  habit?: HabitMetadata;
}

export interface HabitMetadata {
  frequency: 'daily';
  streak: number;
  history: Record<string, number>; // Date (YYYY-MM-DD) -> UserID who checked
}

export interface Member {
  id: number;
  name: string;
  color: string;
}

export type FilterType = 'all' | 'active' | 'completed';
