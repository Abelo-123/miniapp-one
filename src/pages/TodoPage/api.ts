import type { Todo } from './types';

const API_BASE = import.meta.env.VITE_API_URL || 'https://paxyo.com/backend/todos.php';

const HABIT_DELIMITER = ' ||| ';

function mapToTodo(data: any): Todo {
    let text = String(data.text || '');
    let habit: import('./types').HabitMetadata | undefined;

    if (text.includes(HABIT_DELIMITER)) {
        const parts = text.split(HABIT_DELIMITER);
        text = parts[0];
        try {
            habit = JSON.parse(parts[1]);
        } catch (e) {
            console.error('Failed to parse habit metadata', e);
        }
    }

    return {
        id: Number(data.id),
        user_id: Number(data.user_id),
        text: text,
        completed: data.completed === true || data.completed === 1 || data.completed === '1' || data.completed === 'true',
        completed_by: data.completed_by ? Number(data.completed_by) : undefined,
        created_at: String(data.created_at || ''),
        habit
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

export async function addTodo(userId: number, text: string, habit?: import('./types').HabitMetadata): Promise<Todo> {
    const payloadText = habit ? `${text}${HABIT_DELIMITER}${JSON.stringify(habit)}` : text;
    const response = await fetch(`${API_BASE}?user_id=${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ text: payloadText }),
    });
    if (!response.ok) {
        throw new Error('Failed to add todo');
    }
    const data = await response.json();
    return mapToTodo(data);
}

export async function updateTodo(userId: number, todoId: number, updates: Partial<Pick<Todo, 'completed' | 'text' | 'habit'>>): Promise<Todo> {
    const body: any = { ...updates };

    // If we're updating text or habit, we might need to handle the serialization
    if (updates.text !== undefined || updates.habit !== undefined) {
        // This part is tricky because we might not have the other piece. 
        // For simplicity, we'll assume the caller passes BOTH if one changes, OR we rely on what is passed.
        // Actually, the caller in UI should handle merging. But here let's support convenience.
        // If 'habit' is passed, we MUST serialize it into 'text'.
        // If 'text' is ALSO passed, use it. If not, this function would need to know the old text...
        // To avoid fetching here, let's change the signature or expect the caller to do the merging?
        // Let's assume the CALLER constructs the full 'text' if they want to save metadata, OR we can accept `habit` and `text` and merge them.

        // BETTER APPROACH for this specific tool: 
        // We will strip 'habit' from the body sent to server, and construct 'text' if 'habit' is present.

        let newText = updates.text;

        // If we don't have updates.text but we have updates.habit, we theoretically need the old text.
        // BUT, usually we update both or just status.
        // Let's assume if 'habit' is provided, 'text' MUST be provided or we are overwriting it (dangerous?)
        // Let's check if the caller provides 'text'.

        if (updates.habit) {
            if (newText === undefined) {
                // Fallback or Error? Let's throw to be safe for now, or the UI must always pass text.
                // Ideally the UI passes the current text.
                throw new Error("Cannot update habit without providing text");
            }
            newText = `${newText}${HABIT_DELIMITER}${JSON.stringify(updates.habit)}`;
            body.text = newText;
            delete body.habit;
        }
    }

    const response = await fetch(`${API_BASE}?user_id=${userId}&id=${todoId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
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
