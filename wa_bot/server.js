const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const cors = require('cors');
const mysql = require('mysql2');
const cron = require('node-cron');
const fs = require('fs');

const app = express();
app.use(cors());
app.use(express.json());

const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'if0_41035429_arifa'
});

db.connect((err) => {
    if (err) {
        console.error('\n=============================================');
        console.error('ERROR KONEKSI DATABASE DI WA BOT:');
        console.error(err.message);
        console.error('INFO: Mohon ganti username & password database di file server.js (baris 13-18) menyesuaikan dengan database aaPanel kamu!');
        console.error('=============================================\n');
    } else {
        console.log('Connected to MySQL Database.');
    }
});

const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        headless: true,
        // Di aaPanel/Linux, puppeteer sering gagal mengunduh Chromium sendiri.
        // Kita gunakan Google Chrome / Chromium bawaan sistem yang sudah diinstall sebelumnya.
        executablePath: '/usr/bin/google-chrome', // Coba gunakan Chrome bawaan sistem
        args: [
            '--no-sandbox', 
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--single-process',
            '--disable-gpu'
        ]
    }
});

let qrCodeData = '';
let isReady = false;
let initError = null;

client.on('qr', (qr) => {
    console.log('QR Received. Generate it in Admin Panel.');
    qrcode.toDataURL(qr, (err, url) => {
        qrCodeData = url;
    });
});

client.on('ready', () => {
    console.log('WhatsApp Bot is ready!');
    isReady = true;
    qrCodeData = ''; // Clear QR since it's connected
});

client.on('authenticated', () => {
    console.log('Authenticated successfully!');
});

client.on('disconnected', (reason) => {
    console.log('Client was logged out', reason);
    isReady = false;
    client.initialize().catch(err => { initError = err.message; });
});

client.initialize().catch(err => {
    console.error('Failed to initialize client:', err);
    initError = err.message;
});

app.get('/status', (req, res) => {
    res.json({
        ready: isReady,
        qr: qrCodeData,
        error: initError
    });
});

app.post('/send-message', async (req, res) => {
    if (!isReady) {
        return res.status(400).json({ error: 'WhatsApp bot is not ready yet.' });
    }

    let { number, message, pdf_path } = req.body;
    if (!number) return res.status(400).json({ error: 'Number is required' });
    
    // Format number to 628xxx@c.us
    if (number.startsWith('0')) {
        number = '62' + number.substring(1);
    }
    if (!number.endsWith('@c.us')) {
        number += '@c.us';
    }

    try {
        let sent = false;
        if (pdf_path && fs.existsSync(pdf_path)) {
            const media = MessageMedia.fromFilePath(pdf_path);
            await client.sendMessage(number, media, { caption: message });
            sent = true;
        } else {
            await client.sendMessage(number, message);
            sent = true;
        }
        res.json({ success: true, message: 'Message sent!' });
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: err.message });
    }
});

// Cron job to run every hour to check reminders
// We will check 3 days, 1 day, and today
cron.schedule('0 * * * *', () => {
    console.log('Running reminder cron job...');
    if (!isReady) return;

    const query = `
        SELECT rm.*, p.no_telepon 
        FROM rekam_medis rm 
        JOIN pasien p ON rm.id_pasien = p.id 
        WHERE rm.kunjungan_berikutnya IS NOT NULL 
        AND p.no_telepon IS NOT NULL
        AND rm.kunjungan_berikutnya >= CURDATE()
    `;

    db.query(query, async (err, results) => {
        if (err) {
            console.error(err);
            return;
        }

        const now = new Date();
        const currentHour = now.getHours();

        for (const row of results) {
            let targetDate = new Date(row.kunjungan_berikutnya);
            // Ensure targetDate is compared correctly
            const timeDiff = targetDate.getTime() - now.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            let number = row.no_telepon;
            if (!number) continue;
            if (number.startsWith('0')) number = '62' + number.substring(1);
            if (!number.endsWith('@c.us')) number += '@c.us';

            // 3 Days reminder (e.g., at 08:00 AM)
            if (daysDiff === 3 && currentHour >= 8 && row.reminder_3_days === 0) {
                const msg = `Halo ${row.nama_pasien}, ini pengingat dari Klinik Gigi Anggeraja. Jadwal kontrol Anda 3 hari lagi pada tanggal ${row.kunjungan_berikutnya}. Mohon disiapkan.`;
                await client.sendMessage(number, msg).catch(console.error);
                db.query(`UPDATE rekam_medis SET reminder_3_days = 1 WHERE id = ${row.id}`);
            }
            
            // 1 Day reminder (e.g., at 08:00 AM)
            if (daysDiff === 1 && currentHour >= 8 && row.reminder_1_day === 0) {
                const msg = `Halo ${row.nama_pasien}, ini pengingat dari Klinik Gigi Anggeraja. Jadwal kontrol Anda besok pada tanggal ${row.kunjungan_berikutnya}. Jangan sampai lupa ya!`;
                await client.sendMessage(number, msg).catch(console.error);
                db.query(`UPDATE rekam_medis SET reminder_1_day = 1 WHERE id = ${row.id}`);
            }

            // Today reminder ("beberapa jam sebelum") (e.g., at 06:00 AM)
            if (daysDiff === 0 && currentHour >= 6 && row.reminder_today === 0) {
                const msg = `Halo ${row.nama_pasien}, hari ini adalah jadwal kontrol gigi Anda di Klinik Gigi Anggeraja. Mohon datang tepat waktu sesuai jadwal klinik. Terima kasih!`;
                await client.sendMessage(number, msg).catch(console.error);
                db.query(`UPDATE rekam_medis SET reminder_today = 1 WHERE id = ${row.id}`);
            }
        }
    });
});

const PORT = process.env.PORT || 3001; // Menggunakan port dari aaPanel, atau 3001 jika kosong
app.listen(PORT, () => {
    console.log(`WA Bot API listening on port ${PORT}`);
});
