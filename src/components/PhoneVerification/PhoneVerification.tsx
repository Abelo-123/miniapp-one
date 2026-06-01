import { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { getInitDataString } from '../../helpers/telegram';
import { Button, Input } from '@telegram-apps/telegram-ui';
import './PhoneVerification.css';

export function PhoneVerification() {
    const { user, showToast, refreshUser } = useApp();
    const [phoneNumber, setPhoneNumber] = useState(user?.phone_number || '');
    const [otp, setOtp] = useState('');
    const [step, setStep] = useState<'input' | 'verify'>(user?.phone_verified ? 'verified' : 'input');
    const [isLoading, setIsLoading] = useState(false);

    if (user?.phone_verified || step === 'verified') {
        return (
            <div className="phone-verified-badge">
                <span className="verified-icon">✅</span>
                <span className="verified-text">Phone Verified: {user?.phone_number}</span>
            </div>
        );
    }

    const handleSendOTP = async () => {
        if (!phoneNumber || phoneNumber.length < 9) {
            showToast('error', 'Please enter a valid phone number (e.g., 251911234567)');
            return;
        }

        setIsLoading(true);
        try {
            const initData = await getInitDataString();
            const res = await fetch(`${import.meta.env.VITE_NODE_API_URL || '/api'}/otp/send`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ initData, phone_number: phoneNumber })
            });
            const data = await res.json();
            if (data.success) {
                showToast('success', 'OTP Sent successfully via SMS!');
                setStep('verify');
            } else {
                showToast('error', data.error || 'Failed to send OTP');
            }
        } catch (e) {
            showToast('error', 'Connection failed. Try again.');
        } finally {
            setIsLoading(false);
        }
    };

    const handleVerifyOTP = async () => {
        if (!otp || otp.length < 4) {
            showToast('error', 'Please enter a valid OTP');
            return;
        }

        setIsLoading(true);
        try {
            const initData = await getInitDataString();
            const res = await fetch(`${import.meta.env.VITE_NODE_API_URL || '/api'}/otp/verify`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ initData, phone_number: phoneNumber, otp })
            });
            const data = await res.json();
            if (data.success) {
                showToast('success', 'Phone verified successfully!');
                setStep('verified');
                if (refreshUser) refreshUser();
            } else {
                showToast('error', data.error || 'Invalid OTP');
            }
        } catch (e) {
            showToast('error', 'Connection failed. Try again.');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="phone-verification-container">
            {step === 'input' && (
                <div className="verification-step">
                    <div className="verification-header">📱 Verify your Phone Number</div>
                    <Input 
                        type="tel"
                        placeholder="e.g. 251911234567"
                        value={phoneNumber}
                        onChange={(e: any) => setPhoneNumber(e.target.value.replace(/\D/g, ''))}
                        className="verification-input"
                    />
                    <Button 
                        size="m" 
                        stretched 
                        onClick={handleSendOTP} 
                        loading={isLoading}
                        style={{ marginTop: '10px' }}
                    >
                        Send OTP
                    </Button>
                </div>
            )}

            {step === 'verify' && (
                <div className="verification-step">
                    <div className="verification-header">Enter the 4-digit OTP sent to {phoneNumber}</div>
                    <Input 
                        type="number"
                        placeholder="Enter OTP"
                        value={otp}
                        onChange={(e: any) => setOtp(e.target.value.replace(/\D/g, ''))}
                        className="verification-input"
                    />
                    <div style={{ display: 'flex', gap: '10px', marginTop: '10px' }}>
                        <Button 
                            mode="outline" 
                            size="m" 
                            onClick={() => setStep('input')}
                            disabled={isLoading}
                        >
                            Back
                        </Button>
                        <Button 
                            size="m" 
                            stretched 
                            onClick={handleVerifyOTP} 
                            loading={isLoading}
                        >
                            Verify OTP
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
