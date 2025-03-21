<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to get active SSH connections
function get_ssh_connections() {
    $output = shell_exec('sudo netstat -tnp | grep sshd');
    if ($output === null) {
        return [];
    }
    // Suppression des sorties de débogage
    $connections = explode("\n", trim($output));
    $result = [];
    foreach ($connections as $connection) {
        if (empty($connection)) continue;
        $parts = preg_split('/\s+/', $connection);
        $local_address = explode(':', $parts[3]);
        $local_port = $local_address[1];
        // Skip connections on port 22
        if ($local_port == 22) continue;
        $pid = explode('/', $parts[6])[0]; // Extract PID from the process column
        // Get the full command line for the process
        $command_line = shell_exec("ps -o args= -p $pid");
        $command_line = trim($command_line);
        // Suppression des sorties de débogage PID et command line

        // Extract the username from the command line (e.g., "sshd: admin")
        $user = 'Inconnu';
        if (preg_match('/sshd: (\w+)/', $command_line, $matches)) {
            $user = $matches[1];
        }
        $result[] = [
            'ip' => $local_address[0],
            'port' => $local_port,
            'user' => $user,
        ];
    }
    return $result;
}

// Get server statistics
function get_server_stats() {
    $uptime = shell_exec('uptime -p');
    $load = shell_exec("uptime | awk -F 'load average:' '{print $2}' | sed 's/,//g'");
    $memory = shell_exec("free -m | awk 'NR==2{printf \"%.1f/%.1f GB (%.1f%%)\", $3/1024, $2/1024, $3*100/$2}'");
    $disk = shell_exec("df -h / | awk 'NR==2{printf \"%s/%s (%s)\", $3, $2, $5}'");

    return [
        'uptime' => $uptime,
        'load' => $load,
        'memory' => $memory,
        'disk' => $disk
    ];
}

// Get connections
$ssh_connections = get_ssh_connections();
$server_stats = get_server_stats();

// Get current time
$current_time = date('Y-m-d H:i:s');

