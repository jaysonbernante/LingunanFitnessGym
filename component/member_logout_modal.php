<div id="memberLogoutModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); justify-content:center; align-items:center; z-index:2000;">
  <div style="background:#fff; color:#222; border-radius:14px; padding:24px 28px; max-width:320px; width:90%; text-align:center; box-shadow:0 10px 32px rgba(0,0,0,0.25);">
    <h3 style="margin:0 0 10px;">Logout</h3>
    <p style="margin:0 0 18px;">Are you sure you want to logout?</p>
    <div style="display:flex; justify-content:center; gap:10px;">
      <form method="post" action="logout.php" style="margin:0;">
        <input type="hidden" name="confirm_logout" value="1">
        <button type="submit" style="padding:10px 16px; border:none; border-radius:8px; background:#d9534f; color:#fff; cursor:pointer;">Yes</button>
      </form>
      <button type="button" onclick="document.getElementById('memberLogoutModal').style.display='none';" style="padding:10px 16px; border:none; border-radius:8px; background:#6c757d; color:#fff; cursor:pointer;">No</button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var logoutTrigger = document.getElementById('memberLogoutTrigger');
  if (logoutTrigger) {
    logoutTrigger.addEventListener('click', function (event) {
      event.preventDefault();
      var modal = document.getElementById('memberLogoutModal');
      if (modal) {
        modal.style.display = 'flex';
      }
    });
  }
});
</script>
