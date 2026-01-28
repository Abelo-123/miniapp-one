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
  history: string[]; // Array of 'YYYY-MM-DD' strings
}

export type FilterType = 'all' | 'active' | 'completed';
