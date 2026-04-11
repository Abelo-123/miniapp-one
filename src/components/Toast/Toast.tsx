import { useApp } from '../../context/AppContext';

const ICONS: Record<string, string> = {
    success: 'fa-check-circle',
    error: 'fa-exclamation-circle',
    info: 'fa-info-circle',
};

export function ToastContainer() {
    const { toasts, removeToast } = useApp();

    if (toasts.length === 0) return null;

    const latest = toasts[toasts.length - 1];

    return (
        <div className="top-toast-container">
            <div className={`top-toast ${latest.type}`} key={latest.id}>
                <i className={`fa ${ICONS[latest.type] || 'fa-info-circle'} top-toast-icon`}></i>
                <span className="top-toast-message">{latest.message}</span>
                <button className="top-toast-close" onClick={() => removeToast(latest.id)}>
                    <i className="fa fa-times"></i>
                </button>
            </div>
        </div>
    );
}