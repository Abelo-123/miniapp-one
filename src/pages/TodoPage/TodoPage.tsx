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
    Checkbox,
    Modal
} from '@telegram-apps/telegram-ui';
import { Page } from '@/components/Page';
import type { Todo, FilterType, HabitMetadata } from './types';
import { fetchTodos, addTodo, updateTodo, deleteTodo } from './api';
import './TodoPage.css';

const formatDate = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const TODAY = formatDate(new Date());

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
    const [isHabit, setIsHabit] = useState(false);
    const [filter, setFilter] = useState<FilterType>('all');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [isMemberModalOpen, setIsMemberModalOpen] = useState(false); // Modal State

    // Helper to determine member color
    const getMemberColor = (uid: number) => getUserColor(uid);

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
            const habitMetadata: HabitMetadata | undefined = isHabit ? {
                frequency: 'daily',
                streak: 0,
                history: {}
            } : undefined;

            const newTodo = await addTodo(userId, inputValue.trim(), habitMetadata);
            setTodos(prev => [newTodo, ...prev]);
            setInputValue('');
            setIsHabit(false);
        } catch (err) {
            console.error(err);
        }
    };

    const handleToggle = async (todo: Todo) => {
        if (todo.habit) {
            // Habit Logic
            // We only support toggling TODAY for the main list view interaction, 
            // but let's make this generic if we want to toggle past dates from the grid.
            // For the main list checkbox, it toggles TODAY.
            handleHabitDateToggle(todo, TODAY);
            return;
        }

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

    const handleHabitDateToggle = async (todo: Todo, dateStr: string) => {
        if (!todo.habit) return;

        const history = todo.habit.history || {};
        const isCompletedOnDate = !!history[dateStr];

        let newHistory = { ...history };
        if (isCompletedOnDate) {
            delete newHistory[dateStr];
        } else {
            newHistory[dateStr] = userId; // Store who completed it
        }

        // Recalculate streak
        const hasToday = !!newHistory[TODAY];

        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.toISOString().split('T')[0];
        const hasYesterday = !!newHistory[yesterdayStr];

        let calculatedStreak = 0;

        if (hasToday) {
            calculatedStreak = 1;
            let d = new Date();
            while (true) {
                d.setDate(d.getDate() - 1);
                const dStr = d.toISOString().split('T')[0];
                if (newHistory[dStr]) {
                    calculatedStreak++;
                } else {
                    break;
                }
            }
        } else if (hasYesterday) {
            calculatedStreak = 0;
            let d = new Date();
            d.setDate(d.getDate() - 1); // Yesterday
            while (true) {
                const dStr = d.toISOString().split('T')[0];
                if (newHistory[dStr]) {
                    calculatedStreak++;
                    d.setDate(d.getDate() - 1);
                } else {
                    break;
                }
            }
        } else {
            calculatedStreak = 0;
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
            setTodos(todos); // Revert
            console.error(err);
        }
    }

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
    // Derived state
    const regularTodos = todos.filter(t => !t.habit);
    const habits = todos.filter(t => t.habit);

    const filteredTodos = regularTodos.filter(todo => {
        if (filter === 'active') return !todo.completed;
        if (filter === 'completed') return todo.completed;
        return true;
    });

    const getUserColor = (id: number) => {
        const colors = [
            '#FF6B6B', // Red-ish
            '#4ECDC4', // Teal
            '#45B7D1', // Sky Blue
            '#96CEB4', // Sage Green
            '#FF9F43', // Orange
            '#A29BFE', // Purple
            '#F8A5C2', // Pink
            '#546E7A', // Blue Grey
            '#D4AC0D', // Gold
            '#27AE60', // Emerald
        ];
        return colors[Math.abs(id) % colors.length];
    };

    // Helper to generate date range for a specific habit
    const getHabitDateRange = (createdAt: string) => {
        if (!createdAt) return [TODAY];
        // Parse the creation date. If it's "2024-01-28 10:00:00", we want 2024-01-28.
        const start = new Date(createdAt.replace(' ', 'T'));
        start.setHours(0, 0, 0, 0);

        const end = new Date();
        end.setHours(0, 0, 0, 0);

        const range: string[] = [];
        const d = new Date(start);

        // Safety check: if start is somehow in the future, just show today
        if (d > end) return [TODAY];

        let iterations = 0;
        while (d <= end && iterations < 365) { // Max 1 year history
            range.push(formatDate(d));
            d.setDate(d.getDate() + 1);
            iterations++;
        }
        return range.length > 0 ? range : [TODAY];
    };

    // Extract unique members for the modal
    const members = Array.from(new Set([
        userId, // Always include current user
        ...todos.map(t => Number(t.user_id)),
        ...todos.map(t => t.completed_by).filter((id): id is number => id !== undefined).map(Number),
        ...habits.flatMap(h => Object.values(h.habit?.history || {}).map(Number))
    ])).filter(id => !isNaN(id));



    return (
        <Page back={false}>
            <List style={{ background: 'var(--tgui--secondary_bg_color)', minHeight: '100vh' }}>
                <Section
                    header={
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <span>Team Tasks</span>
                            <Button size="s" mode="plain" onClick={() => setIsMemberModalOpen(true)}>
                                Member
                            </Button>
                        </div>
                    }
                    footer="Tasks are shared with everyone."
                >
                    {isMemberModalOpen && (
                        <Modal
                            header={<Modal.Header>Members Legend</Modal.Header>}
                            open={isMemberModalOpen}
                            onOpenChange={setIsMemberModalOpen}
                        >
                            <List style={{ padding: 16 }}>
                                <Section header="Team Members">
                                    {members.map(mid => (
                                        <Cell
                                            key={mid}
                                            before={
                                                <div style={{
                                                    width: 16,
                                                    height: 16,
                                                    borderRadius: '50%',
                                                    background: getMemberColor(mid)
                                                }} />
                                            }
                                        >
                                            <div style={{ display: 'flex', justifyContent: 'space-between', width: '100%' }}>
                                                <Text>User {mid} {Number(mid) === userId ? '(Me)' : ''}</Text>
                                                <Text style={{ color: getMemberColor(Number(mid)) }}>
                                                    Member
                                                </Text>
                                            </div>
                                        </Cell>
                                    ))}
                                </Section>
                                <Button stretched onClick={() => setIsMemberModalOpen(false)}>Close</Button>
                            </List>
                        </Modal>
                    )}
                    <Input
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        placeholder="Whats needs to be done?"
                        after={
                            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                <Checkbox
                                    checked={isHabit}
                                    onChange={() => setIsHabit(!isHabit)}
                                // label="Habit"  // Checkbox might not support label directly inside 'after' cleanly, depending on UI kit
                                />
                                <span style={{ fontSize: 12, marginRight: 4, color: 'var(--tgui--hint_color)' }}>Daily</span>
                                <Button
                                    size="s"
                                    onClick={handleAddTodo}
                                    disabled={!inputValue.trim()}
                                >
                                    Add
                                </Button>
                            </div>
                        }
                    />
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

                {habits.length > 0 && (
                    <Section header="Habit Tracker">
                        <List>
                            {habits.map(habit => {
                                const hRange = getHabitDateRange(habit.created_at);
                                return (
                                    <Cell
                                        key={habit.id}
                                        multiline
                                        after={
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                                <div style={{ textAlign: 'center', minWidth: 24 }}>
                                                    <span style={{ fontSize: 14 }}>🔥</span>
                                                    <div style={{ fontSize: 12 }}>{habit.habit?.streak || 0}</div>
                                                </div>
                                                <Button
                                                    mode="plain"
                                                    size="s"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handleDelete(habit.id);
                                                    }}
                                                    style={{ color: 'var(--tgui--destructive_text_color)', padding: 0 }}
                                                >
                                                    <DeleteIcon />
                                                </Button>
                                            </div>
                                        }
                                    >
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 }}>
                                            <div style={{
                                                width: 8,
                                                height: 8,
                                                borderRadius: '50%',
                                                background: getUserColor(habit.user_id),
                                                flexShrink: 0
                                            }} />
                                            <Text weight="2">{habit.text}</Text>
                                        </div>

                                        <div style={{
                                            display: 'flex',
                                            gap: 8,
                                            overflowX: 'auto',
                                            paddingBottom: 8,
                                            WebkitOverflowScrolling: 'touch',
                                            msOverflowStyle: 'none',
                                            scrollbarWidth: 'none'
                                        }}>
                                            {hRange.map(date => {
                                                const completedBy = habit.habit?.history?.[date];
                                                const isDone = completedBy !== undefined;
                                                const color = isDone ? getMemberColor(completedBy) : 'var(--tgui--secondary_bg_color)';
                                                const isToday = date === TODAY;

                                                return (
                                                    <div
                                                        key={date}
                                                        style={{
                                                            display: 'flex',
                                                            flexDirection: 'column',
                                                            alignItems: 'center',
                                                            gap: 4,
                                                            minWidth: 40
                                                        }}
                                                    >
                                                        <span style={{ fontSize: 10, color: 'var(--tgui--hint_color)', textAlign: 'center', height: 24, display: 'block' }}>
                                                            {date.slice(5).replace('-', '/')}
                                                            {isToday && <div>(Today)</div>}
                                                        </span>
                                                        <div
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                handleHabitDateToggle(habit, date);
                                                            }}
                                                            style={{
                                                                width: 28,
                                                                height: 28,
                                                                borderRadius: '8px',
                                                                background: color,
                                                                border: '1px solid var(--tgui--hint_color)',
                                                                cursor: 'pointer',
                                                                display: 'flex',
                                                                alignItems: 'center',
                                                                justifyContent: 'center',
                                                                transition: 'all 0.2s'
                                                            }}
                                                        >
                                                            {isDone && <span style={{ color: '#fff', fontSize: 14 }}>✓</span>}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </Cell>
                                );
                            })}
                        </List>
                    </Section>
                )}

                {(isLoading || regularTodos.length > 0 || habits.length === 0) && (
                    isLoading ? (
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
                    ) : filteredTodos.length === 0 && habits.length === 0 ? (
                        <div className="center-content">
                            <Title level="3">No tasks found</Title>
                            <Text>
                                {filter === 'all' ? 'Add a task to get started' :
                                    filter === 'active' ? 'You have completed everything!' :
                                        'No completed tasks yet'}
                            </Text>
                        </div>
                    ) : (
                        filteredTodos.length > 0 && (
                            <Section header={`${filter.charAt(0).toUpperCase() + filter.slice(1)} Tasks (${filteredTodos.length})`}>
                                {filteredTodos.map(todo => (
                                    <Cell
                                        key={todo.id}
                                        multiline
                                        before={
                                            <Checkbox
                                                checked={todo.completed}
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
                        )
                    )
                )}
            </List>
        </Page>
    );
};
