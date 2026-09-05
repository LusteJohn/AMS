<template>
  <aside class="faculty-sidebar" aria-label="Faculty navigation">
    <h2>Faculty</h2>
    <nav>
      <RouterLink to="/faculty"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>Dashboard</RouterLink>
      <RouterLink to="/faculty/profile"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>Profile</RouterLink>
      <RouterLink to="/faculty/ojt-company"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V7h16v13M8 7V4h8v3M8 11h2m4 0h2m-8 4h2m4 0h2"/></svg>OJT companies</RouterLink>
      <RouterLink to="/faculty/company-supervisors"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0m3-9v6m-3-3h6"/></svg>Company supervisors</RouterLink>
      <RouterLink to="/faculty/students"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-3-3.87M9 7a4 4 0 1 1 8 0 4 4 0 0 1-8 0z"/></svg>Students</RouterLink>
      <RouterLink to="/faculty/attendance"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11h8V5l6 7-6 7v-6H3z"/></svg>Attendance</RouterLink>
      <RouterLink to="/faculty/attendance-history"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2M4 5l2-2"/></svg>Attendance history</RouterLink>
    </nav>
  </aside>
</template>

<script setup>
import '../../assets/faculty/FacultySideBar.css'
</script>

