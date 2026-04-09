export function LoadingOverlay({ visible }: { visible: boolean }) {
    if (!visible) return null;

    return (
        <div className="loading-overlay">
            <div className="skeleton-bar" style={{ width: '60px', height: '60px', borderRadius: '50%', marginBottom: '20px' }} />
            <div className="skeleton-bar" style={{ width: '120px', height: '18px' }} />
            <div className="loading-overlay__text" style={{ marginTop: '12px' }}>Loading...</div>
        </div>
    );
}
