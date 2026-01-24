import { useState, useEffect, useCallback, type FC } from 'react';
import { useSignal, initData } from '@telegram-apps/sdk-react';
import {
    List,
    Section,
    Cell,
    Input,
    Button,
    Text,
    Title,
    Placeholder,
    SegmentedControl,
    Checkbox
} from '@telegram-apps/telegram-ui';
import { Page } from '@/components/Page';
import type { Todo, FilterType } from './types';
import { fetchTodos, addTodo, updateTodo, deleteTodo } from './api';
import './TodoPage.css';

// Simple icon replacement using text if icons aren't available, 
// or SVG if we want to be fancy.
const DeleteIcon = () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
    </svg>
);

export const TodoPage: FC = () => {
    const initDataState = useSignal(initData.state);
    const userId = initDataState?.user?.id || 12345;

    const [todos, setTodos] = useState<Todo[]>([]);
    const [inputValue, setInputValue] = useState('');
    const [filter, setFilter] = useState<FilterType>('all');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    // Load todos
    const loadTodos = useCallback(async () => {
        try {
            setIsLoading(true);
            setError(null);
            const data = await fetchTodos(userId);
            setTodos(data);
        } catch (err) {
            setError('Failed to load todos');
            console.error(err);
        } finally {
            setIsLoading(false);
        }
    }, [userId]);

    useEffect(() => {
        loadTodos();
    }, [loadTodos]);

    // Handlers
    const handleAddTodo = async () => {
        if (!inputValue.trim()) return;
        try {
            const newTodo = await addTodo(userId, inputValue.trim());
            setTodos(prev => [newTodo, ...prev]);
            setInputValue('');
        } catch (err) {
            console.error(err);
        }
    };

    const handleToggle = async (todo: Todo) => {
        const newCompleted = !todo.completed;
        const optimisticTodos = todos.map(t =>
            t.id === todo.id ? { ...t, completed: newCompleted, completed_by: newCompleted ? userId : undefined } : t
        );
        setTodos(optimisticTodos);

        try {
            await updateTodo(userId, todo.id, { completed: newCompleted });
        } catch (err) {
            setTodos(todos); // Revert
            console.error(err);
        }
    };

    const handleDelete = async (todoId: number) => {
        const optimisticTodos = todos.filter(t => t.id !== todoId);
        const prevTodos = todos;
        setTodos(optimisticTodos);

        try {
            await deleteTodo(userId, todoId);
        } catch (err) {
            setTodos(prevTodos); // Revert
            console.error(err);
        }
    };

    // Derived state
    const filteredTodos = todos.filter(todo => {
        if (filter === 'active') return !todo.completed;
        if (filter === 'completed') return todo.completed;
        return true;
    });

    const getUserColor = (id: number) => {
        const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEEAD', '#FFCC5C', '#FF9671'];
        return colors[Math.abs(id) % colors.length];
    };

    return (
        <Page back={false}>
            <List style={{ background: 'var(--tgui--secondary_bg_color)', minHeight: '100vh' }}>
                <Section
                    header="Team Tasks"
                    footer="Tasks are shared with everyone."
                >
                    <Cell
                        after={
                            <Button
                                size="s"
                                onClick={handleAddTodo}
                                disabled={!inputValue.trim()}
                            >
                                Add
                            </Button>
                        }
                    >
                        <Input
                            value={inputValue}
                            onChange={(e) => setInputValue(e.target.value)}
                            placeholder="What needs to be done?"
                        />
                    </Cell>
                </Section>

                <div style={{ padding: '0 20px 10px' }}>
                    <SegmentedControl
                    >
                        <SegmentedControl.Item
                            onClick={() => setFilter('all')}
                            selected={filter === 'all'}
                        >
                            All
                        </SegmentedControl.Item>
                        <SegmentedControl.Item
                            onClick={() => setFilter('active')}
                            selected={filter === 'active'}
                        >
                            Active
                        </SegmentedControl.Item>
                        <SegmentedControl.Item
                            onClick={() => setFilter('completed')}
                            selected={filter === 'completed'}
                        >
                            Done
                        </SegmentedControl.Item>
                    </SegmentedControl>
                </div>

                {isLoading ? (
                    <div className="center-content">
                        <div className="spinner" />
                        <Text>Loading tasks...</Text>
                    </div>
                ) : error ? (
                    <Placeholder
                        header="Oops"
                        description={error}
                        action={<Button onClick={loadTodos}>Try Again</Button>}
                    />
                ) : filteredTodos.length === 0 ? (
                    <div className="center-content">
                        <Title level="3">No tasks found</Title>
                        <Text>
                            {filter === 'all' ? 'Add a task to get started' :
                                filter === 'active' ? 'You have completed everything!' :
                                    'No completed tasks yet'}
                        </Text>
                    </div>
                ) : (
                    <Section header={`${filter.charAt(0).toUpperCase() + filter.slice(1)} Tasks (${filteredTodos.length})`}>
                        {filteredTodos.map(todo => (
                            <Cell
                                key={todo.id}
                                multiline
                                before={
                                    <Checkbox
                                        checked={!!todo.completed}
                                        onChange={() => handleToggle(todo)}
                                    />
                                }
                                after={
                                    <Button
                                        mode="plain"
                                        size="s"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleDelete(todo.id);
                                        }}
                                        style={{ color: 'var(--tgui--destructive_text_color)' }}
                                    >
                                        <DeleteIcon />
                                    </Button>
                                }
                            >
                                <div className="todo-cell-content">
                                    <span className={`todo-text ${todo.completed ? 'completed' : ''}`}>
                                        {todo.text}
                                    </span>
                                    <div className="todo-meta">
                                        <span
                                            className="user-badge"
                                            style={{ backgroundColor: getUserColor(todo.user_id) }}
                                        >
                                            User {todo.user_id}
                                        </span>
                                        {todo.completed && todo.completed_by && (
                                            <span
                                                className="user-badge"
                                                style={{ backgroundColor: getUserColor(todo.completed_by), opacity: 0.8 }}
                                            >
                                                ✓ Done by {todo.completed_by}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </Cell>
                        ))}
                    </Section>
                )}
            </List>
        </Page>
    );
};
