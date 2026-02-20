const TelegramBot = require('node-telegram-bot-api');
const axios = require('axios');
const express = require('express');
const cors = require('cors'); // Import cors
const fs = require('fs'); // Import file system module
const mysql = require('mysql2/promise'); // Import MySQL client

const lastMessages = new Map(); // Stores { chatId: { messageId, text, imageUrl } }

const BOT_TOKEN = process.env.BOT_TOKEN;

const bot = new TelegramBot(BOT_TOKEN);

// MySQL Connection Pool (using credentials from db.php)
const pool = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'paxyocom_paxyoV2',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// 🔹 Store chat IDs and message IDs
const userChatIds = new Set();
const sentMessageIds = new Map();

// Load user chat IDs from MySQL (auth table)
const loadUserChatIds = async () => {
    try {
        const [rows] = await pool.execute('SELECT tg_id FROM auth');
        rows.forEach((row) => {
            if (row.tg_id) userChatIds.add(row.tg_id.toString());
        });
        console.log(`Loaded ${rows.length} user chat IDs from MySQL.`);
    } catch (error) {
        console.error('Failed to load user chat IDs from MySQL:', error.message);
    }
};

const createAuthUrl = (user) => {
    const userData = {
        id: user.id,
        first_name: user.first_name,
        last_name: user.last_name || "",
        username: user.username || "",
        language_code: user.language_code || "en"
    };
    const userJson = JSON.stringify(userData);
    const authDate = Math.floor(Date.now() / 1000);
    const dataString = `user=${encodeURIComponent(userJson)}&auth_date=${authDate}`;
    return `https://paxyo.com/telegram_auth.php?tg_data=${encodeURIComponent(dataString)}`;
};

// Save or update a user in MySQL
const saveUserChatId = async (user) => {
    try {
        const tgId = user.id.toString();
        const firstName = user.first_name || '';
        const lastName = user.last_name || '';
        const username = user.username || '';

        await pool.execute(
            `INSERT INTO auth (tg_id, first_name, last_name, username, created_at, last_seen) 
             VALUES (?, ?, ?, ?, NOW(), NOW()) 
             ON DUPLICATE KEY UPDATE last_seen = NOW(), first_name = VALUES(first_name), last_name = VALUES(last_name), username = VALUES(username)`,
            [tgId, firstName, lastName, username]
        );

        userChatIds.add(tgId);
        console.log(`User ${tgId} saved/updated in MySQL.`);
    } catch (error) {
        console.error(`Failed to save user ${user.id} to MySQL:`, error.message);
    }
};

// Call loadUserChatIds when the bot starts
loadUserChatIds();

// Helper: Check if user has phone number (via your PHP API)
const checkUserPhone = async (tgId) => {
    try {
        const response = await axios.get(`https://paxyo.com/api_check_phone.php?tg_id=${tgId}`);
        return response.data.has_phone === true;
    } catch (error) {
        console.error('Error checking phone:', error.message);
        return true; // Assume they have phone to not block flow
    }
};

// Helper: Save phone number via PHP API
const saveUserPhone = async (tgId, phone) => {
    try {
        await axios.post('https://paxyo.com/api_save_phone.php', {
            tg_id: tgId,
            phone_number: phone
        });
        console.log(`Phone saved for ${tgId}: ${phone}`);
        return true;
    } catch (error) {
        console.error('Error saving phone:', error.message);
        return false;
    }
};

