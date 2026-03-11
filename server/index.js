/**
 * Paxyo Mini App Backend — Node.js Entry Point
 */
import express from 'express';
import cors from 'cors';
import 'dotenv/config';

// Import Routes
import depositRouter from './routes/deposit.js';
import completeDepositRouter from './routes/completeDeposit.js';
import verifyDepositRouter from './routes/verifyDeposit.js';
import chapaCallbackRouter from './routes/chapaCallback.js';
import getDepositsRouter from './routes/getDeposits.js';
import getBalanceRouter from './routes/getBalance.js';

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Healthcheck
app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', time: new Date().toISOString() });
});

// Chapa Routes
app.use('/api/deposit', depositRouter);
app.use('/api/complete-deposit', completeDepositRouter);
app.use('/api/verify-deposit', verifyDepositRouter);
app.use('/api/chapa-callback', chapaCallbackRouter);

// User Data Routes
app.use('/api/deposits', getDepositsRouter);
app.use('/api/balance', getBalanceRouter);

// Start server
app.listen(PORT, () => {
    console.log(`🚀 Paxyo Backend running on port ${PORT}`);
    console.log(`🌍 Site URL: ${process.env.SITE_URL || 'http://localhost:3001'}`);
});
