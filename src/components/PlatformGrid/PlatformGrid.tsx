import { PLATFORMS } from '../../constants';
import { hapticSelection } from '../../helpers/telegram';
import type { SocialPlatform } from '../../types';

interface Props {
    selectedPlatform: SocialPlatform | null;
    onSelect: (platform: SocialPlatform) => void;
}

/** Horizontal scrollable platform pills — matches the reference UI */
export function PlatformGrid({ selectedPlatform, onSelect }: Props) {
    return (
        <div style={{
            display: 'flex',
            gap: 8,
            overflowX: 'auto',
            paddingBottom: 4,
            scrollbarWidth: 'none',
            msOverflowStyle: 'none',
            WebkitOverflowScrolling: 'touch',
        }}>
            {PLATFORMS.map(p => {
                const isActive = selectedPlatform === p.id;
                return (
                    <button
                        key={p.id}
                        onClick={() => {
                            hapticSelection();
                            onSelect(p.id);
                        }}
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: 6,
                            padding: '8px 14px',
                            background: isActive
                                ? 'var(--tg-theme-button-color, #3390ec)'
                                : 'var(--tg-theme-secondary-bg-color, #1e1e2e)',
                            border: 'none',
                            borderRadius: 20,
                            cursor: 'pointer',
                            transition: 'all 0.15s ease',
                            WebkitTapHighlightColor: 'transparent',
                            color: isActive
                                ? 'var(--tg-theme-button-text-color, #fff)'
                                : 'var(--tg-theme-text-color, #fff)',
                            whiteSpace: 'nowrap',
                            flexShrink: 0,
                            fontSize: 13,
                            fontWeight: 500,
                            fontFamily: 'inherit',
                        }}
                    >
                        <span style={{ fontSize: 16, lineHeight: 1 }}>{p.icon}</span>
                        <span>{p.label}</span>
                    </button>
                );
            })}
        </div>
    );
}