// 🔹 On /start, get user ID and send welcome
bot.onText(/\/start/, async (msg) => {
    const chatId = msg.chat.id;
    const username = msg.from.username;
    const firstName = msg.from.first_name;
    const user = msg.from;
    console.log(`New user started bot: ${username || 'Unknown'} (Chat ID: ${chatId})`);
    const dynamicUrl = createAuthUrl(user);

    // ✅ Save user to MySQL (non-blocking) and update in-memory Set
    saveUserChatId(user).catch(err => console.error('BG Save Error:', err));

    // Check if user has phone number
    const hasPhone = await checkUserPhone(chatId);

    if (!hasPhone) {
        // Request phone number first (Optional)
        const welcomeReqText = `👋 <b>Hey, welcome aboard ${firstName || 'friend'}!</b> 🇪🇹\n\n` +
            `📱 <i>(Optional)</i> Please share your phone number to enable direct support:`;

        await bot.sendMessage(chatId, welcomeReqText, {
            parse_mode: 'HTML',
            reply_markup: {
                keyboard: [[
                    { text: '📱 Share Phone Number', request_contact: true }
                ], [
                    { text: '⏭️ Skip for now' }
                ]],
                resize_keyboard: true,
                one_time_keyboard: true
            }
        });
    }

    // ✅ Send welcome image with Start App button (ALWAYS shown)
    try {
        const welcomeText = `👋 <b>Hey, welcome aboard ${firstName || 'friend'}!</b> 🇪🇹\n\n` +
            `You've just unlocked a space built for creators, dreamers and doers.\n\n` +
            `👥 Followers, 🎥 views, 💬 members እንዲሁም ከ750+ አገልግሎት ለሁሉም ሶሻል ሚዲያ በማይገመት ዋጋ።\n\n` +
            `ስለ አገልግሎቱ አዳዲስ መረጃ እንዲደርስዎ የቴሌግራም ቻናላችን ይቀላቀሉ።\n` +
            `📌 Updates: <a href="https://t.me/paxyo251">t.me/paxyo251</a>`;

        await bot.sendPhoto(chatId, 'https://i.ibb.co/nsW64qKb/pop.jpg', {
            caption: welcomeText,
            parse_mode: 'HTML',
            reply_markup: {
                inline_keyboard: [
                    [
                        {
                            text: 'Start App',
                            web_app: { url: 'https://paxyo.com/smm.php' }
                        }
                    ],
                    [
                        {
                            text: 'How to order',
                            callback_data: 'how_to_order'
                        }
                    ]
                ]
            }
        });

        console.log(`Single welcome message with all buttons sent to ${chatId}`);
    } catch (error) {
        console.error(`Failed to send welcome message to ${chatId}:`, error.message);
        // Minimal fallback
        await bot.sendMessage(chatId, `👋 Welcome! Launch App here:`, {
            reply_markup: {
                inline_keyboard: [[{ text: '🦾 Open App', web_app: { url: 'https://paxyo.com/smm.php' } }]]
            }
        }).catch(e => console.error('Fallback fail:', e.message));
    }
});

// Handle contact sharing (phone number)
bot.on('contact', async (msg) => {
    const chatId = msg.chat.id;
    const contact = msg.contact;

    // Only process if user shared their own contact
    if (contact.user_id === msg.from.id) {
        const phone = contact.phone_number;
        console.log(`Phone received from ${chatId}: ${phone}`);

        // Save phone number
        await saveUserPhone(chatId, phone);

        // Confirm and show app
        await bot.sendMessage(chatId, "✅ <b>Phone number saved!</b>\n\nThank you for sharing your contact. Our support team can now reach you directly if needed.", {
            parse_mode: 'HTML',
            reply_markup: { remove_keyboard: true }
        });

        // Send app button
        await bot.sendMessage(chatId, "🚀 Ready to explore? Launch the app below!", {
            parse_mode: 'HTML',
            reply_markup: {
                inline_keyboard: [[
                    { text: '🦾 Launch SMM App', web_app: { url: 'https://paxyo.com/smm.php' } }
                ]]
            }
        });
    }
});

// Handle all incoming messages to ensure users are captured
bot.on('message', async (msg) => {
    const chatId = msg.chat.id;

    // Capture user if not already in the set (to "avoid that" missing user issue)
    if (msg.from && !userChatIds.has(msg.from.id.toString())) {
        saveUserChatId(msg.from).catch(err => console.error('BG Save Error:', err));
    }

    if (msg.text === '⏭️ Skip for now') {
        await bot.sendMessage(chatId, "No problem! You can share your phone later.\n\n🚀 Launch the app to get started:", {
            parse_mode: 'HTML',
            reply_markup: {
                inline_keyboard: [[
                    { text: '🦾 Launch SMM App', web_app: { url: 'https://paxyo.com/smm.php' } }
                ]]
            }
        });

        // Remove keyboard
        await bot.sendMessage(chatId, ".", {
            reply_markup: { remove_keyboard: true }
        }).then(sentMsg => {
            // Delete the dot message
            bot.deleteMessage(chatId, sentMsg.message_id).catch(() => { });
        });
    }
});

bot.on('callback_query', async (query) => {
    if (query.data === 'how_to_order') {
        await bot.sendMessage(query.message.chat.id,
            'Watch this video to learn how to order:\n[How to order video](https://paxyo.com/mmmm.mp4)',
            { parse_mode: 'Markdown' }
        );
        await bot.answerCallbackQuery(query.id);
    }
});


