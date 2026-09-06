<?php
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/FacultyModel.php';
require_once __DIR__ . '/includes/Permissions.php';
require_once __DIR__ . '/controllers/Controllers.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    $result = $auth->login($_POST['username'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) { header('Location: index.php'); exit; }
    $error = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ETS Project Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}.float-anim{animation:float 4s ease-in-out infinite}.quick-login-btn{transition:all 0.15s ease}.quick-login-btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.3);border-color:#0F643A}.quick-login-btn:active{transform:scale(0.98)}</style>
</head>
<body class="h-full bg-gradient-to-br from-[#0A4A2B] via-[#0F643A] to-[#1E7A4B] flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8 float-anim">
            <div class="text-center mb-6">
                <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="ISU Logo" class="w-24 h-24 mx-auto mb-4 object-contain" onerror="this.style.display='none'">
                <h1 class="text-2xl font-bold text-[#0F643A]">ISU-Cauayan</h1>
                <p class="text-[#5C6B63] text-sm mt-1">Extension Training Services Hub</p>
            </div>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-[#FEE2E2] border border-[#FECACA] text-[#991B1B] text-sm flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-[#1A1A1A] text-sm font-medium mb-1.5">Username</label>
                    <div class="relative"><i class="fas fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-[#6B7280]"></i>
                        <input type="text" name="username" required class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-[#F8FAF8] border border-[#D8E3DA] text-[#1A1A1A] placeholder-[#6B7280] focus:outline-none focus:ring-2 focus:ring-[#0F643A] focus:border-transparent" placeholder="Enter your username" id="username">
                    </div>
                </div>
                <div>
                    <label class="block text-[#1A1A1A] text-sm font-medium mb-1.5">Password</label>
                    <div class="relative"><i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#6B7280]"></i>
                        <input type="password" name="password" required id="password" class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-[#F8FAF8] border border-[#D8E3DA] text-[#1A1A1A] placeholder-[#6B7280] focus:outline-none focus:ring-2 focus:ring-[#0F643A] focus:border-transparent" placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#6B7280] hover:text-[#1A1A1A]"><i class="fas fa-eye" id="eye-icon"></i></button>
                    </div>
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-[#0F643A] hover:bg-[#1E7A4B] text-white font-semibold transition-all transform hover:scale-[1.01] active:scale-[0.99] shadow-lg"><i class="fas fa-sign-in-alt mr-2"></i> Sign In</button>
            </form>
            <div class="mt-5">
                <div class="flex items-center gap-3 mb-3"><div class="flex-1 h-px bg-[#D8E3DA]"></div><span class="text-[#6B7280] text-xs font-medium">Quick Login</span><div class="flex-1 h-px bg-[#D8E3DA]"></div></div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="quickLogin('admin','password123')" class="quick-login-btn flex items-center gap-2 px-3 py-2.5 rounded-xl bg-[#F8FAF8] border border-[#D8E3DA] text-[#1A1A1A]">
                        <div class="w-8 h-8 bg-[#FEE2E2] rounded-lg flex items-center justify-center"><i class="fas fa-shield-halved text-[#DC2626] text-sm"></i></div>
                        <div class="text-left"><p class="text-xs font-semibold">Admin</p><p class="text-[10px] text-[#6B7280]">admin / password123</p></div>
                    </button>
                    <button type="button" onclick="quickLogin('faculty1','password123')" class="quick-login-btn flex items-center gap-2 px-3 py-2.5 rounded-xl bg-[#F8FAF8] border border-[#D8E3DA] text-[#1A1A1A]">
                        <div class="w-8 h-8 bg-[#D1FAE5] rounded-lg flex items-center justify-center"><i class="fas fa-chalkboard-teacher text-[#0F643A] text-sm"></i></div>
                        <div class="text-left"><p class="text-xs font-semibold">Faculty</p><p class="text-[10px] text-[#6B7280]">faculty1 / password123</p></div>
                    </button>
                    <button type="button" onclick="quickLogin('faculty2','password123')" class="quick-login-btn flex items-center gap-2 px-3 py-2.5 rounded-xl bg-[#F8FAF8] border border-[#D8E3DA] text-[#1A1A1A]">
                        <div class="w-8 h-8 bg-[#FEF3C7] rounded-lg flex items-center justify-center"><i class="fas fa-user text-[#D97706] text-sm"></i></div>
                        <div class="text-left"><p class="text-xs font-semibold">Faculty 2</p><p class="text-[10px] text-[#6B7280]">faculty2 / password123</p></div>
                    </button>
                    <button type="button" onclick="quickLogin('viewer','password123')" class="quick-login-btn flex items-center gap-2 px-3 py-2.5 rounded-xl bg-[#F8FAF8] border border-[#D8E3DA] text-[#1A1A1A]">
                        <div class="w-8 h-8 bg-[#EDE9FE] rounded-lg flex items-center justify-center"><i class="fas fa-eye text-[#7C3AED] text-sm"></i></div>
                        <div class="text-left"><p class="text-xs font-semibold">Viewer</p><p class="text-[10px] text-[#6B7280]">viewer / password123</p></div>
                    </button>
                </div>
            </div>
            <div class="mt-6 text-center"><p class="text-[#6B7280] text-xs">ISU-Cauayan Extension Training Services</p><p class="text-[#5C6B63]/60 text-xs mt-1">Project Management Hub v2.0</p></div>
        </div>
    </div>
    <script>
        function togglePassword(){const i=document.getElementById('password'),ic=document.getElementById('eye-icon');if(i.type==='password'){i.type='text';ic.classList.replace('fa-eye','fa-eye-slash')}else{i.type='password';ic.classList.replace('fa-eye-slash','fa-eye')}}
        function quickLogin(u,p){document.getElementById('username').value=u;document.getElementById('password').value=p;setTimeout(()=>document.getElementById('loginForm').submit(),200)}
    </script>
</body>
</html>
