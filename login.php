<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Pengelolaan Surat Masuk & Disposisi</title>
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-card {
        background: #fff;
        width: 100%;
        max-width: 380px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        padding: 40px 32px;
    }

    .login-card h1 {
        font-size: 20px;
        color: #1e3c72;
        margin-bottom: 4px;
        text-align: center;
    }

    .login-card p.subtitle {
        font-size: 13px;
        color: #777;
        text-align: center;
        margin-bottom: 28px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        color: #333;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .form-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s;
    }

    .form-group input:focus {
        outline: none;
        border-color: #2a5298;
    }

    .btn-login {
        width: 100%;
        padding: 11px;
        background: #1e3c72;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 8px;
    }

    .btn-login:hover {
        background: #2a5298;
    }

    .btn-login:disabled {
        background: #9aa8c7;
        cursor: not-allowed;
    }

    .alert {
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 13px;
        margin-bottom: 18px;
        display: none;
    }

    .alert.show {
        display: block;
    }

    .alert-error {
        background: #fdecea;
        color: #b3261e;
        border: 1px solid #f5c2c0;
    }

    .alert-success {
        background: #e8f5e9;
        color: #256029;
        border: 1px solid #b7e1bb;
    }
</style>
</head>
<body>

<div class="login-card">
    <h1>Pengelolaan Surat Masuk &amp; Disposisi</h1>
    <p class="subtitle">Silakan masuk untuk melanjutkan</p>

    <?php if (!empty($flash_message)): ?>
    <div class="alert alert-success show" id="flashAlert">
        <?php echo htmlspecialchars($flash_message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="alert alert-error" id="errorAlert"></div>

    <form id="loginForm" autocomplete="off">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn-login" id="btnLogin">Masuk</button>
    </form>
</div>

<script>
    var form = document.getElementById('loginForm');
    var btn = document.getElementById('btnLogin');
    var errorAlert = document.getElementById('errorAlert');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var username = document.getElementById('username').value.trim();
        var password = document.getElementById('password').value;

        errorAlert.classList.remove('show');
        errorAlert.textContent = '';

        if (!username || !password) {
            errorAlert.textContent = 'Username dan password wajib diisi.';
            errorAlert.classList.add('show');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Memproses...';

        fetch('<?php echo site_url("AuthController/login"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username: username, password: password })
        })
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (data.status === 'success') {
                btn.textContent = 'Berhasil, mengalihkan...';
                window.location.href = data.redirect;
            } else {
                btn.disabled = false;
                btn.textContent = 'Masuk';
                errorAlert.textContent = data.message || 'Terjadi kesalahan, silakan coba lagi.';
                errorAlert.classList.add('show');
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = 'Masuk';
            errorAlert.textContent = 'Tidak dapat terhubung ke server. Silakan coba lagi.';
            errorAlert.classList.add('show');
        });
    });
</script>

</body>
</html>