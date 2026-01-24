import type { Todo } from './types';

const API_BASE = import.meta.env.VITE_API_URL || 'https://paxyo.com/backend/todos.php';

function mapToTodo(data: any): Todo {
    return {
        id: Number(data.id),
        user_id: Number(data.user_id),
        text: String(data.text || ''),
        completed: data.completed === true || data.completed === 1 || data.completed === '1' || data.completed === 'true',
        completed_by: data.completed_by ? Number(data.completed_by) : undefined,
        created_at: String(data.created_at || '')
    };
}

export async function fetchTodos(userId: number): Promise<Todo[]> {
    const response = await fetch(`${API_BASE}?user_id=${userId}`);
    if (!response.ok) {
        throw new Error('Failed to fetch todos');
    }
    const data = await response.json();
    return Array.isArray(data) ? data.map(mapToTodo) : [];
}

export async function addTodo(userId: number, text: string): Promise<Todo> {
    const response = await fetch(`${API_BASE}?user_id=${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ text }),
    });
    if (!response.ok) {
        throw new Error('Failed to add todo');
    }
    const data = await response.json();
    return mapToTodo(data);
}

export async function updateTodo(userId: number, todoId: number, updates: Partial<Pick<Todo, 'completed' | 'text'>>): Promise<Todo> {
    const response = await fetch(`${API_BASE}?user_id=${userId}&id=${todoId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(updates),
    });
    if (!response.ok) {
        throw new Error('Failed to update todo');
    }
    const data = await response.json();
    return mapToTodo(data);
}

export async function deleteTodo(userId: number, todoId: number): Promise<void> {
    const response = await fetch(`${API_BASE}?user_id=${userId}&id=${todoId}`, {
        method: 'DELETE',
    });
    if (!response.ok) {
        throw new Error('Failed to delete todo');
    }
}
