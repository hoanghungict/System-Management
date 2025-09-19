<<<<<<< HEAD
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Notifications Realtime Demo</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 24px; }
        .row { margin-bottom: 12px; }
        label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; }
        input { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        button { padding: 10px 14px; border: 0; background: #2563eb; color: #fff; border-radius: 6px; cursor: pointer; }
        button.secondary { background: #0ea5e9; }
        .log { background: #0b1020; color: #e5e7eb; padding: 12px; border-radius: 8px; height: 300px; overflow: auto; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
        .pill { display: inline-block; padding: 4px 8px; background: #f3f4f6; border-radius: 9999px; font-size: 12px; }
    </style>
    <script src="https://unpkg.com/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
</head>
<body>
    <h2>Notifications Realtime Demo <span class="pill">Module: Notifications</span></h2>

    <div class="row">
        <label>JWT Token (Bearer)</label>
        <input id="token" placeholder="Dán JWT vào đây" />
    </div>

    <div class="grid">
        <div class="row">
            <label>Host</label>
            <input id="host" value="localhost" />
        </div>
        <div class="row">
            <label>Port</label>
            <input id="port" value="8080" />
        </div>
    </div>

    <div class="grid">
        <div class="row">
            <label>Scheme (http/https)</label>
            <input id="scheme" value="http" />
        </div>
        <div class="row">
            <label>Reverb App Key</label>
            <input id="appKey" value="{{ config('broadcasting.connections.reverb.key') }}" />
        </div>
    </div>

    <div class="grid">
        <div class="row">
            <label>User ID</label>
            <input id="userId" value="1" />
        </div>
        <div class="row" style="display:flex; align-items:flex-end; gap:8px;">
            <button id="btnConnect">Kết nối & Subscribe</button>
            <button id="btnDisconnect" class="secondary">Ngắt kết nối</button>
        </div>
    </div>

    <div class="row">
        <label>Log</label>
        <div id="log" class="log"></div>
    </div>

    <script>
        const $ = (id) => document.getElementById(id);
        const logBox = $('log');
        const write = (msg, data) => {
            const line = document.createElement('div');
            const time = new Date().toLocaleTimeString();
            line.textContent = `[${time}] ${msg}`;
            logBox.appendChild(line);
            if (data !== undefined) {
                const pre = document.createElement('pre');
                pre.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
                logBox.appendChild(pre);
            }
            logBox.scrollTop = logBox.scrollHeight;
        };

        let echo = null;
        let channel = null;

        $('btnConnect').addEventListener('click', () => {
            try {
                const token = $('token').value.trim();
                const host = $('host').value.trim();
                const port = parseInt($('port').value.trim(), 10) || 8080;
                const scheme = $('scheme').value.trim() || 'http';
                const appKey = $('appKey').value.trim();
                const userId = $('userId').value.trim();

                if (!token) { write('Lỗi: Chưa nhập JWT token'); return; }
                if (!userId) { write('Lỗi: Chưa nhập userId'); return; }
                if (!appKey) { write('Lỗi: Chưa có Reverb App Key'); return; }

                window.Pusher = window.Pusher || Pusher;
                const EchoClass = window.Echo; // from echo.iife.js

                echo = new EchoClass({
                    broadcaster: 'pusher',
                    key: appKey,
                    wsHost: host,
                    wsPort: port,
                    wssPort: port,
                    forceTLS: scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: '/broadcasting/auth',
                    auth: { headers: { Authorization: `Bearer ${token}` } },
                });

                write('Đang kết nối WS...', { host, port, scheme });

                const channelName = `notifications.user.${userId}`;
                channel = echo.private(channelName)
                    .listen('.user.notification', (e) => write('Sự kiện nhận được', e));

                write('Đã subscribe kênh', channelName);
            } catch (e) {
                write('Lỗi connect', String(e));
            }
        });

        $('btnDisconnect').addEventListener('click', () => {
            try {
                if (channel && channel.name) {
                    echo.leave(channel.name);
                    write('Đã rời kênh', channel.name);
                }
                if (echo) {
                    echo.disconnect();
                    write('Đã ngắt kết nối');
                }
                channel = null; echo = null;
            } catch (e) {
                write('Lỗi disconnect', String(e));
            }
        });
    </script>
</body>
</html>


=======
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Notifications Realtime Demo</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 24px; }
        .row { margin-bottom: 12px; }
        label { display: block; font-size: 12px; color: #555; margin-bottom: 4px; }
        input { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        button { padding: 10px 14px; border: 0; background: #2563eb; color: #fff; border-radius: 6px; cursor: pointer; }
        button.secondary { background: #0ea5e9; }
        .log { background: #0b1020; color: #e5e7eb; padding: 12px; border-radius: 8px; height: 300px; overflow: auto; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
        .pill { display: inline-block; padding: 4px 8px; background: #f3f4f6; border-radius: 9999px; font-size: 12px; }
    </style>
    <script src="https://unpkg.com/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
</head>
<body>
    <h2>Notifications Realtime Demo <span class="pill">Module: Notifications</span></h2>

    <div class="row">
        <label>JWT Token (Bearer)</label>
        <input id="token" placeholder="Dán JWT vào đây" />
    </div>

    <div class="grid">
        <div class="row">
            <label>Host</label>
            <input id="host" value="localhost" />
        </div>
        <div class="row">
            <label>Port</label>
            <input id="port" value="8080" />
        </div>
    </div>

    <div class="grid">
        <div class="row">
            <label>Scheme (http/https)</label>
            <input id="scheme" value="http" />
        </div>
        <div class="row">
            <label>Reverb App Key</label>
            <input id="appKey" value="{{ config('broadcasting.connections.reverb.key') }}" />
        </div>
    </div>

    <div class="grid">
        <div class="row">
            <label>User ID</label>
            <input id="userId" value="1" />
        </div>
        <div class="row" style="display:flex; align-items:flex-end; gap:8px;">
            <button id="btnConnect">Kết nối & Subscribe</button>
            <button id="btnDisconnect" class="secondary">Ngắt kết nối</button>
        </div>
    </div>

    <div class="row">
        <label>Log</label>
        <div id="log" class="log"></div>
    </div>

    <script>
        const $ = (id) => document.getElementById(id);
        const logBox = $('log');
        const write = (msg, data) => {
            const line = document.createElement('div');
            const time = new Date().toLocaleTimeString();
            line.textContent = `[${time}] ${msg}`;
            logBox.appendChild(line);
            if (data !== undefined) {
                const pre = document.createElement('pre');
                pre.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
                logBox.appendChild(pre);
            }
            logBox.scrollTop = logBox.scrollHeight;
        };

        let echo = null;
        let channel = null;

        $('btnConnect').addEventListener('click', () => {
            try {
                const token = $('token').value.trim();
                const host = $('host').value.trim();
                const port = parseInt($('port').value.trim(), 10) || 8080;
                const scheme = $('scheme').value.trim() || 'http';
                const appKey = $('appKey').value.trim();
                const userId = $('userId').value.trim();

                if (!token) { write('Lỗi: Chưa nhập JWT token'); return; }
                if (!userId) { write('Lỗi: Chưa nhập userId'); return; }
                if (!appKey) { write('Lỗi: Chưa có Reverb App Key'); return; }

                window.Pusher = window.Pusher || Pusher;
                const EchoClass = window.Echo; // from echo.iife.js

                echo = new EchoClass({
                    broadcaster: 'pusher',
                    key: appKey,
                    wsHost: host,
                    wsPort: port,
                    wssPort: port,
                    forceTLS: scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint: '/broadcasting/auth',
                    auth: { headers: { Authorization: `Bearer ${token}` } },
                });

                write('Đang kết nối WS...', { host, port, scheme });

                const channelName = `notifications.user.${userId}`;
                channel = echo.private(channelName)
                    .listen('.user.notification', (e) => write('Sự kiện nhận được', e));

                write('Đã subscribe kênh', channelName);
            } catch (e) {
                write('Lỗi connect', String(e));
            }
        });

        $('btnDisconnect').addEventListener('click', () => {
            try {
                if (channel && channel.name) {
                    echo.leave(channel.name);
                    write('Đã rời kênh', channel.name);
                }
                if (echo) {
                    echo.disconnect();
                    write('Đã ngắt kết nối');
                }
                channel = null; echo = null;
            } catch (e) {
                write('Lỗi disconnect', String(e));
            }
        });
    </script>
</body>
</html>


>>>>>>> bd1641df13c4d5c20a66cd48866ad74131db6dc4
