import { List, Section, Button, Placeholder } from '@telegram-apps/telegram-ui';
import { useApp } from '../../context/AppContext';
import { hapticImpact } from '../../helpers/telegram';
import './TicketPage.css';

export function TicketPage() {
    const { tickets, marqueeText, showToast } = useApp();

    const handleOpenForm = () => {
        hapticImpact('light');
        showToast('info', 'Feature coming soon: Open Ticket Form');
    };

    return (
        <List className="ticket-page">
            {/* ── Marquee ── */}
            {marqueeText && (
                <div className="tp-marquee">
                    <div className="tp-marquee-inner">{marqueeText}</div>
                </div>
            )}

            {/* ── Action Button ── */}
            <Section>
                <div style={{ padding: '16px' }}>
                    <Button
                        size="l"
                        stretched
                        onClick={handleOpenForm}
                        mode="filled"
                    >
                        Open Ticket Form
                    </Button>
                </div>
            </Section>

            {/* ── Tickets Table ── */}
            <div className="tp-table-container">
                <div className="tp-table-header">
                    <div className="tp-col tp-col-status">STATUS</div>
                    <div className="tp-col tp-col-type">TYPE</div>
                    <div className="tp-col tp-col-subj">SUBJECT</div>
                    <div className="tp-col tp-col-action">ACTION</div>
                </div>

                {tickets.length === 0 ? (
                    <Placeholder description="No tickets found" />
                ) : (
                    tickets.map(ticket => (
                        <div key={ticket.id} className="tp-table-row">
                            <div className="tp-col tp-col-status">
                                <span className={`tp-status-badge ${ticket.status}`}>
                                    {ticket.status}
                                </span>
                            </div>
                            <div className="tp-col tp-col-type">{ticket.type}</div>
                            <div className="tp-col tp-col-subj">{ticket.subject}</div>
                            <div className="tp-col tp-col-action">{ticket.last_message || '-'}</div>
                        </div>
                    ))
                )}
            </div>
        </List>
    );
}
