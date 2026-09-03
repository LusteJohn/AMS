<template>
  <aside class="faculty-sidebar" aria-label="Faculty navigation">
    <h2>Faculty</h2>
    <nav>
      <RouterLink to="/faculty">Dashboard</RouterLink>
      <RouterLink to="/faculty/ojt-company">OJT companies</RouterLink>
      <RouterLink to="/faculty/company-supervisors">Company supervisors</RouterLink>
    </nav>
  </aside>
</template>

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
.faculty-sidebar a.router-link-active { font-weight: 700; }
@media (max-width: 700px) {
  .faculty-sidebar {
    position: static;
    min-height: auto;
    border-right: 0;
    border-bottom: 1px solid #d9e2df;
  }
}
</style>