// 🔹 Function to send a message with optional image
const sendTelegramMessage = async (chatId, text, imageUrl, type, amount, uid, tid) => {
    try {
        if (imageUrl && amount == null && uid == null) {
            const response = await bot.sendPhoto(chatId, imageUrl, {
                //caption: text,
                reply_markup: {
                    inline_keyboard: [
                        [
                            {
                                text: '🦾 Open App',
                                web_app: { url: 'https://paxyo.com/smm.php' }
                            }
                        ]
                    ]
                }
            });
            console.log(`Photo sent to chat ID ${chatId}:`, response);
            return response.message_id; // Return the message ID
        } else {
            if (type == null && amount == null && uid == null && tid == null) {
                const response = await bot.sendMessage(chatId, text, {
                    reply_markup: {
                        inline_keyboard: [
                            [
                                {
                                    text: '🦾 Open App',
                                    web_app: { url: 'https://paxyo.com/smm.php' }

                                }
                            ]
                        ]
                    }
                });
                console.log(`Message sent to chat ID ${chatId}:`, response);
                return response.message_id; // Return the message ID
            }
        }
    } catch (error) {
        console.error(`Error sending message to chat ID ${chatId}:`, error.response?.data || error.message);
        throw error;
    }
};

// 🔹 Function to broadcast a message to all users
const broadcastMessage = async (text, imageUrl) => {
    console.log(`Broadcasting message: "${text}" to ${userChatIds.size} users`);

    for (const chatId of userChatIds) {
        console.log(`Attempting to send message to chat ID: ${chatId}`);

        try {
            const messageId = await sendTelegramMessage(chatId, text, imageUrl, type = null);
            sentMessageIds.set(chatId, messageId);
            lastMessages.set(chatId, { messageId, text, imageUrl }); // Save full context
            // Store the message ID for this user
            console.log(`Message sent successfully to chat ID: ${chatId}`);

            // Send the inline button after each broadcast message
            await bot.sendMessage(chatId, text, {
                reply_markup: {
                    inline_keyboard: [
                        [
                            {
                                text: '🦾 Open App',
                                web_app: { url: 'https://paxyo.com/smm.php' }

                            }
                        ]
                    ]
                }
            });
        } catch (error) {
            console.error(`Failed to send message to ${chatId}:`, error.response?.data || error.message);
        }
    }
};

// 🔹 Function to delete all broadcast messages for all users
const deleteAllBroadcastMessages = async () => {
    console.log(`Deleting all broadcasted messages for ${sentMessageIds.size} users`);

    for (const [chatId, messageId] of sentMessageIds) {
        try {
            await bot.deleteMessage(chatId, messageId);
            console.log(`Message with ID ${messageId} deleted for chat ID: ${chatId}`);
        } catch (error) {
            console.error(`Failed to delete message for chat ID ${chatId}:`, error.response?.data || error.message);
        }
    }
};



// 🔹 Express server setup
const app = express();
app.use(cors()); // Enable CORS
app.use(express.json());

// Endpoint to broadcast messages (text + image)
app.post('/api/broadcast', async (req, res) => {
    const { message, imageUrl } = req.body;

    if (!message) {
        return res.status(400).send('Message is required');
    }

    try {
        await broadcastMessage(message, imageUrl);
        res.send('Message broadcasted successfully');
    } catch (error) {
        console.error('Failed to broadcast message:', error.message);
        res.status(500).send('Failed to broadcast message');
    }
});

// Endpoint to broadcast only image
app.post('/api/broadcastImage', async (req, res) => {
    const { imageUrl } = req.body;

    if (!imageUrl) {
        return res.status(400).send('Image URL is required');
    }

    try {
        await broadcastMessage('', imageUrl); // Send only the image
        res.send('Image broadcasted successfully');
    } catch (error) {
        console.error('Failed to broadcast image:', error.message);
        res.status(500).send('Failed to broadcast image');
    }
});

// Endpoint to send a message to a specific user
app.post('/api/sendToUser', async (req, res) => {
    const { chatId, message, imageUrl } = req.body;
    if (!chatId || !message) {
        return res.status(400).send('Chat ID and message are required');
    }
    try {
        const messageId = await sendTelegramMessage(chatId, message, imageUrl, type = null);
        res.send({ messageId }); // Return the message ID
    } catch (error) {
        console.error(`Failed to send message to user with Chat ID ${chatId}:`, error.message);
        res.status(500).send('Failed to send message to user');
    }
});

