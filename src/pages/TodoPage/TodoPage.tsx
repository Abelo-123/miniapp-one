import { useState, useEffect, useCallback, type FC } from 'react';
import { useSignal, initData } from '@telegram-apps/sdk-react';
import type { Todo, HabitMetadata } from './types';
import { fetchTodos, addTodo, updateTodo, deleteTodo } from './api';
import './TodoPage.css';

const formatDate = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getWeekday = (date: Date) => {
    const days = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
    return days[date.getDay()];
};

const TODAY = formatDate(new Date());

// Check if a date is in the future
const isFutureDate = (dateStr: string) => {
    return dateStr > TODAY;
};

// Get last N days for the header (past dates only)
const getLastDays = (count: number) => {
    const days = [];
    for (let i = 0; i < count; i++) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        days.push({
            date: formatDate(d),
            weekday: getWeekday(d),
            dayNum: d.getDate()
        });
    }
    return days;
};

export const TodoPage: FC = () => {
    const initDataState = useSignal(initData.state);
    const userId = initDataState?.user?.id || 12345;

    const [todos, setTodos] = useState<Todo[]>([]);
    const [inputValue, setInputValue] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    // Last 5 days for the grid (all past dates)
    const displayDays = getLastDays(5);

    // Load todos
    const loadTodos = useCallback(async () => {
        try {
            setIsLoading(true);
            const data = await fetchTodos(userId);
            setTodos(data);
        } catch (err) {
            console.error(err);
        } finally {
            setIsLoading(false);
        }
    }, [userId]);

    useEffect(() => {
        loadTodos();
    }, [loadTodos]);

    // Add new habit
    const handleAddHabit = async () => {
        if (!inputValue.trim()) return;
        try {
            const habitMetadata: HabitMetadata = {
                frequency: 'daily',
                streak: 0,
                history: {}
            };

            const newTodo = await addTodo(userId, inputValue.trim(), habitMetadata);
            setTodos(prev => [newTodo, ...prev]);
            setInputValue('');
        } catch (err) {
            console.error(err);
        }
    };

    // Toggle habit for a specific date
    const handleDayToggle = async (todo: Todo, dateStr: string) => {
        if (!todo.habit) return;

        // BLOCK FUTURE DATES
        if (isFutureDate(dateStr)) {
            return;
        }

        const history = todo.habit.history || {};
        const isCompletedOnDate = history[dateStr] !== undefined;

        let newHistory = { ...history };
        if (isCompletedOnDate) {
            delete newHistory[dateStr];
        } else {
            newHistory[dateStr] = userId;
        }

        // Calculate streak
        let calculatedStreak = 0;
        if (newHistory[TODAY]) {
            calculatedStreak = 1;
            let d = new Date();
            while (true) {
                d.setDate(d.getDate() - 1);
                const dStr = formatDate(d);
                if (newHistory[dStr]) {
                    calculatedStreak++;
                } else {
                    break;
                }
            }
        }

        const newHabit = {
            ...todo.habit,
            history: newHistory,
            streak: calculatedStreak
        };

        const optimisticTodos = todos.map(t =>
            t.id === todo.id ? { ...t, habit: newHabit } : t
        );
        setTodos(optimisticTodos);

        try {
            await updateTodo(userId, todo.id, { text: todo.text, habit: newHabit });
        } catch (err) {
            setTodos(todos);
            console.error(err);
        }
    };

    // Toggle today's completion (via checkbox)
    const handleTodayToggle = (todo: Todo) => {
        handleDayToggle(todo, TODAY);
    };

    // Delete habit
    const handleDelete = async (todoId: number) => {
        const optimisticTodos = todos.filter(t => t.id !== todoId);
        const prevTodos = todos;
        setTodos(optimisticTodos);

        try {
            await deleteTodo(userId, todoId);
        } catch (err) {
            setTodos(prevTodos);
            console.error(err);
        }
    };

    // Filter only habits
    const habits = todos.filter(t => t.habit);

    return (
        <div className="habits-container">
            {/* Header */}
            <header className="habits-header">
                <h1 className="habits-title">Habits</h1>
                <div className="header-actions">
                    <button className="header-btn" title="Add">+</button>
                    <button className="header-btn" title="Filter">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <line x1="4" y1="21" x2="4" y2="14" />
                            <line x1="4" y1="10" x2="4" y2="3" />
                            <line x1="12" y1="21" x2="12" y2="12" />
                            <line x1="12" y1="8" x2="12" y2="3" />
                            <line x1="20" y1="21" x2="20" y2="16" />
                            <line x1="20" y1="12" x2="20" y2="3" />
                        </svg>
                    </button>
                    <button className="header-btn" title="Menu">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="5" r="2" />
                            <circle cx="12" cy="12" r="2" />
                            <circle cx="12" cy="19" r="2" />
                        </svg>
                    </button>
                </div>
            </header>

            {/* Days Header Row */}
            <div className="days-header">
                <div className="days-header-spacer"></div>
                {displayDays.map(day => (
                    <div key={day.date} className="day-column-header">
                        <span className="weekday">{day.weekday}</span>
                        <span className="day-num">{day.dayNum}</span>
                    </div>
                ))}
            </div>

            {/* Add Habit Input (hidden by default, shown when + clicked) */}
            <div className="add-habit-section">
                <input
                    type="text"
                    className="add-habit-input"
                    placeholder="Add new habit..."
                    value={inputValue}
                    onChange={(e) => setInputValue(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleAddHabit()}
                />
                <button
                    className="add-habit-btn"
                    onClick={handleAddHabit}
                    disabled={!inputValue.trim()}
                >
                    Add
                </button>
            </div>

            {/* Habits List */}
            {isLoading ? (
                <div className="loading-state">
                    <div className="spinner" />
                    <span>Loading habits...</span>
                </div>
            ) : habits.length === 0 ? (
                <div className="empty-state">
                    <h3>No habits yet</h3>
                    <p>Add a habit above to get started</p>
                </div>
            ) : (
                <ul className="habits-list">
                    {habits.map(habit => {
                        const isTodayDone = habit.habit?.history?.[TODAY] !== undefined;

                        return (
                            <li
                                key={habit.id}
                                className="habit-row"
                                onContextMenu={(e) => {
                                    e.preventDefault();
                                    if (confirm(`Delete "${habit.text}"?`)) {
                                        handleDelete(habit.id);
                                    }
                                }}
                            >
                                <div className="habit-name">
                                    <div
                                        className={`habit-checkbox ${isTodayDone ? 'checked' : ''}`}
                                        onClick={() => handleTodayToggle(habit)}
                                    />
                                    <span className="habit-text">{habit.text}</span>
                                </div>

                                {displayDays.map(day => {
                                    const isDone = habit.habit?.history?.[day.date] !== undefined;
                                    const isFuture = isFutureDate(day.date);
                                    return (
                                        <div
                                            key={day.date}
                                            className={`day-cell ${isFuture ? 'future' : ''}`}
                                            onClick={() => !isFuture && handleDayToggle(habit, day.date)}
                                        >
                                            <span className={`day-mark ${isDone ? 'completed' : ''}`}>
                                                ✕
                                            </span>
                                        </div>
                                    );
                                })}
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
};
