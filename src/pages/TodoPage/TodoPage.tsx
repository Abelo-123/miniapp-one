import { useState, useEffect, useCallback, type FC } from 'react';
import { useSignal, initData } from '@telegram-apps/sdk-react';
import type { Todo, HabitMetadata } from './types';
import { fetchTodos, addTodo, updateTodo, deleteTodo } from './api';
import './TodoPage.css';

// ═══════════════════════════════════════════════════════════════
// DATE UTILITIES
// ═══════════════════════════════════════════════════════════════

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

const parseDate = (dateStr: string): Date => {
    // Handle "YYYY-MM-DD" or "YYYY-MM-DD HH:MM:SS" format
    const cleaned = dateStr.replace(' ', 'T');
    return new Date(cleaned);
};

const TODAY = formatDate(new Date());

// Check if date is in the future (after today)
const isFutureDate = (dateStr: string) => dateStr > TODAY;

// Check if date is before habit was created
const isBeforeCreation = (dateStr: string, createdAt: string) => {
    if (!createdAt) return false;
    const creationDate = formatDate(parseDate(createdAt));
    return dateStr < creationDate;
};

// Get last N days for the header
const getLastDays = (count: number) => {
    const days = [];
    for (let i = 0; i < count; i++) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        days.push({
            date: formatDate(d),
            weekday: getWeekday(d),
            dayNum: d.getDate(),
            isToday: i === 0
        });
    }
    return days;
};

// ═══════════════════════════════════════════════════════════════
// FILTER TYPES
// ═══════════════════════════════════════════════════════════════

type FilterMode = 'all' | 'completed' | 'incomplete';

// ═══════════════════════════════════════════════════════════════
// MAIN COMPONENT
// ═══════════════════════════════════════════════════════════════

