document.addEventListener("DOMContentLoaded", function () {
  const hamburgerBtn = document.getElementById("hamburger-btn");
  const sidebar = document.querySelector(".sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  const closeSidebarBtn = document.getElementById("close-sidebar-btn"); // Optional close button inside sidebar

  function toggleSidebar() {
    sidebar.classList.toggle("sidebar-open");
    overlay.classList.toggle("active");
    document.body.classList.toggle("overflow-hidden"); // Prevent background scrolling
  }

  function closeSidebar() {
    sidebar.classList.remove("sidebar-open");
    overlay.classList.remove("active");
    document.body.classList.remove("overflow-hidden");
  }

  if (hamburgerBtn) {
    hamburgerBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      toggleSidebar();
    });
  }

  if (overlay) {
    overlay.addEventListener("click", closeSidebar);
  }

  if (closeSidebarBtn) {
    closeSidebarBtn.addEventListener("click", closeSidebar);
  }

  // Close sidebar when clicking a link (optional, good for mobile)
  const sidebarLinks = document.querySelectorAll(".sidebar-nav a");
  sidebarLinks.forEach((link) => {
    link.addEventListener("click", function () {
      if (window.innerWidth < 768) {
        closeSidebar();
      }
    });
  });

  // Handle resize events to reset state if screen becomes large
  window.addEventListener("resize", function () {
    if (window.innerWidth >= 768) {
      closeSidebar();
    }
  });
});