app.post('/api/sendToJohn', async (req, res) => {
    const amount = req.body.amount;
    const type = req.body.type;
    const uid = req.body.uid;
    const order = req.body.order;
    const ref = req.body.ref;
    const fp = req.body.panel;
    const pb = req.body.pb;
    //const tid = req.body.tid;
    const uuid = req.body.uuid;
    const uuuid = req.body.uuuid;
    const service = req.body.service;

    //const BOT_TOKEN = process.env.BOT_TOKENB;
    const BOT_TOKEN = '7860107567:AAGH_k1ZUQifJtqh2aprVSzJ4PbcqoBwWJ4';
    const bot = new TelegramBot(BOT_TOKEN);

    const userIds = [5928771903, 779060335, 460529558]; // Liffrst of user IDs

    try {
        for (const userId of userIds) {
            let msgText = '';

            if (type == "deposit" && uid != null) {
                msgText = `� <b>New Deposit Received</b>\n\n` +
                    `👤 User: <code>${uid}</code>\n` +
                    `💳 Method: <b>${uuid || 'Unknown'}</b>\n` +
                    `💵 Amount: <b>${amount} ETB</b>`;

            } else if (type == "newuser" && amount == null) {
                const refText = ref ? `\n🔗 Referred By: <code>${ref}</code>` : '';
                msgText = `👤 <b>New User Registered</b>\n\n` +
                    `🆔 ID: <code>${uid}</code>\n` +
                    `📛 Name: <b>${uuid}</b>` +
                    refText;

            } else if (type == "neworder") {
                const fromPanel = fp ? `\n🏢 Panel: <b>${fp}</b>` : '';
                const addrefer = pb ? `\n📉 Prev Balance: ${pb}` : '';
                msgText = `🚀 <b>New Order Placed</b>\n\n` +
                    `� User: <code>${uid}</code> (${uuid})\n` +
                    `📦 Service: <b>${service}</b>\n` +
                    `🔗 Link/Data: <code>${order}</code>\n` +
                    `💰 Cost: <b>${amount}</b>` +
                    fromPanel + addrefer;

            } else if (type == "ticket" && amount == null) {
                msgText = `🎫 <b>New Support Ticket</b>\n\n` +
                    `👤 User: <code>${uid}</code>\n` +
                    `<i>Check admin panel for details.</i>`;

            } else if (type == "phone") {
                msgText = `📞 <b>Phone Verification</b>\n\n` +
                    `� Number: <code>${amount}</code>\n` +
                    `👤 User: <code>${uuid}</code>`;

            } else if (type == "atempt") {
                msgText = `⚠️ <b>Payment Attempt</b>\n\n` +
                    `👤 User: <code>${uuuid}</code>\n` +
                    `💵 Amount: <b>${amount}</b>`;

            } else if (type == "withdrawl") {
                msgText = `💸 <b>Withdrawal Request</b>\n\n` +
                    `👤 User: <code>${uuid}</code>\n` +
                    `💵 Amount: <b>${amount}</b>`;

            } else if (type == "chat") {
                msgText = `💬 <b>Live Support Message</b>\n\n` +
                    `👤 User ID: <code>${uid}</code>\n\n` +
                    `<i>"${req.body.message}"</i>`;

            } else if (type == "refill") {
                msgText = `🔄 <b>Refill Request</b>\n\n` +
                    `👤 User: <code>${uid}</code>\n` +
                    `📦 Order ID: <code>${order}</code>\n` +
                    `🔢 Refill ID: <b>${uuid}</b>`;

            } else if (type == "order_error") {
                msgText = `❌ <b>Order Failed</b>\n\n` +
                    `👤 User: <code>${uid}</code>\n` +
                    `📦 Service: <b>${service}</b>\n` +
                    `⚠️ Error: <b>${req.body.error}</b>`;

            } else if (type == "system_error") {
                msgText = `🚨 <b>System Error</b>\n\n` +
                    `📂 File: <code>${req.body.file}</code>\n` +
                    `🔢 Line: <b>${req.body.line}</b>\n` +
                    `❌ Error: ${req.body.message}`;

            } else if (type == "refund") {
                msgText = `↩️ <b>Order Refunded</b>\n\n` +
                    `👤 User: <code>${uid}</code>\n` +
                    `📦 Order ID: <code>${order}</code>\n` +
                    `💵 Refunded: <b>${amount} ETB</b>`;

            } else if (type == "partial") {
                msgText = `📉 <b>Partial Refund</b>\n\n` +
                    `👤 User: <code>${uid}</code>\n` +
                    `📦 Order ID: <code>${order}</code>\n` +
                    `🔢 Remains: <b>${uuid}</b>\n` +
                    `💵 Refunded: <b>${amount} ETB</b>`;

            } else if (type == "admin_login") {
                msgText = `🔐 <b>Admin Login Detected</b>\n\n` +
                    `🌍 IP: <code>${req.body.ip}</code>\n` +
                    `📱 Device: <code>${req.body.ua}</code>\n` +
                    `🕒 Time: ${new Date().toLocaleString()}`;
            }

            if (msgText) {
                await bot.sendMessage(userId, msgText, { parse_mode: 'HTML' });
            }
        }
        res.send('Messages sent successfully'); // Return success response
    } catch (error) {
        console.error(`Failed to send message to users:`, error.message);
        res.status(500).send('Failed to send message to users');
    }
});


