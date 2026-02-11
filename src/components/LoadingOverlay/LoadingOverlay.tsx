import { Spinner } from '@telegram-apps/telegram-ui';

export function LoadingOverlay({ visible }: { visible: boolean }) {
    if (!visible) return null;

    return (
        <div style={{
            position: 'fixed',
            inset: 0,
            zIndex: 300,
            background: 'var(--tg-theme-secondary-bg-color, rgba(0,0,0,0.5))',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            opacity: 0.85,
        }}>
            <Spinner size="l" />
        </div>
    );
}
