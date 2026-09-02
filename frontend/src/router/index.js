import { createRouter, createWebHistory } from 'vue-router'

import Login from '../auth/Login.vue'
import AdminDashboard from '../admin/AdminDashboard.vue'
import FacultyDashboard from '../faculty/FacultyDashboard.vue'
import StudentDashboard from '../student/StudentDashboard.vue'

function storedUser() {
  try {
    return JSON.parse(sessionStorage.getItem('user') || 'null')
  } catch {
    return null
  }
}

function requireRole(role) {
  return () => {
    const user = storedUser()
    return user?.role?.toLowerCase() === role ? true : '/login'
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: Login },
    { path: '/admin', component: AdminDashboard, beforeEnter: requireRole('admin') },
    { path: '/faculty', component: FacultyDashboard, beforeEnter: requireRole('faculty') },
    { path: '/student', component: StudentDashboard, beforeEnter: requireRole('student') },
  ],
})

export default router
