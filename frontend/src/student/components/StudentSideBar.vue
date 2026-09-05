<template>
  <aside class="student-sidebar" aria-label="Student navigation">
    <h2>Student</h2>
    <nav>
      <RouterLink to="/student"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>Dashboard</RouterLink>
      <RouterLink to="/student/profile"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>Profile</RouterLink>
      <RouterLink to="/student/ojt-company"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V7h16v13M8 7V4h8v3M8 11h2m4 0h2m-8 4h2m4 0h2"/></svg>OJT company</RouterLink>
      <RouterLink to="/student/selected-ojt-company"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/><path d="m17 16 2 2 3-4"/></svg>Selected OJT company</RouterLink>
      <RouterLink to="/student/company-supervisors"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0m3-9v6m-3-3h6"/></svg>Company supervisors</RouterLink>
      <RouterLink to="/student/attendance"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4m8-4v4M7 11h10M7 15h6"/></svg>Attendance</RouterLink>
      <RouterLink to="/student/attendance-history"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2M4 5l2-2"/></svg>Attendance history</RouterLink>
      <RouterLink to="/student/company-attendance"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2M5 4l2-1"/></svg>Company schedule</RouterLink>
    </nav>
  </aside>
</template>

<script setup>
import '../../assets/student/StudentSideBar.css'
</script>

<style scoped>
.student-sidebar { position: sticky; top: 61px; align-self: flex-start; min-width: 180px; min-height: calc(100vh - 61px); padding: 24px; border-right: 1px solid #d9e2df; background: #fffaf3; }
.student-sidebar h2 { margin: 0 0 20px; }
.student-sidebar nav { display: grid; gap: 10px; }
.student-sidebar a { color: inherit; text-decoration: none; }
.student-sidebar a { display: inline-flex; align-items: center; gap: 8px; }
.student-sidebar svg { width: 18px; height: 18px; flex: 0 0 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
.student-sidebar a.router-link-active { font-weight: 700; }
@media (max-width: 700px) {
  .student-sidebar {
    position: fixed;
    inset: auto 0 0;
    z-index: 30;
    display: flex;
    align-items: center;
    min-width: 0;
    min-height: 0;
    width: 100%;
    box-sizing: border-box;
    padding: 8px 12px calc(8px + env(safe-area-inset-bottom));
    border-top: 1px solid #d9e2df;
    border-right: 0;
    background: #fffaf3;
  }
  .student-sidebar h2 { display: none; }
  .student-sidebar nav { display: flex; gap: 8px; width: 100%; overflow-x: auto; overscroll-behavior-x: contain; scrollbar-width: thin; }
  .student-sidebar a { flex: 0 0 auto; padding: 10px 12px; white-space: nowrap; }
}
</style>
