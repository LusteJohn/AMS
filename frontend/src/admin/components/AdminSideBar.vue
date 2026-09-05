<template>
  <aside class="admin-sidebar" aria-label="Admin navigation">
    <h2>Admin</h2>
    <nav>
      <RouterLink to="/admin"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>Dashboard</RouterLink>
      <RouterLink to="/admin/academic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16M6 16V9m6 7V5m6 11v-4"/></svg>Academic</RouterLink>
      <RouterLink to="/admin/accounts"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0M19 5v4m-2-2h4"/></svg>User accounts</RouterLink>
      <RouterLink to="/admin/students"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0m3-9v6m-3-3h6"/></svg>Students</RouterLink>
      <RouterLink to="/admin/faculty"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10 12 5l9 5-9 5zM6 12v5c3 2 9 2 12 0v-5"/></svg>Faculty</RouterLink>
      <RouterLink to="/admin/ojt-companies"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V7h16v13M8 7V4h8v3M8 11h2m4 0h2m-8 4h2m4 0h2"/></svg>OJT companies</RouterLink>
      <RouterLink to="/admin/company-supervisors"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0m3-9v6m-3-3h6"/></svg>Company supervisors</RouterLink>
      <RouterLink to="/admin/attendance"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4m8-4v4M7 11h10M7 15h6"/></svg>Student attendance</RouterLink>
      <RouterLink to="/admin/attendance-history"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2M4 5l2-2"/></svg>Attendance history</RouterLink>
    </nav>
  </aside>
</template>

<script setup>
import '../../assets/admin/AdminSideBar.css'
</script>

