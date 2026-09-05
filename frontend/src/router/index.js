import { createRouter, createWebHistory } from 'vue-router'

import Login from '../auth/Login.vue'
import AdminDashboard from '../admin/AdminDashboard.vue'
import Academic from '../admin/Academic.vue'
import UserAccount from '../admin/UserAccount.vue'
import Student from '../admin/Student.vue'
import Faculty from '../admin/Faculty.vue'
import AdminOjtCompany from '../admin/AdminOjtCompany.vue'
import FacultyDashboard from '../faculty/FacultyDashboard.vue'
import FacultyStudent from '../faculty/FacultyStudent.vue'
import FacultyStudentAttendance from '../faculty/FacultyStudentAttendance.vue'
import FacultyStudentAttendanceHistory from '../faculty/FacultyStudentAttendanceHistory.vue'
import FacultyProfile from '../faculty/FacultyProfile.vue'
import StudentDashboard from '../student/StudentDashboard.vue'
import StudentProfile from '../student/StudentProfile.vue'
import StudentOjtCompany from '../student/StudentOjtCompany.vue'
import StudentSelectedOjtCompany from '../student/StudentSelectedOjtCompany.vue'
import FacultyOjtCompany from '../faculty/FacultyOjtCompany.vue'
import AdminCompanySupervisor from '../admin/AdminCompanySupervisor.vue'
import FacultyCompanySupervisor from '../faculty/FacultyCompanySupervisor.vue'
import StudentCompanySupervisor from '../student/StudentCompanySupervisor.vue'
import StudentAttendance from '../student/StudentAttendance.vue'
import StudentCompanyAttendance from '../student/StudentCompanyAttendance.vue'
import StudentAttendanceHistory from '../student/StudentAttendanceHistory.vue'
import AdminStudentAttendance from '../admin/StudentAttendance.vue'
import AdminStudentAttendanceHistory from '../admin/StudentAttendanceHistory.vue'

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
    { path: '/admin/students', component: Student, beforeEnter: requireRole('admin'), meta: { requiresAuth: true } },
    { path: '/admin/faculty', component: Faculty, beforeEnter: requireRole('admin'), meta: { requiresAuth: true } },
    { path: '/admin/ojt-companies', component: AdminOjtCompany, beforeEnter: requireRole('admin'), meta: { requiresAuth: true } },
    { path: '/admin/company-supervisors', component: AdminCompanySupervisor, beforeEnter: requireRole('admin'), meta: { requiresAuth: true } },
    { path: '/admin/attendance', component: AdminStudentAttendance, beforeEnter: requireRole('admin'), meta: { requiresAuth: true } },
    { path: '/admin/attendance-history', component: AdminStudentAttendanceHistory, beforeEnter: requireRole('admin'), meta: { requiresAuth: true } },
    { path: '/faculty', component: FacultyDashboard, beforeEnter: requireRole('faculty'), meta: { requiresAuth: true } },
    { path: '/faculty/profile', component: FacultyProfile, beforeEnter: requireRole('faculty'), meta: { requiresAuth: true } },
    { path: '/faculty/students', component: FacultyStudent, beforeEnter: requireRole('faculty'), meta: { requiresAuth: true } },
    { path: '/faculty/attendance', component: FacultyStudentAttendance, beforeEnter: requireRole('faculty'), meta: { requiresAuth: true } },
    { path: '/faculty/attendance-history', component: FacultyStudentAttendanceHistory, beforeEnter: requireRole('faculty'), meta: { requiresAuth: true } },
    { path: '/faculty/ojt-company', component: FacultyOjtCompany, beforeEnter: requireRole('faculty'), meta: { requiresAuth: true } },
    { path: '/faculty/company-supervisors', component: FacultyCompanySupervisor, beforeEnter: requireRole('faculty'), meta: { requiresAuth: true } },
    { path: '/student', component: StudentDashboard, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
    { path: '/student/profile', component: StudentProfile, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
    { path: '/student/ojt-company', component: StudentOjtCompany, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
    { path: '/student/selected-ojt-company', component: StudentSelectedOjtCompany, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
    { path: '/student/company-supervisors', component: StudentCompanySupervisor, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
    { path: '/student/attendance', component: StudentAttendance, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
    { path: '/student/attendance-history', component: StudentAttendanceHistory, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
    { path: '/student/company-attendance', component: StudentCompanyAttendance, beforeEnter: requireRole('student'), meta: { requiresAuth: true } },
  ],
})

export default router
