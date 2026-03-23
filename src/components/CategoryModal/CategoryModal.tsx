import { useState, useMemo } from 'react';
import { useApp } from '../../context/AppContext';

interface Props {
    categories: string[];
    onSelect: (category: string) => void;
    onClose: () => void;
    isLoading?: boolean;
}

export function CategoryModal({ categories, onSelect, onClose, isLoading }: Props) {
    const { services } = useApp();
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        if (!search.trim()) return categories;
        const q = search.toLowerCase();
        return categories.filter(c => c.toLowerCase().includes(q));
    }, [categories, search]);

    // Show loading only when:
    // 1. Explicitly told to (isLoading prop), OR
    // 2. Services haven't loaded yet (services array is empty) AND no categories
    const servicesNotLoaded = services.length === 0;
    const showLoading = isLoading || (servicesNotLoaded && categories.length === 0);

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div
                className="modal-sheet"
                onClick={e => e.stopPropagation()}
                style={{ minHeight: '40vh' }}
            >
                <div className="modal-header">
                    <span className="modal-title">Select Category</span>
                    <button className="modal-close" onClick={onClose}>✕</button>
                </div>

                <div className="modal-search">
                    <input
                        type="text"
                        className="modal-search-input"
                        placeholder="Search categories..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>

                <div className="modal-list">
                    {showLoading ? (
                        <div className="modal-empty">Loading categories...</div>
                    ) : filtered.length === 0 ? (
                        <div className="modal-empty">No categories found</div>
                    ) : (
                        filtered.map(cat => (
                            <div
                                key={cat}
                                className="modal-item"
                                onClick={() => onSelect(cat)}
                            >
                                <span className="modal-item-icon">📂</span>
                                <span className="modal-item-text">{cat}</span>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
