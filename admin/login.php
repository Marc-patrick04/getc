<?php
require_once '../includes/auth.php';

$auth = new Auth();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($username, $password)) {
        redirect('dashboard.php');
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - GETC Ltd</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-orange));
        }
        
        .login-box {
            background: var(--white);
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-box h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary-blue);
        }
        
        .login-box h1 span {
            color: var(--secondary-orange);
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            border-color: var(--secondary-orange);
            outline: none;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--secondary-orange);
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: 0.3s;
        }
         .button  {
            width: 100%;
            padding: 1rem;
            background: blue;
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: 0.3s;
           
        }
        .button a {
            color: white;
            text-decoration: none;
        }
        
        .btn-login:hover {
            background: var(--primary-blue);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1><span>GETC</span> Admin</h1>
            
            <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-login">Login</button>
               
                
            </form>
             <button type="submit" class="button"><a href="../index.php?v=<?php echo time(); ?>" >Back to webpage </a></button>
        </div>
    </div>
</body>
</html>