// Page auto-refresh (every 30 seconds)
$auto_refresh = true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extranet Serveur - Moniteur de Connexions</title>
    <?php if ($auto_refresh): ?>
    <meta http-equiv="refresh" content="30">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #121212;
            --secondary-bg: #1e1e1e;
            --card-bg: #232323;
            --primary-text: #e0e0e0;
            --secondary-text: #a0a0a0;
            --accent-color: #2979ff;
            --accent-light: #2979ff;
            --success-color: #43a047;
            --warning-color: #ffb300;
            --danger-color: #e53935;
            --border-color: #333;
            --header-bg: #1565c0;
            --debug-bg: #333333;
            --debug-text: #8bc34a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--primary-text);
            line-height: 1.6;
            padding: 0;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--header-bg);
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            display: flex;
            align-items: center;
        }

        .header-title h1 {
            margin: 0;
            color: var(--primary-text);
            font-size: 24px;
            margin-left: 10px;
        }

        .header-info {
            display: flex;
            align-items: center;
            color: var(--primary-text);
            font-size: 14px;
        }

        .refresh-info {
            margin-left: 20px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease;
            border-left: 4px solid var(--accent-color);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        .card-title {
            color: var(--secondary-text);
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-right: 8px;
            color: var(--accent-color);
        }

        .card-value {
            color: var(--primary-text);
            font-size: 18px;
            font-weight: bold;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .section-header i {
            margin-right: 10px;
            color: var(--accent-color);
            font-size: 20px;
        }

        .section-title {
            color: var(--primary-text);
            font-size: 20px;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        th {
            padding: 15px;
            text-align: left;
            background-color: var(--secondary-bg);
            color: var(--primary-text);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--secondary-text);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(41, 121, 255, 0.1);
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }

        .badge-active {
            background-color: var(--success-color);
            color: white;
        }

        .badge-recent {
            background-color: var(--warning-color);
            color: black;
        }

        .badge-inactive {
            background-color: var(--danger-color);
            color: white;
        }

        .empty-table {
            text-align: center;
            padding: 20px;
            color: var(--secondary-text);
            font-style: italic;
        }

        .connection-status {
            display: flex;
            align-items: center;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-active {
            background-color: var(--success-color);
            box-shadow: 0 0 5px var(--success-color);
        }

        .status-inactive {
            background-color: var(--danger-color);
            box-shadow: 0 0 5px var(--danger-color);
        }

        footer {
            text-align: center;
            padding: 20px;
            color: var(--secondary-text);
            font-size: 12px;
            margin-top: 40px;
            border-top: 1px solid var(--border-color);
            background-color: var(--secondary-bg);
        }

        /* Blue Glowing Effect for Accents */
        .glow-text {
            color: var(--accent-light);
            text-shadow: 0 0 5px rgba(41, 121, 255, 0.5);
        }

        /* Server Status Indicators */
        .server-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
            background-color: var(--success-color);
            color: white;
        }

        /* Debug Information Styling */
        pre {
            background-color: var(--debug-bg);
            color: var(--debug-text);
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            border-left: 4px solid var(--accent-color);
        }

        .debug-section {
            margin-top: 30px;
            padding: 15px;
            background-color: var(--secondary-bg);
            border-radius: 8px;
        }

        .debug-title {
            display: flex;
            align-items: center;
            color: var(--accent-light);
            margin-bottom: 10px;
            font-size: 18px;
        }

        .debug-title i {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-info {
                margin-top: 10px;
                margin-left: 0;
            }

            .dashboard {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="header-title">
                <i class="fas fa-server" style="color: var(--accent-light);"></i>
                <h1>Extranet Serveur <span class="server-status">En ligne</span></h1>
            </div>
            <div class="header-info">
                <span>Dernière mise à jour: <?php echo htmlspecialchars($current_time); ?></span>
                <?php if ($auto_refresh): ?>
                <span class="refresh-info"><i class="fas fa-sync-alt"></i> Actualisation automatique toutes les 30 secondes</span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="dashboard">
            <div class="card">
                <div class="card-title"><i class="fas fa-clock"></i> Temps de fonctionnement</div>
                <div class="card-value"><?php echo htmlspecialchars($server_stats['uptime'] ?? 'N/A'); ?></div>
            </div>
            <div class="card">
                <div class="card-title"><i class="fas fa-microchip"></i> Charge moyenne</div>
                <div class="card-value"><?php echo htmlspecialchars($server_stats['load'] ?? 'N/A'); ?></div>
            </div>
            <div class="card">
                <div class="card-title"><i class="fas fa-memory"></i> Utilisation mémoire</div>
                <div class="card-value"><?php echo htmlspecialchars($server_stats['memory'] ?? 'N/A'); ?></div>
            </div>
            <div class="card">
                <div class="card-title"><i class="fas fa-hdd"></i> Utilisation disque</div>
                <div class="card-value"><?php echo htmlspecialchars($server_stats['disk'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <i class="fas fa-terminal"></i>
                <h2 class="section-title">Connexions SSH actives</h2>
            </div>
            <table>
                <tr>
                    <th>Statut</th>
                    <th>Adresse IP</th>
                    <th>Port</th>
                    <th>Utilisateur</th>
                </tr>
                <?php if (empty($ssh_connections)): ?>
                <tr>
                    <td colspan="4" class="empty-table">Aucune connexion SSH active</td>
                </tr>
                <?php else: ?>
                <?php foreach ($ssh_connections as $conn): ?>
                <tr>
                    <td>
                        <div class="connection-status">
                            <div class="status-dot status-active"></div>
                            <span class="badge badge-active">Actif</span>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($conn['ip']); ?></td>
                    <td><?php echo htmlspecialchars($conn['port']); ?></td>
                    <td><?php echo htmlspecialchars($conn['user']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <footer>
        <p>Extranet Serveur • Outils de Surveillance • <?php echo date('Y'); ?></p>
    </footer>

    <script>
        // Script simple pour afficher l'heure active
        function updateTime() {
            const timeElements = document.querySelectorAll('.current-time');
            const now = new Date();
            const timeString = now.toLocaleTimeString();

            timeElements.forEach(el => {
                el.textContent = timeString;
            });
        }

        // Mise à jour de l'heure toutes les secondes
        setInterval(updateTime, 1000);
    </script>
</body>
</html>