import { useState, useEffect, useCallback, useMemo, type FC } from 'react';
import { useSignal, initData } from '@telegram-apps/sdk-react';
import type { Todo, HabitMetadata, Member } from './types';
import { fetchTodos, addTodo, updateTodo, deleteTodo } from './api';
import './TodoPage.css';

// ═══════════════════════════════════════════════════════════════
// MEMBER COLORS - Unique color for each user
// ═══════════════════════════════════════════════════════════════

const MEMBER_COLORS = [
    '#4fc3f7', // Cyan
    '#ff7043', // Orange
    '#66bb6a', // Green
    '#ab47bc', // Purple
    '#ffa726', // Amber
    '#ef5350', // Red
    '#26c6da', // Teal
    '#ec407a', // Pink
    '#8d6e63', // Brown
    '#78909c', // Blue Grey
];

const getColorForUser = (userId: number): string => {
    const index = Math.abs(userId) % MEMBER_COLORS.length;
    return MEMBER_COLORS[index];
};

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
    const cleaned = dateStr.replace(' ', 'T');
    return new Date(cleaned);
};

const TODAY = formatDate(new Date());

const isFutureDate = (dateStr: string) => dateStr > TODAY;
const isBeforeCreation = (dateStr: string, createdAt: string) => {
    if (!createdAt) return false;
    return dateStr < formatDate(parseDate(createdAt));
};

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
    const [showMembersModal, setShowMembersModal] = useState(false);

    const displayDays = getLastDays(5);

    // ═══════════════════════════════════════════════════════════
    // EXTRACT MEMBERS FROM DATA
    // ═══════════════════════════════════════════════════════════

    const members = useMemo((): Member[] => {
        const memberMap = new Map<number, Member>();

        // Add current user
        memberMap.set(userId, {
            id: userId,
            name: userName,
            color: getColorForUser(userId)
        });

        // Extract from todos (creators and checkers)
        todos.forEach(todo => {
            // Creator
            if (todo.user_id && !memberMap.has(todo.user_id)) {
                memberMap.set(todo.user_id, {
                    id: todo.user_id,
                    name: `User ${todo.user_id}`,
                    color: getColorForUser(todo.user_id)
                });
            }

            // People who checked habits
            if (todo.habit?.history) {
                Object.values(todo.habit.history).forEach(checkerId => {
                    if (!memberMap.has(checkerId)) {
                        memberMap.set(checkerId, {
                            id: checkerId,
                            name: `User ${checkerId}`,
                            color: getColorForUser(checkerId)
                        });
                    }
                });
            }
        });

        return Array.from(memberMap.values());
    }, [todos, userId, userName]);

    // Get member by ID
    const getMember = (id: number): Member => {
        return members.find(m => m.id === id) || {
            id,
            name: `User ${id}`,
            color: getColorForUser(id)
        };
    };

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
        if (isFutureDate(dateStr)) return;
        if (isBeforeCreation(dateStr, todo.created_at)) return;

        const history = todo.habit.history || {};
        const isCompletedOnDate = history[dateStr] !== undefined;

        let newHistory = { ...history };
        if (isCompletedOnDate) {
            // Only allow unchecking your own check
            if (history[dateStr] === userId) {
                delete newHistory[dateStr];
            } else {
                return; // Can't uncheck someone else's mark
            }
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
        if (!confirm('Delete this habit?')) return;

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

    // ═══════════════════════════════════════════════════════════
    // FILTERING
    // ═══════════════════════════════════════════════════════════

    const habits = todos.filter(t => t.habit);

    const filteredHabits = habits.filter(habit => {
        const isTodayDone = habit.habit?.history?.[TODAY] !== undefined;

        switch (filterMode) {
            case 'completed': return isTodayDone;
            case 'incomplete': return !isTodayDone;
            default: return true;
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
                    {/* Members Button */}
                    <button
                        className="header-btn"
                        onClick={() => setShowMembersModal(true)}
                        title="View Members"
                    >
                        <span className="member-count">{members.length}</span>
                        👥
                    </button>

                    {/* Filter Button */}
                    <div className="filter-wrapper">
                        <button
                            className={`header-btn ${filterMode !== 'all' ? 'active' : ''}`}
                            onClick={() => setShowFilterMenu(!showFilterMenu)}
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                            </svg>
                        </button>

                        {showFilterMenu && (
                            <div className="filter-menu">
                                <button
                                    className={`filter-option ${filterMode === 'all' ? 'selected' : ''}`}
                                    onClick={() => { setFilterMode('all'); setShowFilterMenu(false); }}
                                >All</button>
                                <button
                                    className={`filter-option ${filterMode === 'completed' ? 'selected' : ''}`}
                                    onClick={() => { setFilterMode('completed'); setShowFilterMenu(false); }}
                                >✓ Done</button>
                                <button
                                    className={`filter-option ${filterMode === 'incomplete' ? 'selected' : ''}`}
                                    onClick={() => { setFilterMode('incomplete'); setShowFilterMenu(false); }}
                                >✕ Todo</button>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            {/* Days Header */}
            <div className="days-header">
                <div className="days-header-spacer"></div>
                {displayDays.map(day => (
                    <div key={day.date} className={`day-col ${day.isToday ? 'today' : ''}`}>
                        <span className="day-letter">{day.weekday}</span>
                        <span className="day-num">{day.dayNum}</span>
                    </div>
                ))}
                <div className="del-col"></div>
            </div>

            {/* Add Input */}
            <div className="add-section">
                <input
                    type="text"
                    className="add-input"
                    placeholder="New habit..."
                    value={inputValue}
                    onChange={(e) => setInputValue(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleAddHabit()}
                />
                <button className="add-btn" onClick={handleAddHabit} disabled={!inputValue.trim()}>+</button>
            </div>

            {/* Habits List */}
            {isLoading ? (
                <div className="loading"><div className="spinner" /></div>
            ) : filteredHabits.length === 0 ? (
                <div className="empty">
                    {habits.length === 0 ? 'No habits yet' : 'No matches'}
                </div>
            ) : (
                <ul className="habits-list">
                    {filteredHabits.map(habit => {
                        const isTodayDone = habit.habit?.history?.[TODAY] !== undefined;
                        const creator = getMember(habit.user_id);

                        return (
                            <li key={habit.id} className="habit-row">
                                {/* Checkbox + Name + Creator Color */}
                                <div className="habit-info">
                                    <div
                                        className={`checkbox ${isTodayDone ? 'checked' : ''}`}
                                        style={{ borderColor: creator.color, backgroundColor: isTodayDone ? creator.color : 'transparent' }}
                                        onClick={() => handleTodayToggle(habit)}
                                    />
                                    <span className="habit-text" style={{ color: creator.color }}>
                                        {habit.text}
                                    </span>
                                </div>

                                {/* Day Cells with colored marks */}
                                {displayDays.map(day => {
                                    const checkerId = habit.habit?.history?.[day.date];
                                    const isDone = checkerId !== undefined;
                                    const isFuture = isFutureDate(day.date);
                                    const isBeforeHabit = isBeforeCreation(day.date, habit.created_at);
                                    const isDisabled = isFuture || isBeforeHabit;
                                    const checkerColor = isDone ? getColorForUser(checkerId) : '#444';

                                    return (
                                        <div
                                            key={day.date}
                                            className={`cell ${isDisabled ? 'disabled' : ''}`}
                                            onClick={() => !isDisabled && handleDayToggle(habit, day.date)}
                                            title={isDone ? `Checked by User ${checkerId}` : ''}
                                        >
                                            <span
                                                className={`mark ${isDone ? 'done' : ''}`}
                                                style={{ color: checkerColor }}
                                            >
                                                {isBeforeHabit ? '·' : isDone ? '✓' : '○'}
                                            </span>
                                        </div>
                                    );
                                })}

                                {/* Delete */}
                                <button className="del-btn" onClick={() => handleDelete(habit.id)}>×</button>
                            </li>
                        );
                    })}
                </ul>
            )}

            {/* Members Modal */}
            {showMembersModal && (
                <>
                    <div className="overlay" onClick={() => setShowMembersModal(false)} />
                    <div className="modal">
                        <div className="modal-header">
                            <h2>Members ({members.length})</h2>
                            <button className="close-btn" onClick={() => setShowMembersModal(false)}>×</button>
                        </div>
                        <ul className="members-list">
                            {members.map(member => (
                                <li key={member.id} className="member-item">
                                    <span className="member-dot" style={{ backgroundColor: member.color }}></span>
                                    <span className="member-name">
                                        {member.name}
                                        {member.id === userId && <span className="you-badge"> (you)</span>}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </>
            )}

            {/* Click overlay to close filter */}
            {showFilterMenu && (
                <div className="overlay" onClick={() => setShowFilterMenu(false)} />
            )}
        </div>
    );
};
