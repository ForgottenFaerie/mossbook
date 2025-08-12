<?php
// Simple .env loader (no Composer)

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found at: $path");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue; // skip comments
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}

// Load .env from parent folder
loadEnv(__DIR__ . '/.env');


// DB credentials from .env
$host = $_ENV['DB_HOST'];
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

// Connect to DB
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = "";
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $fName    = trim($_POST['fName']);
    $lName    = trim($_POST['lName']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute(['username' => $username, 'email' => $email]);

        if ($stmt->rowCount() > 0) {
            $error = "Username or email already taken.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, password) 
                                   VALUES (:username, :fName, :lName, :email, :password)");
            $stmt->execute([
                'username' => $username,
                'fName'    => $fName,
                'lName'    => $lName,
                'email'    => $email,
                'password' => $hashedPassword
            ]);
            $success = "Account created successfully! <a href='/login.php'>Login here</a>.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta lang="en" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Mossbook. - Sign Up</title>
        <link rel="stylesheet" href="/src/css/index.css" />
        <link rel="icon" href="/assets/favicon/favicon.ico" type="image/x-icon" />
        <link rel="manifest" href="/site.webmanifest">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cal+Sans&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Dynalight&family=Inconsolata:wght@200..900&family=Lexend+Deca:wght@100..900&family=Spectral:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    </head>
    <body>
        <header>
            <div class="header-container">
                <div class="logo">
                    <a href="/"><img src="/assets/favicon/favicon.ico" alt="Mossbook Logo" /></a>
                    <h1>Sign Up</h1>
                </div>
        </header>
        <main>
            <div class="form-container">
                <?php if ($error): ?>
                    <p style="color:red;"><?php echo $error; ?></p>
                <?php endif; ?>
                <?php if ($success): ?>
                    <p style="color:green;"><?php echo $success; ?></p>
                <?php endif; ?>

                <form action="" method="POST">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" />

                    <label for="fName">First Name:</label>
                    <input type="text" id="fName" name="fName" required value="<?php echo isset($fName) ? htmlspecialchars($fName) : ''; ?>" />

                    <label for="lName">Last initial:</label>
                    <input type="text" id="lName" name="lName" required value="<?php echo isset($lName) ? htmlspecialchars($lName) : ''; ?>" />

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" />

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required />

                    <button type="submit">Sign Up</button>
                </form>
                <p>Already have an account? <a href="/login.php">Log in here</a>.</p>
            </div>
        </main>
    </body>
</html>
