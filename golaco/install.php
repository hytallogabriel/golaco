<?php
/**
 * GOLAÇO - Web Installer
 * Setup database, tables, and insert default Brasileirão A & B players.
 */

session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'golaco';

    try {
        // Connect to MySQL server without database first
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Reconnect to the newly created database
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create Users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            money INT DEFAULT 1000,
            reset_token VARCHAR(255) DEFAULT NULL,
            display_name VARCHAR(100) DEFAULT NULL,
            bio TEXT DEFAULT NULL,
            avatar_path VARCHAR(255) DEFAULT NULL,
            dashboard_card_bg VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        // Create Players table
        $pdo->exec("CREATE TABLE IF NOT EXISTS players (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            overall INT NOT NULL,
            position ENUM('GK', 'CB', 'LB', 'RB', 'CM', 'LM', 'RM', 'ST') NOT NULL,
            series ENUM('A', 'B') NOT NULL,
            image_path VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        // Create Tournaments table
        $pdo->exec("CREATE TABLE IF NOT EXISTS tournaments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            team_name VARCHAR(100) NOT NULL,
            formation VARCHAR(10) NOT NULL,
            kit_color VARCHAR(20) NOT NULL,
            players_json TEXT NOT NULL,
            bench_json TEXT NULL,
            current_stage INT DEFAULT 1,
            status ENUM('active', 'eliminated', 'completed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        // Migration: ensure bench_json column exists on pre-existing installs
        $bench_col = $pdo->query("SHOW COLUMNS FROM tournaments LIKE 'bench_json'");
        if ($bench_col->rowCount() === 0) {
            $pdo->exec("ALTER TABLE tournaments ADD COLUMN bench_json TEXT NULL AFTER players_json");
        }

        // Check if players already exist to avoid duplicates
        $check = $pdo->query("SELECT COUNT(*) FROM players")->fetchColumn();
        if ($check == 0) {
            // Seed default players (Brasileirão Serie A & B)
            $default_players = [
                // Goleiros (GK)
                ['name' => 'Weverton', 'overall' => 82, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'John Victor', 'overall' => 81, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'Fábio', 'overall' => 80, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'Tadeu', 'overall' => 78, 'position' => 'GK', 'series' => 'B'],
                ['name' => 'Jordi', 'overall' => 74, 'position' => 'GK', 'series' => 'B'],
                ['name' => 'Agustín Marchesín', 'overall' => 79, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'Sergio Rochet', 'overall' => 82, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'Cássio', 'overall' => 80, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'Everson', 'overall' => 80, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'Marcos Felipe', 'overall' => 78, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'Léo Jardim', 'overall' => 81, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'Hugo Souza', 'overall' => 79, 'position' => 'GK', 'series' => 'A'],
                ['name' => 'Gabriel Brazão', 'overall' => 76, 'position' => 'GK', 'series' => 'B'],
                ['name' => 'João Ricardo', 'overall' => 79, 'position' => 'GK', 'series' => 'A'],

                // Zagueiros (CB)
                ['name' => 'Gustavo Gómez', 'overall' => 83, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Thiago Silva', 'overall' => 83, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Léo Ortiz', 'overall' => 80, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Bastos', 'overall' => 78, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Wagner Leonardo', 'overall' => 77, 'position' => 'CB', 'series' => 'B'],
                ['name' => 'Rafael Carioca', 'overall' => 71, 'position' => 'CB', 'series' => 'B'],
                ['name' => 'Murilo', 'overall' => 81, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Robert Arboleda', 'overall' => 80, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'André Ramalho', 'overall' => 79, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Alexander Barboza', 'overall' => 79, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Vitão', 'overall' => 80, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Junior Alonso', 'overall' => 79, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Gabriel Xavier', 'overall' => 78, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Thiago Heleno', 'overall' => 77, 'position' => 'CB', 'series' => 'A'],
                ['name' => 'Gil', 'overall' => 76, 'position' => 'CB', 'series' => 'B'],
                ['name' => 'David Ricardo', 'overall' => 74, 'position' => 'CB', 'series' => 'B'],

                // Laterais Esquerdos (LB)
                ['name' => 'Guilherme Arana', 'overall' => 81, 'position' => 'LB', 'series' => 'A'],
                ['name' => 'Joaquín Piquerez', 'overall' => 81, 'position' => 'LB', 'series' => 'A'],
                ['name' => 'Lucas Piton', 'overall' => 78, 'position' => 'LB', 'series' => 'A'],
                ['name' => 'Matheus Bidu', 'overall' => 73, 'position' => 'LB', 'series' => 'B'],
                ['name' => 'Ayrton Lucas', 'overall' => 80, 'position' => 'LB', 'series' => 'A'],
                ['name' => 'Alex Telles', 'overall' => 82, 'position' => 'LB', 'series' => 'A'],
                ['name' => 'Welington', 'overall' => 77, 'position' => 'LB', 'series' => 'A'],
                ['name' => 'Marlon', 'overall' => 78, 'position' => 'LB', 'series' => 'A'],
                ['name' => 'Bruno Pacheco', 'overall' => 77, 'position' => 'LB', 'series' => 'A'],
                ['name' => 'Lucas Esquivel', 'overall' => 76, 'position' => 'LB', 'series' => 'A'],
                ['name' => 'Gonzalo Escobar', 'overall' => 75, 'position' => 'LB', 'series' => 'B'],
                ['name' => 'Matheus Bahia', 'overall' => 74, 'position' => 'LB', 'series' => 'B'],

                // Laterais Direitos (RB)
                ['name' => 'William', 'overall' => 80, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'Samuel Xavier', 'overall' => 76, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'Zeca', 'overall' => 73, 'position' => 'RB', 'series' => 'B'],
                ['name' => 'Hugo Mallo', 'overall' => 75, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'Marcos Rocha', 'overall' => 78, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'Guillermo Varela', 'overall' => 77, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'Igor Vinícius', 'overall' => 77, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'Fagner', 'overall' => 77, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'Vitinho', 'overall' => 79, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'Santiago Arias', 'overall' => 78, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'Tinga', 'overall' => 78, 'position' => 'RB', 'series' => 'A'],
                ['name' => 'JP Chermont', 'overall' => 75, 'position' => 'RB', 'series' => 'B'],

                // Meio-Campistas (CM)
                ['name' => 'Giorgian De Arrascaeta', 'overall' => 84, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Rodrigo Garro', 'overall' => 82, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Gerson', 'overall' => 82, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Matheus Pereira', 'overall' => 82, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Dimitri Payet', 'overall' => 81, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Ganso', 'overall' => 81, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Alan Patrick', 'overall' => 81, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Lucas Lima', 'overall' => 76, 'position' => 'CM', 'series' => 'B'],
                ['name' => 'Matheus Frizzo', 'overall' => 75, 'position' => 'CM', 'series' => 'B'],
                ['name' => 'Nenê', 'overall' => 74, 'position' => 'CM', 'series' => 'B'],
                ['name' => 'Raphael Veiga', 'overall' => 83, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Nicolás De La Cruz', 'overall' => 83, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Erick Pulgar', 'overall' => 80, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Aníbal Moreno', 'overall' => 80, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Thiago Almada', 'overall' => 82, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Jhon Arias', 'overall' => 82, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Everton Ribeiro', 'overall' => 80, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Lucas Romero', 'overall' => 78, 'position' => 'CM', 'series' => 'A'],
                ['name' => 'Diego Pituca', 'overall' => 76, 'position' => 'CM', 'series' => 'B'],

                // Meias Esquerdas (LM)
                ['name' => 'Lucas Moura', 'overall' => 83, 'position' => 'LM', 'series' => 'A'],
                ['name' => 'Everton Cebolinha', 'overall' => 80, 'position' => 'LM', 'series' => 'A'],
                ['name' => 'Alesson', 'overall' => 73, 'position' => 'LM', 'series' => 'B'],
                ['name' => 'Ferreira', 'overall' => 79, 'position' => 'LM', 'series' => 'A'],
                ['name' => 'Ángel Romero', 'overall' => 78, 'position' => 'LM', 'series' => 'A'],
                ['name' => 'Jefferson Savarino', 'overall' => 80, 'position' => 'LM', 'series' => 'A'],
                ['name' => 'Keno', 'overall' => 78, 'position' => 'LM', 'series' => 'A'],
                ['name' => 'Wesley', 'overall' => 79, 'position' => 'LM', 'series' => 'A'],
                ['name' => 'Álvaro Barreal', 'overall' => 78, 'position' => 'LM', 'series' => 'A'],
                ['name' => 'Paulinho', 'overall' => 81, 'position' => 'LM', 'series' => 'A'],
                ['name' => 'Guilherme', 'overall' => 77, 'position' => 'LM', 'series' => 'B'],
                ['name' => 'Erick Pulga', 'overall' => 76, 'position' => 'LM', 'series' => 'B'],

                // Meias Direitas (RM)
                ['name' => 'Estêvão', 'overall' => 82, 'position' => 'RM', 'series' => 'A'],
                ['name' => 'Luiz Henrique', 'overall' => 82, 'position' => 'RM', 'series' => 'A'],
                ['name' => 'Chrystian Barletta', 'overall' => 74, 'position' => 'RM', 'series' => 'B'],
                ['name' => 'Felipe Anderson', 'overall' => 81, 'position' => 'RM', 'series' => 'A'],
                ['name' => 'Luciano', 'overall' => 80, 'position' => 'RM', 'series' => 'A'],
                ['name' => 'Gustavo Scarpa', 'overall' => 81, 'position' => 'RM', 'series' => 'A'],
                ['name' => 'Yago Pikachu', 'overall' => 78, 'position' => 'RM', 'series' => 'A'],
                ['name' => 'Agustín Canobbio', 'overall' => 78, 'position' => 'RM', 'series' => 'A'],
                ['name' => 'Rómulo Otero', 'overall' => 76, 'position' => 'RM', 'series' => 'B'],
                ['name' => 'Lucas Ronier', 'overall' => 75, 'position' => 'RM', 'series' => 'B'],
                ['name' => 'Paulo Baya', 'overall' => 75, 'position' => 'RM', 'series' => 'B'],

                // Centroavantes / Atacantes (ST)
                ['name' => 'Hulk', 'overall' => 85, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Pedro', 'overall' => 83, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Pablo Vegetti', 'overall' => 82, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Jonathan Calleri', 'overall' => 81, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Anselmo Ramon', 'overall' => 74, 'position' => 'ST', 'series' => 'B'],
                ['name' => 'Vagner Love', 'overall' => 74, 'position' => 'ST', 'series' => 'B'],
                ['name' => 'Caio Dantas', 'overall' => 73, 'position' => 'ST', 'series' => 'B'],
                ['name' => 'Yuri Alberto', 'overall' => 81, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Memphis Depay', 'overall' => 83, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Igor Jesus', 'overall' => 81, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Flaco López', 'overall' => 80, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Germán Cano', 'overall' => 81, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Martin Braithwaite', 'overall' => 80, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Rafael Borré', 'overall' => 81, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Kaio Jorge', 'overall' => 79, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Juan Martín Lucero', 'overall' => 80, 'position' => 'ST', 'series' => 'A'],
                ['name' => 'Gustavo Coutinho', 'overall' => 75, 'position' => 'ST', 'series' => 'B']
            ];

            $stmt = $pdo->prepare("INSERT INTO players (name, overall, position, series) VALUES (:name, :overall, :position, :series)");
            foreach ($default_players as $p) {
                $stmt->execute([
                    ':name' => $p['name'],
                    ':overall' => $p['overall'],
                    ':position' => $p['position'],
                    ':series' => $p['series']
                ]);
            }
        }

        // Create default Admin user if not exists
        $adminCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCheck->execute();
        if ($adminCheck->fetchColumn() == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, money) VALUES (:username, :email, :password, 'admin', 5000)");
            $stmt->execute([
                ':username' => 'admin',
                ':email' => 'admin@golaco.com',
                ':password' => $adminPassword
            ]);
        }

        // Write config/db.php
        $config_dir = __DIR__ . '/config';
        if (!is_dir($config_dir)) {
            mkdir($config_dir, 0777, true);
        }

        $db_config_content = "<?php\n"
            . "define('DB_HOST', " . var_export($db_host, true) . ");\n"
            . "define('DB_USER', " . var_export($db_user, true) . ");\n"
            . "define('DB_PASS', " . var_export($db_pass, true) . ");\n"
            . "define('DB_NAME', " . var_export($db_name, true) . ");\n\n"
            . "try {\n"
            . "    \$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);\n"
            . "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n"
            . "    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\n"
            . "} catch (PDOException \$e) {\n"
            . "    die('Erro de conexão com o banco de dados: ' . \$e->getMessage());\n"
            . "}\n";

        file_put_contents($config_dir . '/db.php', $db_config_content);

        // Create uploads folder
        $uploads_dir = __DIR__ . '/uploads';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }

        $success = "Banco de dados configurado com sucesso! Os jogadores iniciais e o administrador padrão foram criados.";

    } catch (PDOException $e) {
        $error = "Erro na instalação: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação GOLAÇO</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00ff66;
            --primary-dark: #00b347;
            --bg: #090e17;
            --card-bg: rgba(20, 30, 48, 0.6);
            --border: rgba(0, 255, 102, 0.2);
            --text: #ffffff;
            --text-muted: #8c9ba5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #090e17 0%, #032b1d 100%);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(to right, #ffffff, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 30px;
            font-weight: 300;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .alert-error {
            background: rgba(255, 77, 77, 0.1);
            border: 1px solid rgba(255, 77, 77, 0.3);
            color: #ff4d4d;
        }

        .alert-success {
            background: rgba(0, 255, 102, 0.1);
            border: 1px solid rgba(0, 255, 102, 0.3);
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #d1d5db;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            background: rgba(9, 14, 23, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(0, 255, 102, 0.3);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 10px;
            color: #090e17;
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 255, 102, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 255, 102, 0.6);
        }

        .btn:active {
            transform: translateY(0);
        }

        .admin-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .admin-info h3 {
            font-size: 1rem;
            margin-bottom: 8px;
            color: #fff;
        }

        .admin-info p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .admin-info strong {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>GOLAÇO</h1>
        <p class="subtitle">Instalador de Banco de Dados</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
            
            <div class="admin-info">
                <h3>Dados do Administrador Padrão:</h3>
                <p>Usuário: <strong>admin</strong></p>
                <p>Senha: <strong>admin123</strong></p>
            </div>
            <br>
            <a href="auth/login.php" class="btn">Ir para o Login</a>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="db_host">Host do MySQL</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label for="db_user">Usuário MySQL</label>
                    <input type="text" id="db_user" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label for="db_pass">Senha MySQL</label>
                    <input type="password" id="db_pass" name="db_pass" placeholder="Em branco no XAMPP padrão">
                </div>
                <div class="form-group">
                    <label for="db_name">Nome do Banco</label>
                    <input type="text" id="db_name" name="db_name" value="golaco" required>
                </div>
                <button type="submit" class="btn">Instalar Banco de Dados</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
