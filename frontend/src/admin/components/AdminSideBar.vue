<template>
  <aside class="admin-sidebar" aria-label="Admin navigation">
    <h2>Admin</h2>
    <nav>
      <RouterLink to="/admin">Dashboard</RouterLink>
      <RouterLink to="/admin/academic">Academic</RouterLink>
      <RouterLink to="/admin/accounts">User accounts</RouterLink>
      <RouterLink to="/admin/students">Students</RouterLink>
      <RouterLink to="/admin/faculty">Faculty</RouterLink>
      <RouterLink to="/admin/ojt-companies">OJT companies</RouterLink>
    </nav>
  </aside>
</template>

<style scoped>
.admin-sidebar {
  padding: 24px;
  border-right: 1px solid #d9e2df;
  min-width: 180px;
}
.admin-sidebar h2 { margin: 0 0 20px; }
.admin-sidebar nav { display: grid; gap: 10px; }
.admin-sidebar a { color: inherit; text-decoration: none; }
.admin-sidebar a.router-link-active { font-weight: 700; }
</style>
