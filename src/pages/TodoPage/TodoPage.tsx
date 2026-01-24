import { useState, useEffect, useCallback, type FC, type FormEvent } from 'react';
import { useSignal, isMiniAppDark, initData } from '@telegram-apps/sdk-react';
import { Page } from '@/components/Page';
import type { Todo, FilterType } from './types';
import { fetchTodos, addTodo, updateTodo, deleteTodo } from './api';
import './TodoPage.css';

export const TodoPage: FC = () => {
    const isDark = useSignal(isMiniAppDark);
    const initDataState = useSignal(initData.state);
    const userId = initDataState?.user?.id || 12345; // Fallback for testing

    const [todos, setTodos] = useState<Todo[]>([]);
    const [inputValue, setInputValue] = useState('');
    const [filter, setFilter] = useState<FilterType>('all');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [isAdding, setIsAdding] = useState(false);

    // Load todos on mount
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

    // Handle add todo
    const handleAddTodo = async (e: FormEvent) => {
        e.preventDefault();
        if (!inputValue.trim() || isAdding) return;

        try {
            setIsAdding(true);
            const newTodo = await addTodo(userId, inputValue.trim());
            setTodos(prev => [newTodo, ...prev]);
            setInputValue('');
        } catch (err) {
            console.error(err);
        } finally {
            setIsAdding(false);
        }
    };

    // Handle toggle complete
    const handleToggle = async (todo: Todo) => {
        const newCompleted = !todo.completed;
        // Optimistic update
        setTodos(prev =>
            prev.map(t => (t.id === todo.id ? { ...t, completed: newCompleted, completed_by: newCompleted ? userId : undefined } : t))
        );
        try {
            await updateTodo(userId, todo.id, { completed: newCompleted });
        } catch (err) {
            // Revert on error
            setTodos(prev =>
                prev.map(t => (t.id === todo.id ? { ...t, completed: todo.completed } : t))
            );
            console.error(err);
        }
    };

    // Handle delete
    const handleDelete = async (todoId: number) => {
        const prevTodos = todos;
        // Optimistic update
        setTodos(prev => prev.filter(t => t.id !== todoId));
        try {
            await deleteTodo(userId, todoId);
        } catch (err) {
            // Revert on error
            setTodos(prevTodos);
            console.error(err);
        }
    };

    // Filter todos
    const filteredTodos = todos.filter(todo => {
        if (filter === 'active') return !todo.completed;
        if (filter === 'completed') return todo.completed;
        return true;
    });

    // Stats
    const completedCount = todos.filter(t => t.completed).length;
    const activeCount = todos.length - completedCount;
    const progress = todos.length > 0 ? (completedCount / todos.length) * 100 : 0;

    // Generate user color
    const getUserColor = (id: number) => {
        const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEEAD', '#FFCC5C', '#FF9671'];
        return colors[id % colors.length];
    };

    return (
        <Page back={false}>
            <div className="todo-page">
                {/* Header */}
                <header className="todo-header">
                    <h1>✨ Team Tasks</h1>
                    <button
                        className="theme-toggle"
                        onClick={() => {
                            // Theme is controlled by Telegram, this is just visual feedback
                            document.body.classList.toggle('force-dark');
                        }}
                        aria-label="Toggle theme"
                    >
                        {isDark ? '☀️' : '🌙'}
                    </button>
                </header>

                {/* Input Section */}
                <form className="todo-input-section" onSubmit={handleAddTodo}>
                    <div className="todo-input-wrapper">
                        <input
                            type="text"
                            className="todo-input"
                            placeholder="What needs to be done?"
                            value={inputValue}
                            onChange={e => setInputValue(e.target.value)}
                            disabled={isAdding}
                        />
                        <button
                            type="submit"
                            className="add-button"
                            disabled={!inputValue.trim() || isAdding}
                        >
                            +
                        </button>
                    </div>
                </form>

                {/* Filter Section */}
                <div className="filter-section">
                    <button
                        className={`filter-button ${filter === 'all' ? 'active' : ''}`}
                        onClick={() => setFilter('all')}
                    >
                        All<span className="filter-count">{todos.length}</span>
                    </button>
                    <button
                        className={`filter-button ${filter === 'active' ? 'active' : ''}`}
                        onClick={() => setFilter('active')}
                    >
                        Active<span className="filter-count">{activeCount}</span>
                    </button>
                    <button
                        className={`filter-button ${filter === 'completed' ? 'active' : ''}`}
                        onClick={() => setFilter('completed')}
                    >
                        Completed<span className="filter-count">{completedCount}</span>
                    </button>
                </div>

                {/* Progress Section */}
                {todos.length > 0 && (
                    <div className="progress-section">
                        <div className="progress-bar">
                            <div className="progress-fill" style={{ width: `${progress}%` }} />
                        </div>
                        <p className="progress-text">
                            {completedCount} of {todos.length} tasks completed
                        </p>
                    </div>
                )}

                {/* Content */}
                {isLoading ? (
                    <div className="loading-state">
                        <div className="spinner" />
                    </div>
                ) : error ? (
                    <div className="error-state">
                        <div className="error-icon">⚠️</div>
                        <p>{error}</p>
                        <button className="retry-button" onClick={loadTodos}>
                            Try Again
                        </button>
                    </div>
                ) : filteredTodos.length === 0 ? (
                    <div className="empty-state">
                        <div className="empty-icon">
                            {filter === 'all' ? '📝' : filter === 'active' ? '🎉' : '🔍'}
                        </div>
                        <h3 className="empty-title">
                            {filter === 'all'
                                ? 'No tasks yet'
                                : filter === 'active'
                                    ? 'All done!'
                                    : 'No completed tasks'}
                        </h3>
                        <p className="empty-subtitle">
                            {filter === 'all'
                                ? 'Add your first task above'
                                : filter === 'active'
                                    ? 'You completed all your tasks'
                                    : 'Complete some tasks to see them here'}
                        </p>
                    </div>
                ) : (
                    <div className="todo-list">
                        {filteredTodos.map(todo => (
                            <div
                                key={todo.id}
                                className={`todo-item ${todo.completed ? 'completed' : ''}`}
                            >
                                <div className="todo-main">
                                    <div className="todo-meta-top">
                                        <span
                                            className="user-badge creator-badge"
                                            style={{ backgroundColor: getUserColor(todo.user_id) }}
                                        >
                                            User {todo.user_id}
                                        </span>
                                    </div>
                                    <div className="todo-content-wrapper">
                                        <button
                                            className={`todo-checkbox ${todo.completed ? 'checked' : ''}`}
                                            onClick={() => handleToggle(todo)}
                                            aria-label={todo.completed ? 'Mark as incomplete' : 'Mark as complete'}
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                        </button>
                                        <span className="todo-text">{todo.text}</span>
                                    </div>
                                    {todo.completed && todo.completed_by && (
                                        <div className="todo-meta-bottom">
                                            <span
                                                className="user-badge completer-badge"
                                                style={{ backgroundColor: getUserColor(todo.completed_by), opacity: 0.8 }}
                                            >
                                                ✓ Done by {todo.completed_by}
                                            </span>
                                        </div>
                                    )}
                                </div>
                                <button
                                    className="delete-button"
                                    onClick={() => handleDelete(todo.id)}
                                    aria-label="Delete todo"
                                >
                                    🗑️
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </Page>
    );
};

export default TodoPage;
