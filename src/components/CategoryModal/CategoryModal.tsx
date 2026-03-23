import { useState, useMemo, useEffect, useRef } from 'react';
import { useApp } from '../../context/AppContext';
import { PLATFORMS } from '../../constants';

interface Props {
    categories: string[];
    onSelect: (category: string) => void;
    onClose: () => void;
    isLoading?: boolean;
}

export function CategoryModal({ categories, onSelect, onClose, isLoading }: Props) {
    const { services, selectedPlatform } = useApp();
    const [search, setSearch] = useState('');
    const [, forceUpdate] = useState(0);
    const listRef = useRef<HTMLDivElement>(null);

    // Force a re-render after mount to ensure layout is computed
    useEffect(() => {
        // Use double rAF to ensure the browser has painted
        const raf1 = requestAnimationFrame(() => {
            const raf2 = requestAnimationFrame(() => {
                forceUpdate(c => c + 1);
            });
            return () => cancelAnimationFrame(raf2);
        });
        return () => cancelAnimationFrame(raf1);
    }, []);

    // Derive categories internally as a fallback when prop is empty but services exist
    const resolvedCategories = useMemo(() => {
        // If categories prop has data, use it
        if (categories.length > 0) return categories;

        // Fallback: derive from services + selectedPlatform directly
        if (services.length === 0) return [];

        if (!selectedPlatform || selectedPlatform === 'top') return ['Top Services'];

        const allCategories = [...new Set(services.map(s => s.category))];
        const platformDef = PLATFORMS.find(p => p.id === selectedPlatform);

        if (!platformDef) return allCategories;

        if (selectedPlatform === 'other') {
            const majorKeywords = PLATFORMS
                .filter(p => p.id !== 'other' && p.id !== 'top')
                .flatMap(p => p.keywords);
            return allCategories.filter(cat => {
                const lower = cat.toLowerCase();
                return !majorKeywords.some(kw => lower.includes(kw));
            });
        }

        return allCategories.filter(cat => {
            const lower = cat.toLowerCase();
            return platformDef.keywords.some(kw => lower.includes(kw));
        });
    }, [categories, services, selectedPlatform]);

    const filtered = useMemo(() => {
        if (!search.trim()) return resolvedCategories;
        const q = search.toLowerCase();
        return resolvedCategories.filter(c => c.toLowerCase().includes(q));
    }, [resolvedCategories, search]);

    const showLoading = isLoading || (services.length === 0 && resolvedCategories.length === 0);

    // Scroll container height fix for WebViews that don't support vh/dvh
    const getListStyle = (): React.CSSProperties => {
        // Use a safe fixed pixel approach instead of viewport units
        const screenH = window.innerHeight || document.documentElement.clientHeight || 600;
        const maxListH = Math.max(screenH * 0.45, 200); // 45% of screen, minimum 200px
        return {
            overflowY: 'auto' as const,
            WebkitOverflowScrolling: 'touch' as any,
            minHeight: '150px',
            maxHeight: `${maxListH}px`,
            display: 'block',
            visibility: 'visible' as const,
        };
    };

    return (
        <div
            className="modal-overlay"
            onClick={onClose}
            style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 9999 }}
        >
            <div
                className="modal-sheet"
                onClick={e => e.stopPropagation()}
                style={{
                    minHeight: '300px', // Fixed pixel value instead of vh
                    maxHeight: `${Math.min((window.innerHeight || 600) * 0.75, 600)}px`,
                    display: 'flex',
                    flexDirection: 'column' as const,
                    overflow: 'hidden',
                }}
            >
                <div className="modal-header" style={{ flexShrink: 0 }}>
                    <span className="modal-title">Select Category</span>
                    <button className="modal-close" onClick={onClose}>✕</button>
                </div>

                <div className="modal-search" style={{ flexShrink: 0 }}>
                    <input
                        type="text"
                        className="modal-search-input"
                        placeholder="Search categories..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>

                <div
                    ref={listRef}
                    className="modal-list"
                    style={getListStyle()}
                >
                    {showLoading ? (
                        <div className="modal-empty" style={{ display: 'block', padding: '40px 20px', textAlign: 'center' }}>
                            Loading categories...
                        </div>
                    ) : filtered.length === 0 ? (
                        <div className="modal-empty" style={{ display: 'block', padding: '40px 20px', textAlign: 'center' }}>
                            No categories found
                        </div>
                    ) : (
                        filtered.map((cat) => (
                            <div
                                key={cat}
                                className="modal-item"
                                onClick={() => onSelect(cat)}
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    padding: '14px 16px',
                                    cursor: 'pointer',
                                    visibility: 'visible',
                                }}
                            >
                                <span className="modal-item-icon" style={{ marginRight: '12px', fontSize: '18px' }}>📂</span>
                                <span className="modal-item-text" style={{ fontSize: '14px' }}>{cat}</span>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
