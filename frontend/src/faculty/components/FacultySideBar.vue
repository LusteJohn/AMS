<template>
  <aside class="faculty-sidebar" aria-label="Faculty navigation">
    <h2>Faculty</h2>
    <nav>
      <RouterLink to="/faculty"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>Dashboard</RouterLink>
      <RouterLink to="/faculty/ojt-company"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V7h16v13M8 7V4h8v3M8 11h2m4 0h2m-8 4h2m4 0h2"/></svg>OJT companies</RouterLink>
      <RouterLink to="/faculty/company-supervisors"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0m3-9v6m-3-3h6"/></svg>Company supervisors</RouterLink>
      <RouterLink to="/faculty/students"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-3-3.87M9 7a4 4 0 1 1 8 0 4 4 0 0 1-8 0z"/></svg>Students</RouterLink>
      <RouterLink to="/faculty/attendance"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11h8V5l6 7-6 7v-6H3z"/></svg>Attendance</RouterLink>
    </nav>
  </aside>
</template>

<script setup>
import '../../assets/faculty/FacultySideBar.css'
</script>

<style scoped>
.faculty-sidebar {
  position: sticky;
  top: 61px;
  align-self: flex-start;
  min-width: 180px;
  min-height: calc(100vh - 61px);
  padding: 24px;
  border-right: 1px solid #d9e2df;
  background: #fffaf3;
}
.faculty-sidebar h2 { margin: 0 0 20px; }
.faculty-sidebar nav { display: grid; gap: 10px; }
.faculty-sidebar a { color: inherit; text-decoration: none; }
.faculty-sidebar a { display: inline-flex; align-items: center; gap: 8px; }
.faculty-sidebar svg { width: 18px; height: 18px; flex: 0 0 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
.faculty-sidebar a.router-link-active { font-weight: 700; }
@media (max-width: 700px) {
  .faculty-sidebar {
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
  .faculty-sidebar h2 { display: none; }
  .faculty-sidebar nav { display: flex; gap: 8px; width: 100%; overflow-x: auto; overscroll-behavior-x: contain; scrollbar-width: thin; }
  .faculty-sidebar a { flex: 0 0 auto; padding: 10px 12px; white-space: nowrap; }
}
</style>
