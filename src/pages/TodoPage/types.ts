export interface Todo {
  id: number;
  user_id: number;
  text: string;
  completed: boolean;
  completed_by?: number;
  created_at: string;
}

export type FilterType = 'all' | 'active' | 'completed';
