import { useState, useEffect, useCallback, useMemo, type FC, memo } from 'react';
import { useSignal, initData } from '@telegram-apps/sdk-react';
import type { Todo, HabitMetadata, Member } from './types';
import { fetchTodos, addTodo, updateTodo, deleteTodo } from './api';
import './TodoPage.css';

// ═══════════════════════════════════════════════════════════════
// MEMBER COLORS - More vibrant & distinct
// ═══════════════════════════════════════════════════════════════

const MEMBER_COLORS = [
    '#3498db', // Bright Blue
    '#e74c3c', // Bright Red
    '#2ecc71', // Bright Green
    '#f1c40f', // Bright Yellow
    '#9b59b6', // Bright Purple
    '#1abc9c', // Turquoise
    '#e67e22', // Orange
    '#e91e63', // Pink
    '#2c3e50', // Navy Blue
    '#00d2ff', // Cyan
];

const getColorForUser = (userId: number): string => {
    // Scatter the IDs so sequential IDs get very different colors
    const hash = (Math.abs(userId) * 7 + 3);
    const index = hash % MEMBER_COLORS.length;
    return MEMBER_COLORS[index];
};

// ═══════════════════════════════════════════════════════════════
// UTILITIES
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
// MINI COMPONENTS FOR PERF
// ═══════════════════════════════════════════════════════════════

const HabitRow = memo(({
    habit,
    displayDays,
    userId,
    getMember,
    onToggle,
    onDelete
}: {
    habit: Todo,
    displayDays: any[],
    userId: number,
    getMember: (id: number, name?: string) => Member,
    onToggle: (todo: Todo, dateStr: string) => void,
    onDelete: (id: number) => void
}) => {
    const creator = getMember(habit.user_id, habit.user_name);
    const isTodayDone = habit.habit?.history?.[TODAY] !== undefined;

    return (
        <li className="habit-row">
            <div className="habit-info">
                {/* Visual Identity: Checkbox border = creator color */}
                <div
                    className={`checkbox ${isTodayDone ? 'checked' : ''}`}
                    style={{ borderColor: creator.color, backgroundColor: isTodayDone ? creator.color : 'transparent' }}
                    onClick={() => onToggle(habit, TODAY)}
                />
                <div className="habit-content">
                    <span className="habit-text">
                        {habit.text}
                    </span>
                    {/* Small creator indicator if not self */}
                    {habit.user_id !== userId && (
                        <span className="creator-dot" style={{ backgroundColor: creator.color }} title={creator.name} />
                    )}
                </div>
            </div>

            {displayDays.map(day => {
                const checkerId = habit.habit?.history?.[day.date];
                const isDone = checkerId !== undefined;
                const isDisabled = isFutureDate(day.date) || isBeforeCreation(day.date, habit.created_at);
                const checkerColor = isDone ? getColorForUser(checkerId) : '#444';

                return (
                    <div
                        key={day.date}
                        className={`cell ${isDisabled ? 'disabled' : ''}`}
                        onClick={() => !isDisabled && onToggle(habit, day.date)}
                    >
                        <span className={`mark ${isDone ? 'done' : ''}`} style={{ color: checkerColor }}>
                            {isDisabled ? '·' : isDone ? '✓' : '○'}
                        </span>
                    </div>
                );
            })}
            <button className="del-btn" onClick={() => onDelete(habit.id)}>×</button>
        </li>
    );
});

// ═══════════════════════════════════════════════════════════════
// MAIN PAGE
// ═══════════════════════════════════════════════════════════════

