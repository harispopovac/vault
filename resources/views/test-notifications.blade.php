<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Browser Tab Notifications</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .connected { background: #d4edda; color: #155724; }
        .disconnected { background: #f8d7da; color: #721c24; }
        .tab-opened { background: #d1ecf1; color: #0c5460; }
        .log {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #0056b3; }
        .instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🚀 Browser Tab Notification Test</h1>

    <div class="instructions">
        <h3>Instructions:</h3>
        <ol>
            <li>Click "Connect to Notifications" below</li>
            <li>Open another terminal and make a git push to trigger the webhook</li>
            <li>Watch for automatic browser tab opening with your prompt!</li>
        </ol>
    </div>

    <div id="connection-status" class="status disconnected">
        Not connected to notification stream
    </div>

    <button onclick="connectToStream()">Connect to Notifications</button>
    <button onclick="disconnectFromStream()">Disconnect</button>
    <button onclick="clearLog()">Clear Log</button>

    <h3>Event Log:</h3>
    <div id="log" class="log">
        Waiting for connection...
    </div>

    <script>
        let eventSource = null;
        let isConnected = false;

        function log(message) {
            const logElement = document.getElementById('log');
            const timestamp = new Date().toLocaleTimeString();
            logElement.innerHTML += `[${timestamp}] ${message}\n`;
            logElement.scrollTop = logElement.scrollHeight;
        }

        function updateStatus(status, className) {
            const statusElement = document.getElementById('connection-status');
            statusElement.textContent = status;
            statusElement.className = `status ${className}`;
        }

        function connectToStream() {
            if (eventSource) {
                eventSource.close();
            }

            log('🔌 Connecting to notification stream...');
            updateStatus('Connecting...', 'disconnected');

            // For testing, we'll use a dummy auth token
            // In real implementation, this would come from your authentication system
            const authToken = 'dummy-token-for-testing';

            eventSource = new EventSource('/api/v1/notifications/stream');

            eventSource.onopen = function(event) {
                isConnected = true;
                log('✅ Connected to notification stream');
                updateStatus('Connected - Waiting for webhook triggers', 'connected');
            };

            eventSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleNotificationEvent(data);
                } catch (e) {
                    log('❌ Failed to parse notification data: ' + e.message);
                }
            };

            eventSource.onerror = function(event) {
                isConnected = false;
                log('💥 Connection error occurred');
                updateStatus('Connection error - Will retry in 5 seconds', 'disconnected');

                // Auto-reconnect after 5 seconds
                setTimeout(() => {
                    if (eventSource && eventSource.readyState === EventSource.CLOSED) {
                        log('🔄 Attempting to reconnect...');
                        connectToStream();
                    }
                }, 5000);
            };
        }

        function disconnectFromStream() {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
                isConnected = false;
                log('🔌 Disconnected from notification stream');
                updateStatus('Disconnected', 'disconnected');
            }
        }

        function clearLog() {
            document.getElementById('log').innerHTML = '';
        }

        function handleNotificationEvent(data) {
            switch (data.type) {
                case 'connected':
                    log(`🎉 Connection established for user ${data.user_id}`);
                    break;

                case 'browser_tab':
                    if (data.action === 'open') {
                        log(`🚀 Opening browser tab for ${data.trigger_name}`);
                        log(`📂 Repository: ${data.repository}`);
                        log(`📋 Event: ${data.event_type}`);
                        log(`🔗 URL: ${data.url}`);

                        updateStatus('Browser tab triggered! Opening...', 'tab-opened');

                        // Open the new tab
                        const newTab = window.open(data.url, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');

                        if (!newTab) {
                            log('❌ Popup blocked! Please allow popups for this site.');
                            updateStatus('Popup blocked - Please allow popups', 'disconnected');
                        } else {
                            log('✅ Browser tab opened successfully!');
                            setTimeout(() => {
                                updateStatus('Connected - Waiting for webhook triggers', 'connected');
                            }, 3000);
                        }
                    }
                    break;

                case 'heartbeat':
                    // Silent heartbeat - just confirm connection is alive
                    if (!isConnected) {
                        isConnected = true;
                        updateStatus('Connected - Waiting for webhook triggers', 'connected');
                    }
                    break;

                default:
                    log(`📨 Unknown notification type: ${data.type}`);
            }
        }

        // Auto-connect on page load
        document.addEventListener('DOMContentLoaded', function() {
            log('🌐 Page loaded - Ready to connect to notifications');
        });
    </script>
</body>
</html>