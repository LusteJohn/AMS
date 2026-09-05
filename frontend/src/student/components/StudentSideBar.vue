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

