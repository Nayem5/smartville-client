// ===== CURSOR =====
const dot = document.querySelector('.cursor-dot');
const outline = document.querySelector('.cursor-outline');
let mouseX = 0, mouseY = 0, outlineX = 0, outlineY = 0;
document.addEventListener('mousemove', e => {
  mouseX = e.clientX; mouseY = e.clientY;
  dot.style.left = mouseX + 'px'; dot.style.top = mouseY + 'px';
});
(function animateOutline() {
  outlineX += (mouseX - outlineX) * 0.15;
  outlineY += (mouseY - outlineY) * 0.15;
  outline.style.left = outlineX + 'px'; outline.style.top = outlineY + 'px';
  requestAnimationFrame(animateOutline);
})();

// ===== PARTICLES =====
const container = document.getElementById('particles-container');
const colors = ['#6c63ff','#f72585','#4cc9f0'];
for (let i = 0; i < 20; i++) {
  const p = document.createElement('div');
  p.className = 'particle';
  const size = Math.random() * 5 + 2;
  p.style.cssText = `width:${size}px;height:${size}px;left:${Math.random()*100}%;background:${colors[Math.floor(Math.random()*colors.length)]};animation-duration:${Math.random()*12+8}s;animation-delay:${Math.random()*8}s;`;
  container.appendChild(p);
}

// ===== ROLE SELECTOR =====
function selectRole(btn) {
  document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

// ===== TOGGLE PASSWORD =====
function togglePass(btn) {
  const input = btn.parentElement.querySelector('input');
  if (input.type === 'password') { input.type = 'text'; btn.innerHTML = '<i class="fas fa-eye-slash"></i>'; }
  else { input.type = 'password'; btn.innerHTML = '<i class="fas fa-eye"></i>'; }
}

// ===== LOGIN =====
function handleLogin(e) {
  const btn = e.target.querySelector('.auth-submit-btn');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Logging in...</span>';
  btn.disabled = true;
}

// ===== SIGNUP STEPS =====
let currentStep = 1;
function nextStep(n) {
  document.getElementById(`step-${currentStep}`).classList.remove('active');
  document.getElementById(`step-${currentStep}-ind`).classList.remove('active');
  document.getElementById(`step-${currentStep}-ind`).classList.add('done');
  if (currentStep > 1) {
    document.querySelectorAll('.step-line')[currentStep - 2].classList.add('done');
  }
  currentStep = n;
  document.getElementById(`step-${currentStep}`).classList.add('active');
  document.getElementById(`step-${currentStep}-ind`).classList.add('active');
}
function prevStep(n) {
  document.getElementById(`step-${currentStep}`).classList.remove('active');
  document.getElementById(`step-${currentStep}-ind`).classList.remove('active');
  currentStep = n;
  document.getElementById(`step-${currentStep}`).classList.add('active');
  document.getElementById(`step-${currentStep}-ind`).classList.remove('done');
  document.getElementById(`step-${currentStep}-ind`).classList.add('active');
}

// ===== PASSWORD STRENGTH =====
const pass1 = document.getElementById('pass1');
if (pass1) {
  pass1.addEventListener('input', () => {
    const val = pass1.value;
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    let strength = 0;
    if (val.length >= 8) strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;
    const widths = ['0%','20%','40%','60%','80%','100%'];
    const colors = ['#ff6b6b','#ff6b6b','#ff9f43','#ffd700','#1dd3b0','#6c63ff'];
    const labels = ['Too short','Weak','Fair','Good','Strong','Very Strong'];
    fill.style.width = widths[strength];
    fill.style.background = colors[strength];
    text.textContent = labels[strength];
    text.style.color = colors[strength];
  });
}

// ===== SIGNUP SUBMIT =====
function handleSignup(e) {
  const btn = e.target.querySelector('.auth-submit-btn:last-child') || e.target.querySelector('[type="submit"]');
  if (btn) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Creating account...</span>';
    btn.disabled = true;
  }
}
