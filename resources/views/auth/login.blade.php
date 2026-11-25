@extends('layouts.auth')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
  /* ========================
     1. GLOBAL & BACKGROUND
     ======================== */
  .login-bg {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: url('{{ asset('assets/bg/gelap.jpg') }}') no-repeat center center fixed;
    background-size: cover;
    filter: blur(10px); z-index: -1;
  }
  .login-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.4); z-index: -1;
  }

  /* ========================
     2. CARD WRAPPER
     ======================== */
  .login-card {
    background: #fff; border-radius: 20px; 
    overflow: hidden; 
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    width: 100%; max-width: 900px; min-height: 550px; border: none;
  }

  /* ========================
     3. BAGIAN KANAN (VISUAL)
     ======================== */
  .right-side-image {
    background: url('{{ asset('assets/bg/gelap.jpg') }}') no-repeat center center fixed;
    background-size: cover;
    position: relative; height: 100%;
    display: flex; flex-direction: column; justify-content: center;
    padding: 40px; color: white;
    padding-top: 80px; 
  }
  .right-side-overlay {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.90), rgba(102, 16, 242, 0.85));
    z-index: 1;
  }
  .right-content { position: relative; z-index: 2; }

  /* ========================
     4. HACKER ANIMATION (FIXED: POSISI SAMPING)
     ======================== */
  .hacker-container {
    position: absolute;
    /* Posisi Awal (Ngumpet) */
    top: 180px;   
    right: 0px; 
    left: auto;   
    z-index: 0; /* Di belakang Code Window */
    
    transform: rotate(35deg); 
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
    
    /* FIX: KEMBALI KE ROW (Teks di sebelah kiri kepala) */
    display: flex;
    flex-direction: row; 
    align-items: center;
  }

  .hacker-img {
    width: 120px; 
    filter: drop-shadow(5px 5px 5px rgba(0,0,0,0.4));
    transform: scaleX(-1); 
  }

  .speech-bubble {
    background: #fff; color: #000; padding: 6px 14px;
    border-radius: 12px; 
    
    /* FIX: Buntut di kanan bawah (arah ke kepala) */
    border-bottom-right-radius: 0; 
    
    font-size: 0.85rem; font-weight: bold; font-family: sans-serif;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    
    /* FIX: Jarak Margin (Jarak teks ke kepala) */
    margin-bottom: 0;
    margin-right: 0px; 
    
    opacity: 0;
    transform: scale(0);
    
    /* FIX: Animasi muncul dari kanan (mulut) */
    transform-origin: right center; 
    transition: all 0.2s ease; 
  }

  /* Class Aktif (Peeking) */
  .hacker-container.peeking {
    top: 70px;  
    right: -60px; 
  }

  /* Animasi Bubble text muncul telat */
  .hacker-container.peeking .speech-bubble {
    opacity: 1;
    transform: scale(1);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.4s;
  }

  /* ========================
     5. CODE EDITOR WINDOW
     ======================== */
  .code-window {
    background: #1e1e1e; 
    border-radius: 10px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    border: 1px solid #333;
    font-family: 'Consolas', 'Monaco', monospace; 
    overflow: hidden; font-size: 0.85rem; line-height: 1.6;
    transition: all 0.3s ease;
    position: relative;
    z-index: 10; 
  }

  .window-header {
    background: #252526; padding: 8px 15px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #333;
  }
  .dot { width: 10px; height: 10px; border-radius: 50%; }
  .dot-red { background: #ff5f56; }
  .dot-yellow { background: #ffbd2e; }
  .dot-green { background: #27c93f; }
  
  .code-body { padding: 15px 20px; color: #d4d4d4; min-height: 200px; }
  
  /* Syntax Highlighting */
  .kwd { color: #569cd6; font-weight: bold; }
  .str { color: #ce9178; }
  .fn { color: #dcdcaa; }
  .cls { color: #4ec9b0; }
  .cmt { color: #6a9955; font-style: italic; }
  .var { color: #9cdcfe; }
  .err { color: #f44747; }

  /* ========================
     6. FORM STYLES
     ======================== */
  .form-section { padding: 50px 40px; display: flex; flex-direction: column; justify-content: center; height: 100%; }
  
  /* Logo Image Styling */
  .logo-img { width: 60px; height: auto; object-fit: contain; }

  .form-control { padding: 12px; border-radius: 8px; border: 1px solid #e1e1e1; background-color: #fcfcfc; }
  .input-group-text {
    background-color: #fcfcfc; border: 1px solid #e1e1e1; border-left: none;
    border-top-right-radius: 8px !important; border-bottom-right-radius: 8px !important;
    cursor: pointer; color: #6c757d;
  }
  input[name="password"] { border-right: none; border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; }
  .input-group:focus-within .form-control, .input-group:focus-within .input-group-text {
    border-color: #86b7fe; box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
  }
  .btn-primary-custom {
    background: #0d6efd; border: none; padding: 12px; border-radius: 8px;
    font-weight: 600; width: 100%; transition: all 0.3s;
  }
  .btn-primary-custom:hover { background: #0b5ed7; transform: translateY(-2px); }

  @media (max-width: 768px) {
    .right-side-image { display: none; }
    .login-card { max-width: 400px; min-height: auto; }
  }
</style>

<div class="login-bg"></div>
<div class="login-overlay"></div>

<div class="d-flex justify-content-center align-items-center vh-100 p-3">
  <div class="card login-card">
    <div class="row g-0 h-100">
      
      <div class="col-md-6">
        <div class="form-section">
          <img src="{{ asset('assets/logo/logorilll2.png') }}" alt="Logo" class="logo-img mb-4">
          
          <h3 class="fw-bold mb-1" style="color: #1a1a1a;">Get Started</h3>
          <p class="text-muted mb-4" style="font-size: 0.9rem;">Welcome back! Silahkan login ke akun mu.</p>

          @if($errors->any())
            <div class="alert alert-danger py-2" style="font-size: 0.9rem;">{{ $errors->first() }}</div>
          @endif

          <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="mb-3">
              <label class="form-label fw-500 small">Username</label>
              <input type="text" name="username" id="usernameInput" class="form-control" placeholder="Masukan username" required value="{{ old('username') }}">
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between">
                <label class="form-label fw-500 small">Password</label>
                <a href="#" class="text-decoration-none small text-primary" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot?</a>
              </div>
              <div class="input-group">
                <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required>
                <span class="input-group-text" id="togglePassword"><i class="bi bi-eye-slash" id="toggleIcon"></i></span>
              </div>
            </div>

            <div class="d-grid mb-3">
              <button class="btn btn-primary-custom text-white">Login</button>
            </div>
          </form>

          <div class="text-center mt-1">
            <small class="text-muted">Masuk sebagai <a href="{{ route('home') }}" class="text-decoration-none fw-bold">Tamu</a></small>
          </div>
        </div>
      </div>

      <div class="col-md-6 d-none d-md-block">
        <div class="right-side-image">
          <div class="right-side-overlay"></div>
          <div class="right-content text-start px-3">
            <h1 class="display-6 fw-bold mb-2">Profil Jurusan PPLG/RPL</h1>
            <p class="mb-4 opacity-75">Welcome Future Developers! Akses data jurusan semudah menulis kode.</p>

            <div class="hacker-container" id="hackerPeek">
                <div class="speech-bubble">I see you... 👁️</div>
                <img src="{{ asset('assets/img/hacker.png') }}" class="hacker-img" alt="Hacker">
            </div>

            <div class="code-window">
                <div class="window-header">
                    <div class="dot dot-red"></div>
                    <div class="dot dot-yellow"></div>
                    <div class="dot dot-green"></div>
                    <div class="ms-2 text-muted fst-italic" id="fileNameDisplay" style="opacity:0.6; font-size:12px;">AuthController.php</div>
                </div>
                <div class="code-body" id="codeDisplay"></div>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="forgotModalLabel">Lupa Kata Sandi?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4 px-4">
        <div class="mb-3 text-warning">
           <i class="bi bi-shield-lock-fill" style="font-size: 3rem;"></i>
        </div>
        <p class="mb-3 text-muted">
          Untuk alasan keamanan dan integritas data, fitur reset password mandiri saat ini dinonaktifkan.
        </p>
        <p class="fw-medium text-dark bg-light p-3 rounded">
          Silakan hubungi <span class="text-primary">Admin Jurusan</span> atau <span class="text-primary">Guru Produktif RPL/PPLG</span> untuk mengajukan permohonan reset kata sandi.
        </p>
      </div>
      <div class="modal-footer border-top-0 pt-0 justify-content-center pb-4">
        <button type="button" class="btn btn-primary px-4 rounded-pill" data-bs-dismiss="modal">Saya Mengerti</button>
      </div>
    </div>
  </div>
</div>

<script>
  // === 1. LOGIC HACKER & TOGGLE PASSWORD ===
  const togglePassword = document.querySelector('#togglePassword');
  const passwordInput = document.querySelector('#passwordInput');
  const toggleIcon = document.querySelector('#toggleIcon');
  const hackerPeek = document.querySelector('#hackerPeek'); 

  if(togglePassword) {
    togglePassword.addEventListener('click', function (e) {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      toggleIcon.classList.toggle('bi-eye');
      toggleIcon.classList.toggle('bi-eye-slash');

      if (type === 'text') {
         // UNHIDE: Hacker meluncur ke atas
         hackerPeek.classList.add('peeking');
      } else {
         // HIDE: Hacker ngumpet
         hackerPeek.classList.remove('peeking');
      }
    });
  }

  // === 2. DYNAMIC CODE EDITOR LOGIC ===
  const usernameInput = document.getElementById('usernameInput');
  const codeDisplay = document.getElementById('codeDisplay');
  const fileNameDisplay = document.getElementById('fileNameDisplay');
  // Mendeteksi error dari Laravel Session
  const hasError = @json($errors->any());
  
  // Template Kode Default
  const codeIdle = `
    <div class="mb-1"><span class="kwd">class</span> <span class="cls">JurusanRPL</span> <span class="kwd">extends</span> <span class="cls">SMK_Negeri</span> {</div>
    <div class="ps-3 mb-1"><span class="cmt">// Menunggu input user...</span></div>
    <div class="ps-3 mb-1"><span class="kwd">public function</span> <span class="fn">index</span>() {</div>
    <div class="ps-4 mb-1"><span class="kwd">return</span> <span class="cls">View</span>::<span class="fn">make</span>(<span class="str">'auth.login'</span>);</div>
    <div class="ps-3 mb-2">}</div>
    <div class="ps-3 mb-1"><span class="kwd">public</span> <span class="cls">$security</span> = <span class="str">'High'</span>;</div>
    <div>}</div>
  `;

  // Template Kode Error
  const codeError = `
    <div class="mb-1"><span class="kwd">try</span> {</div>
    <div class="ps-3 mb-1"><span class="cls">Auth</span>::<span class="fn">attempt</span>(<span class="var">$credentials</span>);</div>
    <div class="mb-1">} <span class="kwd">catch</span> (<span class="cls">AuthException</span> <span class="var">$e</span>) {</div>
    <div class="ps-3 mb-1"><span class="cmt">// Login Gagal! Password salah.</span></div>
    <div class="ps-3 mb-1"><span class="kwd">return</span> <span class="fn">back</span>()-><span class="fn">withErrors</span>([</div>
    <div class="ps-4 mb-1"><span class="str">'msg'</span> => <span class="str">'<span class="err">Invalid Credentials!</span>'</span></div>
    <div class="ps-3 mb-1">]);</div>
    <div>}</div>
  `;

  // Template Kode Typing (Realtime)
  function renderTyping(userText) {
    const displayUser = userText ? userText : '';
    return `
      <div class="mb-1"><span class="kwd">public function</span> <span class="fn">validateInput</span>(<span class="cls">Request</span> <span class="var">$request</span>) {</div>
      <div class="ps-3 mb-1"><span class="cmt">// Processing...</span></div>
      <div class="ps-3 mb-1"><span class="var">$data</span> = [</div>
      <div class="ps-4 mb-1"><span class="str">'username'</span> => <span class="str">"${displayUser}"</span>,</div>
      <div class="ps-4 mb-1"><span class="str">'password'</span> => <span class="fn">Hash</span>::<span class="fn">make</span>(<span class="str">"********"</span>),</div>
      <div class="ps-3 mb-1">];</div>
      <div class="ps-3 mb-1"><span class="kwd">return</span> <span class="cls">Auth</span>::<span class="fn">check</span>(<span class="var">$data</span>);</div>
      <div>}</div>
    `;
  }

  function updateCodeView() {
    if (hasError) {
        codeDisplay.innerHTML = codeError;
        fileNameDisplay.innerText = "ErrorLog.php";
        fileNameDisplay.style.color = "#ff5f56";
    } else {
        codeDisplay.innerHTML = codeIdle;
        fileNameDisplay.innerText = "AuthController.php";
        fileNameDisplay.style.color = "#d4d4d4";
    }
  }

  // Jalankan saat load awal
  updateCodeView();

  let timeout = null;
  const handleInput = () => {
      fileNameDisplay.innerText = "Validation.php";
      fileNameDisplay.style.color = "#4ec9b0"; 
      const currentVal = usernameInput.value;
      codeDisplay.innerHTML = renderTyping(currentVal);
      
      clearTimeout(timeout);
      // Kembali ke tampilan default setelah 2.5 detik diam
      timeout = setTimeout(() => { updateCodeView(); }, 2500);
  };

  usernameInput.addEventListener('input', handleInput);
  passwordInput.addEventListener('input', handleInput);
</script>

@endsection