/**
 * Static Portfolio Demo — Login Page Controller
 *
 * Preserves resources/views/auth/login.blade.php interactive hacker peek,
 * live code-editor preview, and integrates with simulated auth.js.
 */

import {
  getDemoAccounts,
  login,
  setFlash,
  getIntendedUrl,
} from '../auth.js';
import { flushFlashMessages, escapeHtml } from '../layout.js';

const codeIdle = `
  <div class="mb-1"><span class="kwd">class</span> <span class="cls">JurusanRPL</span> <span class="kwd">extends</span> <span class="cls">SMK_Negeri</span> {</div>
  <div class="ps-3 mb-1"><span class="cmt">// Menunggu input user...</span></div>
  <div class="ps-3 mb-1"><span class="kwd">public function</span> <span class="fn">index</span>() {</div>
  <div class="ps-4 mb-1"><span class="kwd">return</span> <span class="cls">View</span>::<span class="fn">make</span>(<span class="str">'auth.login'</span>);</div>
  <div class="ps-3 mb-2">}</div>
  <div class="ps-3 mb-1"><span class="kwd">public</span> <span class="cls">$security</span> = <span class="str">'Demo'</span>;</div>
  <div>}</div>
`;

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

function renderTyping(userText) {
  const safeUser = escapeHtml(userText || '');
  return `
    <div class="mb-1"><span class="kwd">public function</span> <span class="fn">validateInput</span>(<span class="cls">Request</span> <span class="var">$request</span>) {</div>
    <div class="ps-3 mb-1"><span class="cmt">// Processing...</span></div>
    <div class="ps-3 mb-1"><span class="var">$data</span> = [</div>
    <div class="ps-4 mb-1"><span class="str">'username'</span> => <span class="str">"${safeUser}"</span>,</div>
    <div class="ps-4 mb-1"><span class="str">'password'</span> => <span class="fn">Hash</span>::<span class="fn">make</span>(<span class="str">"********"</span>),</div>
    <div class="ps-3 mb-1">];</div>
    <div class="ps-3 mb-1"><span class="kwd">return</span> <span class="cls">Auth</span>::<span class="fn">check</span>(<span class="var">$data</span>);</div>
    <div>}</div>
  `;
}

document.addEventListener('DOMContentLoaded', () => {
  flushFlashMessages();

  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('passwordInput');
  const usernameInput = document.getElementById('usernameInput');
  const toggleIcon = document.getElementById('toggleIcon');
  const hackerPeek = document.getElementById('hackerPeek');
  const codeDisplay = document.getElementById('codeDisplay');
  const fileNameDisplay = document.getElementById('fileNameDisplay');
  const loginForm = document.getElementById('loginForm');
  const errorAlert = document.getElementById('loginErrorAlert');
  const demoContainer = document.getElementById('demoAccountPills');

  let hasError = false;
  let typingTimeout = null;

  function updateCodeView() {
    if (!codeDisplay || !fileNameDisplay) return;
    if (hasError) {
      codeDisplay.innerHTML = codeError;
      fileNameDisplay.innerText = 'ErrorLog.php';
      fileNameDisplay.style.color = '#ff5f56';
    } else {
      codeDisplay.innerHTML = codeIdle;
      fileNameDisplay.innerText = 'AuthController.php';
      fileNameDisplay.style.color = '#d4d4d4';
    }
  }

  function triggerTypingPreview() {
    if (!codeDisplay || !fileNameDisplay) return;
    fileNameDisplay.innerText = 'Validation.php';
    fileNameDisplay.style.color = '#4ec9b0';
    codeDisplay.innerHTML = renderTyping(usernameInput?.value || '');

    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(updateCodeView, 2500);
  }

  function showAuthError(message) {
    hasError = true;
    if (errorAlert) {
      errorAlert.textContent = message;
      errorAlert.classList.remove('d-none');
    }
    updateCodeView();
  }

  function clearAuthError() {
    hasError = false;
    if (errorAlert) {
      errorAlert.textContent = '';
      errorAlert.classList.add('d-none');
    }
  }

  // 1. Hacker peek & password visibility toggle
  if (togglePassword && passwordInput && toggleIcon && hackerPeek) {
    togglePassword.addEventListener('click', () => {
      const nextType = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', nextType);

      toggleIcon.classList.toggle('bi-eye');
      toggleIcon.classList.toggle('bi-eye-slash');
      hackerPeek.classList.toggle('peeking', nextType === 'text');
    });
  }

  // 2. Code window typing preview
  updateCodeView();
  usernameInput?.addEventListener('input', () => {
    clearAuthError();
    triggerTypingPreview();
  });
  passwordInput?.addEventListener('input', () => {
    clearAuthError();
    triggerTypingPreview();
  });

  // 3. Render 5 demo account quick-fill pills
  if (demoContainer) {
    const accounts = getDemoAccounts();
    demoContainer.innerHTML = accounts
      .map((acc) => {
        const variant =
          acc.role === 'admin'
            ? 'btn-outline-danger'
            : acc.role === 'guru'
              ? 'btn-outline-primary'
              : 'btn-outline-success';
        return `
          <button type="button"
                  class="btn ${variant} demo-account-btn"
                  data-username="${escapeHtml(acc.username)}"
                  data-password="${escapeHtml(acc.password)}"
                  title="${escapeHtml(acc.detail)}">
            ${escapeHtml(acc.badge)} (${escapeHtml(acc.username)})
          </button>
        `;
      })
      .join('');

    demoContainer.querySelectorAll('.demo-account-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (usernameInput) usernameInput.value = btn.dataset.username || '';
        if (passwordInput) passwordInput.value = btn.dataset.password || '';
        clearAuthError();
        triggerTypingPreview();
        usernameInput?.focus();
      });
    });
  }

  // 4. Form submit handler
  loginForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    const res = login(usernameInput?.value || '', passwordInput?.value || '');
    if (!res.ok) {
      showAuthError(res.error);
      return;
    }

    setFlash('success', `Selamat datang, ${res.user.username}!`);
    window.location.href = getIntendedUrl('dashboard.html');
  });
});

