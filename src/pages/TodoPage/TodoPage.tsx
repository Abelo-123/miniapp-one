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
    SegmentedControl,
    Checkbox,
    Modal
} from '@telegram-apps/telegram-ui';
import { Page } from '@/components/Page';
import type { Todo, FilterType, HabitMetadata } from './types';
import { fetchTodos, addTodo, updateTodo, deleteTodo } from './api';
import './TodoPage.css';

const TODAY = new Date().toISOString().split('T')[0];

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

    // Helper to determine special user
    const SPECIFIC_USER_ID = 12345; // Hardcoded as per request "Red = Specific", assuming Admin/Me
    const getMemberColor = (uid: number) => uid === SPECIFIC_USER_ID ? '#FF6B6B' : '#4ECDC4'; // Red vs Blue

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
        // Basic streak calc: iterate backwards from TODAY. 
        // If today is completed, streak starts including today.
        // If today is NOT completed, streak starts from yesterday (if completed).
        // Actually for "current streak", if today is not done, but yesterday was, streak is preserved.
        // If yesterday was missed, streak is 0.

        newHistory.sort(); // Keep sorted

        let streak = 0;
        let checkDate = new Date();
        // check today
        const todayStr = checkDate.toISOString().split('T')[0];
        if (newHistory.includes(todayStr)) {
            streak++;
        }

        // check past days
        // We look for consecutive days backwards
        const historyKeys = Object.keys(newHistory);
        while (true) {
            checkDate.setDate(checkDate.getDate() - 1);
            const dStr = checkDate.toISOString().split('T')[0];
            if (newHistory[dStr]) {
                streak++;
            } else {
                // If we broke the chain, handled by the loop end
                // BUT: if today is NOT done, we still might have a streak from yesterday.
                // The above logic only counts IF connected to today.
                // Habit trackers usually show streak:
                // If done today: 5
                // If not done today (but done yesterday): 4
                // If not done yesterday: 0
                break;
            }
        }

        // Retry streak calculation to be robust:
        // 1. Check if today is done. If so, count = 1 + consecutive backwards from yesterday.
        // 2. If today NOT done. Check if yesterday is done. If so, count = consecutive backwards from yesterday.
        // 3. Else 0.

        const sortedHistory = [...newHistory].sort();
        const hasToday = sortedHistory.includes(TODAY);

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
                // Check if key exists in history
                if (newHistory[d.toISOString().split('T')[0]]) {
                    calculatedStreak++;
                } else {
                    break;
                }
            }
        } else if (hasYesterday) {
            calculatedStreak = 0; // Streak calculation logic is subjective. If today is missed, streak implies *current* streak logic. 
            // If we want "current streak including valid pending", standard is: if today not done but yesterday done, streak is active (from yesterday).
            // If today done: streak includes today.
            // Here we just count backwards from *yesterday*.
            let d = new Date(); // Start today
            d.setDate(d.getDate() - 1); // Yesterday
            // We know yesterday is present
            while (true) {
                if (newHistory[d.toISOString().split('T')[0]]) {
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
        const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEEAD', '#FFCC5C', '#FF9671'];
        return colors[Math.abs(id) % colors.length];
    };

    // Calculate date range for the grid
    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0);
    const todayStr = todayDate.toISOString().split('T')[0];

    // Find the earliest date: Min of (created_at OR history items)
    const earliestDate = habits.reduce((min, h) => {
        // Start with task creation date
        let taskMin = h.created_at ? h.created_at.split('T')[0].split(' ')[0] : todayStr;

        // Check history
        const historyKeys = Object.keys(h.habit?.history || {});
        if (historyKeys.length > 0) {
            const histMin = historyKeys.reduce((m, d) => d < m ? d : m, todayStr);
            if (histMin < taskMin) taskMin = histMin;
        }

        return taskMin < min ? taskMin : min;
    }, todayStr);

    // Default to at least 7 days ago if no history or history is recent
    let startDate = new Date(earliestDate);
    const minStart = new Date(todayDate);
    minStart.setDate(minStart.getDate() - 6);

    if (startDate > minStart) {
        startDate = minStart;
    }

    const dateRange: string[] = [];
    const d = new Date(startDate);
    while (d <= todayDate) {
        dateRange.push(d.toISOString().split('T')[0]);
        d.setDate(d.getDate() + 1);
    }

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
                        <div style={{
                            padding: 16,
                            background: 'var(--tgui--bg_color)',
                            borderBottom: '1px solid var(--tgui--secondary_bg_color)',
                            marginBottom: 16
                        }}>
                            <Title level="3" style={{ marginBottom: 12 }}>Member Colors</Title>
                            <div style={{ display: 'flex', gap: 16, marginBottom: 16 }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                    <div style={{ width: 16, height: 16, borderRadius: '50%', background: '#FF6B6B' }} />
                                    <Text>Specific User (ID {SPECIFIC_USER_ID})</Text>
                                </div>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                    <div style={{ width: 16, height: 16, borderRadius: '50%', background: '#4ECDC4' }} />
                                    <Text>Other Members</Text>
                                </div>
                            </div>
                            <Button size="s" stretched onClick={() => setIsMemberModalOpen(false)}>Close</Button>
                        </div>
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
                        <div style={{ overflowX: 'auto', paddingBottom: 10 }}>
                            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 14 }}>
                                <thead>
                                    <tr>
                                        <th style={{ textAlign: 'left', padding: 8 }}>Task</th>
                                        {dateRange.map(date => (
                                            <th key={date} style={{ padding: 4, textAlign: 'center', fontSize: 10, minWidth: 40 }}>
                                                {date.slice(5).replace('-', '/')}
                                                <br />
                                                {date === TODAY ? '(Today)' : ''}
                                            </th>
                                        ))}
                                        <th style={{ padding: 8, textAlign: 'center' }}>🔥</th>
                                        <th style={{ padding: 8 }}></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {habits.map(habit => (
                                        <tr key={habit.id} style={{ borderTop: '1px solid var(--tgui--secondary_bg_color)' }}>
                                            <td style={{ padding: 8, maxWidth: 120, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                                {habit.text}
                                            </td>
                                            {dateRange.map(date => {
                                                const completedBy = habit.habit?.history?.[date]; // Look up user ID
                                                const isDone = completedBy !== undefined;
                                                const color = isDone ? getMemberColor(completedBy) : 'var(--tgui--secondary_bg_color)';

                                                return (
                                                    <td key={date} style={{ textAlign: 'center', padding: 4 }}>
                                                        <div
                                                            onClick={() => handleHabitDateToggle(habit, date)}
                                                            style={{
                                                                width: 20,
                                                                height: 20,
                                                                borderRadius: '50%',
                                                                background: color,
                                                                border: '1px solid var(--tgui--hint_color)',
                                                                margin: '0 auto',
                                                                cursor: 'pointer'
                                                            }}
                                                        >
                                                            {isDone && <span style={{ color: '#fff', fontSize: 12, lineHeight: '20px' }}>✓</span>}
                                                        </div>
                                                    </td>
                                                );
                                            })}
                                            <td style={{ textAlign: 'center', padding: 8 }}>
                                                {habit.habit?.streak || 0}
                                            </td>
                                            <td style={{ textAlign: 'center', padding: 8 }}>
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
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
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
