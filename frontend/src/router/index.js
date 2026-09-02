import { createRouter, createWebHistory } from 'vue-router'

import Login from '../auth/Login.vue'
import AdminDashboard from '../admin/AdminDashboard.vue'
import Academic from '../admin/Academic.vue'
import UserAccount from '../admin/UserAccount.vue'
import FacultyDashboard from '../faculty/FacultyDashboard.vue'
import StudentDashboard from '../student/StudentDashboard.vue'
import StudentProfile from '../student/StudentProfile.vue'

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
    { path: '/admin', component: AdminDashboard, beforeEnter: requireRole('admin'), meta: { requiresAuth: true } },
    { path: '/admin/academic', component: Academic, beforeEnter: requireRole('admin'), meta: { requiresAuth: true } },
    { path: '/admin/accounts', component: UserAccount, beforeEnter: requireRole('admin'), meta: { requiresAuth: true } },
    { path: '/faculty', component: FacultyDashboard, beforeEnter: requireRole('faculty'), meta: { requiresAuth: true } },
    { path: '/student', component: StudentDashboard, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
    { path: '/student/profile', component: StudentProfile, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
  ],
})

export default router
