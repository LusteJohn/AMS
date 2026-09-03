<template>
  <aside class="student-sidebar" aria-label="Student navigation">
    <h2>Student</h2>
    <nav>
      <RouterLink to="/student">Dashboard</RouterLink>
      <RouterLink to="/student/profile">Profile</RouterLink>
      <RouterLink to="/student/ojt-company">OJT company</RouterLink>
      <RouterLink to="/student/selected-ojt-company">Selected OJT company</RouterLink>
      <RouterLink to="/student/company-supervisors">Company supervisors</RouterLink>
    </nav>
  </aside>
</template>

<style scoped>
.student-sidebar { min-width: 180px; padding: 24px; border-right: 1px solid #d9e2df; }
.student-sidebar h2 { margin: 0 0 20px; }
.student-sidebar nav { display: grid; gap: 10px; }
.student-sidebar a { color: inherit; text-decoration: none; }
.student-sidebar a.router-link-active { font-weight: 700; }
</style>