export const TodoPage: FC = () => {
    const initDataState = useSignal(initData.state);
    const userId = initDataState?.user?.id || 12345;
    const userName = initDataState?.user?.first_name || 'You';

    const [todos, setTodos] = useState<Todo[]>([]);
    const [inputValue, setInputValue] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [filterMode, setFilterMode] = useState<'all' | 'completed' | 'incomplete'>('all');
    const [showFilterMenu, setShowFilterMenu] = useState(false);
    const [showMembersModal, setShowMembersModal] = useState(false);

    const displayDays = useMemo(() => getLastDays(5), []);

    // ═══════════════════════════════════════════════════════════
    // SYNC LOGIC (Super Fast)
    // ═══════════════════════════════════════════════════════════

    const loadTodos = useCallback(async (silent = false) => {
        try {
            if (!silent) setIsLoading(true);
            const data = await fetchTodos(userId);
            // Only update state if data actually changed to prevent flutter
            setTodos(prev => JSON.stringify(prev) === JSON.stringify(data) ? prev : data);
        } catch (err) {
            console.error(err);
        } finally {
            if (!silent) setIsLoading(false);
        }
    }, [userId]);

    // Background polling every 8 seconds for collaboration
    useEffect(() => {
        loadTodos();
        const interval = setInterval(() => loadTodos(true), 8000);
        return () => clearInterval(interval);
    }, [loadTodos]);

    // ═══════════════════════════════════════════════════════════
    // MEMBERS LOGIC
    // ═══════════════════════════════════════════════════════════

    const members = useMemo((): Member[] => {
        const memberMap = new Map<number, Member>();

        memberMap.set(userId, {
            id: userId,
            name: userName,
            color: getColorForUser(userId)
        });

        todos.forEach(todo => {
            if (todo.user_id && !memberMap.has(todo.user_id)) {
                memberMap.set(todo.user_id, {
                    id: todo.user_id,
                    name: todo.user_name || `User ${todo.user_id}`,
                    color: getColorForUser(todo.user_id)
                });
            }
        });

        return Array.from(memberMap.values());
    }, [todos, userId, userName]);

    const getMember = useCallback((id: number, fallbackName?: string): Member => {
        return members.find(m => m.id === id) || {
            id,
            name: fallbackName || `User ${id}`,
            color: getColorForUser(id)
        };
    }, [members]);

    // ═══════════════════════════════════════════════════════════
    // ACTIONS (Optimistic Updates)
    // ═══════════════════════════════════════════════════════════

    const handleAddHabit = async () => {
        if (!inputValue.trim()) return;
        const textToSubmit = inputValue.trim();
        setInputValue('');

        // Optimistic Entry
        const tempId = -Math.floor(Math.random() * 1000000);
        const tempTodo: Todo = {
            id: tempId,
            user_id: userId,
            user_name: userName,
            text: textToSubmit,
            completed: false,
            created_at: new Date().toISOString(),
            habit: { frequency: 'daily', streak: 0, history: {} }
        };

        setTodos(prev => [tempTodo, ...prev]);

        try {
            const habitMetadata: HabitMetadata = { frequency: 'daily', streak: 0, history: {} };
            const newTodo = await addTodo(userId, textToSubmit, habitMetadata, userName);
            // Replace optimistic one with real one
            setTodos(prev => prev.map(t => t.id === tempId ? newTodo : t));
        } catch (err) {
            setTodos(prev => prev.filter(t => t.id !== tempId));
            console.error(err);
        }
    };

    const handleDayToggle = useCallback(async (todo: Todo, dateStr: string) => {
        if (!todo.habit || isFutureDate(dateStr) || isBeforeCreation(dateStr, todo.created_at)) return;

        const history = todo.habit.history || {};
        const isCompletedOnDate = history[dateStr] !== undefined;
        let newHistory = { ...history };

        if (isCompletedOnDate) {
            if (history[dateStr] === userId) delete newHistory[dateStr];
            else return;
        } else {
            newHistory[dateStr] = userId;
        }

        const newHabit = { ...todo.habit, history: newHistory };
        const backup = todos;
        setTodos(prev => prev.map(t => t.id === todo.id ? { ...t, habit: newHabit } : t));

        try {
            await updateTodo(userId, todo.id, { text: todo.text, habit: newHabit });
        } catch (err) {
            setTodos(backup);
            console.error(err);
        }
    }, [userId, todos]);

    const handleDelete = useCallback(async (todoId: number) => {
        if (!confirm('Delete this habit?')) return;
        const prevTodos = todos;
        setTodos(prev => prev.filter(t => t.id !== todoId));
        try { await deleteTodo(userId, todoId); }
        catch (err) { setTodos(prevTodos); console.error(err); }
    }, [userId, todos]);

    const filteredHabits = useMemo(() => {
        return todos.filter(t => t.habit).filter(habit => {
            const isTodayDone = habit.habit?.history?.[TODAY] !== undefined;
            if (filterMode === 'completed') return isTodayDone;
            if (filterMode === 'incomplete') return !isTodayDone;
            return true;
        });
    }, [todos, filterMode]);

    return (
        <div className="habits-container">
            <header className="habits-header">
                <h1 className="habits-title">Habits</h1>
                <div className="header-actions">
                    <button className="header-btn" onClick={() => setShowMembersModal(true)}>
                        <span className="member-count">{members.length}</span> 👥
                    </button>
                    <div className="filter-wrapper">
                        <button className={`header-btn ${filterMode !== 'all' ? 'active' : ''}`} onClick={() => setShowFilterMenu(!showFilterMenu)}>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                            </svg>
                        </button>
                        {showFilterMenu && (
                            <div className="filter-menu">
                                <button className={`filter-option ${filterMode === 'all' ? 'selected' : ''}`} onClick={() => { setFilterMode('all'); setShowFilterMenu(false); }}>All</button>
                                <button className={`filter-option ${filterMode === 'completed' ? 'selected' : ''}`} onClick={() => { setFilterMode('completed'); setShowFilterMenu(false); }}>Done</button>
                                <button className={`filter-option ${filterMode === 'incomplete' ? 'selected' : ''}`} onClick={() => { setFilterMode('incomplete'); setShowFilterMenu(false); }}>Todo</button>
                            </div>
                        )}
                    </div>
                </div>
            </header>

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

            <div className="add-section">
                <input
                    className="add-input"
                    placeholder="New habit..."
                    value={inputValue}
                    onChange={(e) => setInputValue(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleAddHabit()}
                />
                <button className="add-btn" onClick={handleAddHabit} disabled={!inputValue.trim()}>+</button>
            </div>

            {isLoading ? <div className="loading"><div className="spinner" /></div> : (
                <ul className="habits-list">
                    {filteredHabits.map(habit => (
                        <HabitRow
                            key={habit.id}
                            habit={habit}
                            displayDays={displayDays}
                            userId={userId}
                            getMember={getMember}
                            onToggle={handleDayToggle}
                            onDelete={handleDelete}
                        />
                    ))}
                </ul>
            )}

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
                                    <span className="member-name">{member.name}{member.id === userId && <span className="you-badge"> (you)</span>}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </>
            )}
            {showFilterMenu && <div className="overlay" onClick={() => setShowFilterMenu(false)} />}
        </div>
    );
};