export const TodoPage: FC = () => {
    const initDataState = useSignal(initData.state);
    const userId = initDataState?.user?.id || 12345;
    const userName = initDataState?.user?.first_name || 'You';

    const [todos, setTodos] = useState<Todo[]>([]);
    const [inputValue, setInputValue] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [filterMode, setFilterMode] = useState<FilterMode>('all');
    const [showFilterMenu, setShowFilterMenu] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    // Last 5 days for the grid
    const displayDays = getLastDays(5);

    // ═══════════════════════════════════════════════════════════
    // DATA LOADING
    // ═══════════════════════════════════════════════════════════

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

    // ═══════════════════════════════════════════════════════════
    // HABIT ACTIONS
    // ═══════════════════════════════════════════════════════════

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

    const handleDayToggle = async (todo: Todo, dateStr: string) => {
        if (!todo.habit) return;

        // SMART BLOCKING: No future dates, no dates before creation
        if (isFutureDate(dateStr)) {
            return;
        }
        if (isBeforeCreation(dateStr, todo.created_at)) {
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

    const handleTodayToggle = (todo: Todo) => {
        handleDayToggle(todo, TODAY);
    };

    const handleDelete = async (todoId: number) => {
        setDeletingId(todoId);
        const optimisticTodos = todos.filter(t => t.id !== todoId);
        const prevTodos = todos;
        setTodos(optimisticTodos);

        try {
            await deleteTodo(userId, todoId);
        } catch (err) {
            setTodos(prevTodos);
            console.error(err);
        } finally {
            setDeletingId(null);
        }
    };

    // ═══════════════════════════════════════════════════════════
    // FILTERING
    // ═══════════════════════════════════════════════════════════

    const habits = todos.filter(t => t.habit);

    const filteredHabits = habits.filter(habit => {
        const isTodayDone = habit.habit?.history?.[TODAY] !== undefined;

        switch (filterMode) {
            case 'completed':
                return isTodayDone;
            case 'incomplete':
                return !isTodayDone;
            default:
                return true;
        }
    });

    // ═══════════════════════════════════════════════════════════
    // RENDER
    // ═══════════════════════════════════════════════════════════

    return (
        <div className="habits-container">
            {/* Header */}
            <header className="habits-header">
                <h1 className="habits-title">Habits</h1>
                <div className="header-actions">
                    {/* Filter Button */}
                    <div className="filter-wrapper">
                        <button
                            className={`header-btn ${filterMode !== 'all' ? 'active' : ''}`}
                            title="Filter"
                            onClick={() => setShowFilterMenu(!showFilterMenu)}
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                            </svg>
                        </button>

                        {/* Filter Dropdown */}
                        {showFilterMenu && (
                            <div className="filter-menu">
                                <button
                                    className={`filter-option ${filterMode === 'all' ? 'selected' : ''}`}
                                    onClick={() => { setFilterMode('all'); setShowFilterMenu(false); }}
                                >
                                    All Habits
                                </button>
                                <button
                                    className={`filter-option ${filterMode === 'completed' ? 'selected' : ''}`}
                                    onClick={() => { setFilterMode('completed'); setShowFilterMenu(false); }}
                                >
                                    ✓ Completed Today
                                </button>
                                <button
                                    className={`filter-option ${filterMode === 'incomplete' ? 'selected' : ''}`}
                                    onClick={() => { setFilterMode('incomplete'); setShowFilterMenu(false); }}
                                >
                                    ✕ Not Done Today
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            {/* Current User */}
            <div className="user-badge">
                <span className="user-dot"></span>
                <span className="user-name">{userName}</span>
            </div>

            {/* Days Header Row */}
            <div className="days-header">
                <div className="days-header-spacer"></div>
                {displayDays.map(day => (
                    <div key={day.date} className={`day-column-header ${day.isToday ? 'today' : ''}`}>
                        <span className="weekday">{day.weekday}</span>
                        <span className="day-num">{day.dayNum}</span>
                    </div>
                ))}
                <div className="delete-column-spacer"></div>
            </div>

            {/* Add Habit Input */}
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

            {/* Filter indicator */}
            {filterMode !== 'all' && (
                <div className="filter-indicator">
                    Showing: {filterMode === 'completed' ? 'Completed today' : 'Not done today'}
                    <button
                        className="clear-filter"
                        onClick={() => setFilterMode('all')}
                    >
                        ✕
                    </button>
                </div>
            )}

            {/* Habits List */}
            {isLoading ? (
                <div className="loading-state">
                    <div className="spinner" />
                    <span>Loading habits...</span>
                </div>
            ) : filteredHabits.length === 0 ? (
                <div className="empty-state">
                    {habits.length === 0 ? (
                        <>
                            <h3>No habits yet</h3>
                            <p>Add a habit above to get started</p>
                        </>
                    ) : (
                        <>
                            <h3>No habits match filter</h3>
                            <p>Try changing the filter</p>
                        </>
                    )}
                </div>
            ) : (
                <ul className="habits-list">
                    {filteredHabits.map(habit => {
                        const isTodayDone = habit.habit?.history?.[TODAY] !== undefined;


                        return (
                            <li
                                key={habit.id}
                                className={`habit-row ${deletingId === habit.id ? 'deleting' : ''}`}
                            >
                                {/* Habit Name with Checkbox */}
                                <div className="habit-name">
                                    <div
                                        className={`habit-checkbox ${isTodayDone ? 'checked' : ''}`}
                                        onClick={() => handleTodayToggle(habit)}
                                    />
                                    <span className="habit-text">{habit.text}</span>
                                </div>

                                {/* Day Cells */}
                                {displayDays.map(day => {
                                    const isDone = habit.habit?.history?.[day.date] !== undefined;
                                    const isFuture = isFutureDate(day.date);
                                    const isBeforeHabit = isBeforeCreation(day.date, habit.created_at);
                                    const isDisabled = isFuture || isBeforeHabit;

                                    return (
                                        <div
                                            key={day.date}
                                            className={`day-cell ${isDisabled ? 'disabled' : ''} ${day.isToday ? 'today-cell' : ''}`}
                                            onClick={() => !isDisabled && handleDayToggle(habit, day.date)}
                                            title={isBeforeHabit ? 'Before habit created' : isFuture ? 'Future date' : ''}
                                        >
                                            <span className={`day-mark ${isDone ? 'completed' : ''}`}>
                                                {isBeforeHabit ? '—' : '✕'}
                                            </span>
                                        </div>
                                    );
                                })}

                                {/* Delete Button */}
                                <button
                                    className="delete-btn"
                                    onClick={() => handleDelete(habit.id)}
                                    title="Delete habit"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" />
                                        <line x1="10" y1="11" x2="10" y2="17" />
                                        <line x1="14" y1="11" x2="14" y2="17" />
                                    </svg>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            )}

            {/* Click overlay to close filter menu */}
            {showFilterMenu && (
                <div className="overlay" onClick={() => setShowFilterMenu(false)} />
            )}
        </div>
    );
};
