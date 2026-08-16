// PM2 process manager config for production. Manages the two long-running
// background processes the app needs — the queue worker and the Reverb
// WebSocket server (chat real-time). Apache/Nginx + PHP-FPM serve the app
// itself and aren't managed here; static assets are built once via
// `npm run build` at deploy time, not run persistently.
//
// Usage: pm2 start ecosystem.config.cjs
//        pm2 restart ecosystem.config.cjs   (after a deploy)

module.exports = {
    apps: [
        {
            name: 'codeware-queue',
            script: 'artisan',
            interpreter: 'php',
            args: 'queue:work --tries=3 --max-time=3600 --sleep=3',
            cwd: __dirname,
            exec_mode: 'fork',
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: '300M',
            out_file: 'storage/logs/pm2-queue-out.log',
            error_file: 'storage/logs/pm2-queue-error.log',
            env: {
                APP_ENV: 'production',
            },
        },
        {
            name: 'codeware-reverb',
            script: 'artisan',
            interpreter: 'php',
            args: 'reverb:start',
            cwd: __dirname,
            exec_mode: 'fork',
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: '300M',
            out_file: 'storage/logs/pm2-reverb-out.log',
            error_file: 'storage/logs/pm2-reverb-error.log',
            env: {
                APP_ENV: 'production',
            },
        },
    ],
};