// Endpoint to delete a message for all users
app.post('/api/deleteAllMessages', async (req, res) => {
    try {
        await deleteAllBroadcastMessages();
        res.send('All broadcast messages deleted successfully');
    } catch (error) {
        console.error('Failed to delete all messages:', error.message);
        res.status(500).send('Failed to delete all messages');
    }
});

app.post('/api/deleteByContent', async (req, res) => {
    const { message, imageUrl } = req.body;

    if (!message && !imageUrl) {
        return res.status(400).send('Either message text or image URL must be provided');
    }

    let deletedCount = 0;

    for (const [chatId, msgData] of lastMessages) {
        const matchesText = message && msgData.text === message;
        const matchesImage = imageUrl && msgData.imageUrl === imageUrl;

        if (matchesText || matchesImage) {
            try {
                await bot.deleteMessage(chatId, msgData.messageId);
                deletedCount++;
            } catch (err) {
                console.error(`Failed to delete for ${chatId}`, err.message);
            }
        }
    }

    res.send(`Deleted ${deletedCount} matching messages`);
});

app.get('/api/getServicesofgodofpanel', async (req, res) => {
    try {
        const [rows] = await pool.execute(
            'SELECT bigvalueforgodofpanel FROM panel WHERE owner = ? AND `key` = ? LIMIT 1',
            [6528707984, 'disabled']
        );

        if (rows.length === 0) {
            return res.status(404).json({ error: 'Data not found' });
        }

        res.json(rows[0]);
    } catch (error) {
        console.error('Error fetching data from MySQL:', error.message);
        res.status(500).json({ error: 'Error fetching data', message: error.message });
    }
});

app.get('/api/getServicesofsmma', async (req, res) => {
    try {
        const [rows] = await pool.execute(
            'SELECT bigvalueforsmma FROM panel WHERE owner = ? AND `key` = ? LIMIT 1',
            [6528707984, 'disabled']
        );

        if (rows.length === 0) {
            return res.status(404).json({ error: 'Data not found' });
        }

        res.json(rows[0]);
    } catch (error) {
        console.error('Error fetching data from MySQL:', error.message);
        res.status(500).json({ error: 'Error fetching data', message: error.message });
    }
});

app.get('/api/getrecoforgodofpanel', async (req, res) => {
    try {
        const [rows] = await pool.execute(
            "SELECT message FROM adminmessage WHERE father = ? AND `from` = ?",
            [6528707984, 'Admin-re-forgodofpanel']
        );

        res.json(rows);
    } catch (error) {
        console.error('Error fetching recommended services from MySQL:', error.message);
        res.status(500).json({ error: 'Error fetching data', message: error.message });
    }
});


// Start the Express server
const PORT = process.env.PORT || 3000;

app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});


const WEBHOOK_URL = 'https://paxyo-bot-ywuk.onrender.com'; // Removed /webhook prefix to simplify

bot.setWebHook(`${WEBHOOK_URL}/bot${BOT_TOKEN}`);

app.post(`/bot${BOT_TOKEN}`, (req, res) => {
    bot.processUpdate(req.body);
    res.sendStatus(200);
});

// Set the menu button to open the Mini App
// bot.setChatMenuButton({
//     menu_button: JSON.stringify({
//         type: 'web_app',
//         text: 'Open',
//         web_app: {
//             url: 'https://paxyo.com/smm.php'
//         }
//     })
// });
