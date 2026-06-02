import { Router } from 'express';
import pool from '../config/database.js';
import { getTelegramUserId } from '../lib/auth.js';

const router = Router();
const SMS_API_KEY = 'QDTMVU3H8Y8ALO4MGY0FROM54E8CY7CZ:949';
const SMS_API_URL = 'https://smsethiopia.com/api/sms/send';

// Generate a 4-digit OTP
const generateOTP = () => Math.floor(1000 + Math.random() * 9000).toString();

// 1. Send OTP Route
router.post('/send', async (req, res) => {
    const { initData, phone_number } = req.body;
    const tgId = getTelegramUserId(initData);
    
    if (!tgId) return res.status(401).json({ success: false, error: 'Unauthorized' });
    if (!phone_number || phone_number.length < 9) return res.json({ success: false, error: 'Invalid phone number' });

    try {
        const otp = generateOTP();
        const expiresAt = new Date(Date.now() + 10 * 60000); // 10 minutes

        // Save OTP to DB
        await pool.execute(`
            INSERT INTO otp_verifications (tg_id, phone_number, otp, expires_at) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            phone_number = VALUES(phone_number), 
            otp = VALUES(otp), 
            expires_at = VALUES(expires_at)
        `, [tgId, phone_number, otp, expiresAt]);

        // Send SMS
        const smsResponse = await fetch(SMS_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'KEY': SMS_API_KEY
            },
            body: JSON.stringify({
                msisdn: phone_number,
                text: `Your Paxyo verification code is: ${otp}`
            })
        });

        const responseText = await smsResponse.text();
        console.log('--- SMS API RAW RESPONSE ---');
        console.log('Status Code:', smsResponse.status);
        console.log('Response Body:', responseText);
        console.log('----------------------------');

        let smsResult;
        try {
            smsResult = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse SMS API response as JSON');
            return res.json({ success: false, error: `Provider returned non-JSON: ${responseText.substring(0, 100)}` });
        }
        
        if (smsResult.status === 'success' || smsResult.success || smsResult.sent === true || smsResult.sent === 'true') {
            return res.json({ success: true, message: 'OTP sent successfully' });
        } else {
            console.error('SMS API Error JSON:', smsResult);
            return res.json({ 
                success: false, 
                error: `Failed to send SMS through provider. Details: ${JSON.stringify(smsResult)}` 
            });
        }
    } catch (error) {
        console.error('OTP Send Error:', error);
        return res.json({ success: false, error: `Internal server error: ${error.message}` });
    }
});

// 2. Verify OTP Route
router.post('/verify', async (req, res) => {
    const { initData, phone_number, otp } = req.body;
    const tgId = getTelegramUserId(initData);
    
    if (!tgId) return res.status(401).json({ success: false, error: 'Unauthorized' });
    if (!otp) return res.json({ success: false, error: 'OTP is required' });

    try {
        const [rows] = await pool.execute(
            'SELECT * FROM otp_verifications WHERE tg_id = ? AND phone_number = ? AND otp = ?',
            [tgId, phone_number, otp]
        );

        if (rows.length === 0) {
            return res.json({ success: false, error: 'Invalid or expired OTP' });
        }

        const verification = rows[0];
        
        if (new Date() > new Date(verification.expires_at)) {
            return res.json({ success: false, error: 'OTP has expired' });
        }

        // OTP is valid! Update the user's auth record
        await pool.execute(
            'UPDATE auth SET phone_number = ?, phone_verified = 1 WHERE tg_id = ?',
            [phone_number, tgId]
        );

        // Clean up the used OTP
        await pool.execute('DELETE FROM otp_verifications WHERE tg_id = ?', [tgId]);

        return res.json({ success: true, message: 'Phone number verified successfully!' });
    } catch (error) {
        console.error('OTP Verify Error:', error);
        return res.json({ success: false, error: 'Internal server error' });
    }
});

export default router;
