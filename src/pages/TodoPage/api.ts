import type { Todo } from './types';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost/todo-api/todos.php';

export async function fetchTodos(userId: number): Promise<Todo[]> {
    const response = await fetch(`${API_BASE}?user_id=${userId}`);
    if (!response.ok) {
        throw new Error('Failed to fetch todos');
    }
    return response.json();
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
    return response.json();
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
    return response.json();
}

export async function deleteTodo(userId: number, todoId: number): Promise<void> {
    const response = await fetch(`${API_BASE}?user_id=${userId}&id=${todoId}`, {
        method: 'DELETE',
    });
    if (!response.ok) {
        throw new Error('Failed to delete todo');
    }
}
