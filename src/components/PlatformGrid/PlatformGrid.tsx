
import { PLATFORMS } from '../../constants';
import type { SocialPlatform } from '../../types';

interface Props {
    selectedPlatform: SocialPlatform | null;
    onSelect: (platform: SocialPlatform) => void;
}

export function PlatformGrid({ selectedPlatform, onSelect }: Props) {
    return (
        <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(4, 1fr)',
            gap: 8,
        }}>
            {PLATFORMS.map(p => {
                const isActive = selectedPlatform === p.id;
                return (
                    <button
                        key={p.id}
                        onClick={() => onSelect(p.id)}
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: 4,
                            padding: '14px 6px',
                            background: isActive
                                ? 'var(--tg-theme-button-color, #6c5ce7)'
                                : 'var(--tg-theme-secondary-bg-color, #1e1e1e)',
                            border: '1px solid',
                            borderColor: isActive
                                ? 'var(--tg-theme-button-color, #6c5ce7)'
                                : 'var(--tg-theme-hint-color, #333)',
                            borderRadius: 12,
                            cursor: 'pointer',
                            transition: 'all 0.15s ease',
                            WebkitTapHighlightColor: 'transparent',
                            color: isActive
                                ? 'var(--tg-theme-button-text-color, #fff)'
                                : 'var(--tg-theme-text-color, #fff)',
                        }}
                    >
                        <span style={{ fontSize: 24, lineHeight: 1 }}>{p.icon}</span>
                        <span style={{
                            fontSize: 11,
                            fontWeight: 500,
                            whiteSpace: 'nowrap',
                            opacity: isActive ? 1 : 0.7,
                        }}>
                            {p.label}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
