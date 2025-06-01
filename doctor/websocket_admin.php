<?php
// Admin page to manage WebSocket server
session_start();

// Check for admin privileges
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: hospital.php');
    exit;
}

// Handle start/stop server actions
$serverStatus = 'unknown';
$serverOutput = '';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'start':
            // Start the WebSocket server
            $command = 'php websocket_server.php > websocket_log.txt 2>&1 &';
            exec($command);
            $serverStatus = 'starting';
            break;
            
        case 'stop':
            // Find and kill the WebSocket server process
            exec("ps aux | grep 'php websocket_server.php' | grep -v grep", $output);
            foreach ($output as $line) {
                $parts = preg_split('/\s+/', trim($line));
                if (isset($parts[1])) {
                    $pid = $parts[1];
                    exec("kill $pid");
                }
            }
            $serverStatus = 'stopped';
            break;
            
        case 'status':
            // Check server status
            exec("ps aux | grep 'php websocket_server.php' | grep -v grep", $output);
            $serverStatus = count($output) > 0 ? 'running' : 'stopped';
            break;
    }
}

// Check current status
exec("ps aux | grep 'php websocket_server.php' | grep -v grep", $output);
$serverStatus = count($output) > 0 ? 'running' : 'stopped';

// Get log content if available
if (file_exists('websocket_log.txt')) {
    $serverOutput = file_get_contents('websocket_log.txt');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Server Admin</title>
    <link rel="shortcut icon" type="image/x-icon" href="vcarelogo.ico" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-running {
            background-color: #28a745;
        }
        
        .status-stopped {
            background-color: #dc3545;
        }
        
        .status-starting {
            background-color: #ffc107;
        }
        
        .status-unknown {
            background-color: #6c757d;
        }
        
        .log-container {
            background-color: #f8f9fa;
            height: 500px;
            overflow-y: scroll;
            font-family: monospace;
            font-size: 0.85rem;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            white-space: pre-wrap;
        }
        
        .refresh-button {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>WebSocket Server Admin</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Server Status</h5>
            </div>
            <div class="card-body">
                <p>
                    Current Status: 
                    <span class="status-indicator status-<?php echo $serverStatus; ?>"></span>
                    <strong><?php echo ucfirst($serverStatus); ?></strong>
                </p>
                
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="start">
                    <button type="submit" class="btn btn-success me-2" <?php echo $serverStatus === 'running' ? 'disabled' : ''; ?>>
                        Start Server
                    </button>
                </form>
                
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="stop">
                    <button type="submit" class="btn btn-danger me-2" <?php echo $serverStatus === 'stopped' ? 'disabled' : ''; ?>>
                        Stop Server
                    </button>
                </form>
                
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="status">
                    <button type="submit" class="btn btn-primary">
                        Refresh Status
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Server Log</h5>
            </div>
            <div class="card-body">
                <form method="post" class="refresh-button">
                    <input type="hidden" name="action" value="status">
                    <button type="submit" class="btn btn-sm btn-secondary">
                        Refresh Log
                    </button>
                </form>
                
                <div class="log-container">
                    <?php echo htmlspecialchars($serverOutput); ?>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="hospital.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom of log
        document.addEventListener('DOMContentLoaded', function() {
            const logContainer = document.querySelector('.log-container');
            logContainer.scrollTop = logContainer.scrollHeight;
            
            // Auto-refresh status and log every 10 seconds
            setInterval(function() {
                const form = document.querySelector('form input[name="action"][value="status"]').form;
                form.submit();
            }, 10000);
        });
    </script>
</body>
</html> 