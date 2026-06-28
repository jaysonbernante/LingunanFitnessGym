document.addEventListener('DOMContentLoaded', function () {
  var sidebar = document.querySelector('.member-sidebar');
  var toggle = document.getElementById('memberSidebarToggle');
  var backdrop = document.getElementById('memberSidebarBackdrop');

  if (!sidebar || !toggle || !backdrop) {
    return;
  }

  function setOpen(open) {
    if (open) {
      sidebar.classList.add('open');
      toggle.classList.add('open');
      backdrop.classList.add('show');
      toggle.setAttribute('aria-label', 'Close sidebar');
    } else {
      sidebar.classList.remove('open');
      toggle.classList.remove('open');
      backdrop.classList.remove('show');
      toggle.setAttribute('aria-label', 'Open sidebar');
    }
  }

  toggle.addEventListener('click', function () {
    setOpen(!sidebar.classList.contains('open'));
  });

  backdrop.addEventListener('click', function () {
    setOpen(false);
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 900) {
      setOpen(false);
    }
  });
});
