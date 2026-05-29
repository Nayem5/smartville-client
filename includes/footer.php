</div><!-- end page-body -->
</div><!-- end main-content -->

<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('collapsed');
}
function toggleNotif() {
  document.getElementById('notifDropdown').classList.toggle('show');
  document.getElementById('userDropdown').classList.remove('show');
}
function toggleUserMenu() {
  document.getElementById('userDropdown').classList.toggle('show');
  document.getElementById('notifDropdown').classList.remove('show');
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.notif-wrap')) document.getElementById('notifDropdown').classList.remove('show');
  if (!e.target.closest('.user-menu')) document.getElementById('userDropdown').classList.remove('show');
});
// Auto-hide alerts
document.querySelectorAll('.alert').forEach(a => setTimeout(() => a.style.display='none', 5000));
</script>
</body>
</html>
