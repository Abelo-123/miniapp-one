import fetch from 'node-fetch';
import dotenv from 'dotenv';
dotenv.config();

const botToken = process.env.BOT_TOKEN || '7547947738:AAFCrTdxp5EmLg5f39rrKn8kO5kLhA0Tekw';

async function checkWebhook() {
    try {
        console.log("Checking webhook for bot token prefix:", botToken.split(':')[0]);
        const res = await fetch(`https://api.telegram.org/bot${botToken}/getWebhookInfo`);
        const data = await res.json();
        console.log("Webhook Info:", JSON.stringify(data, null, 2));
    } catch (e) {
        console.error("Error checking webhook:", e);
    }
}

checkWebhook();
