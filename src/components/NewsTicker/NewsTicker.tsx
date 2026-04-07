import { useApp } from '../../context/AppContext';

export function NewsTicker() {
    const { marqueeText } = useApp();

    if (!marqueeText) return null;

    return (
        <div className="news-ticker">
            <span className="news-ticker__badge">NEWS</span>
            <div className="news-ticker__text-wrapper">
                <span className="news-ticker__text">{marqueeText}</span>
            </div>
        </div>
    );
}
