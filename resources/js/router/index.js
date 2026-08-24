import { createRouter, createWebHistory } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'

// Vistas de Autenticación
const LoginView = () => import('@/views/auth/LoginView.vue')
const ForgotPasswordView = () => import('@/views/auth/ForgotPasswordView.vue')
const ResetPasswordView = () => import('@/views/auth/ResetPasswordView.vue')

// Vistas de Dashboard principales
const AdminDashboard = () => import('@/views/admin/DashboardView.vue')
const ManagerDashboard = () => import('@/views/manager/DashboardView.vue')
const ClientDashboard = () => import('@/views/client/DashboardView.vue')

const routes = [
    {
        path: '/',
        name: 'login',
        component: LoginView,
        meta: { guest: true },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPasswordView,
        meta: { guest: true },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: ResetPasswordView,
        meta: { guest: true },
    },
    {
        path: '/admin',
        component: AppLayout,
        meta: { requiresAuth: true, roles: ['admin'] },
        children: [
            { path: 'dashboard', name: 'admin.dashboard', component: AdminDashboard },
            { path: 'users', name: 'admin.users', component: () => import('@/views/admin/UsersView.vue') },
            { path: 'case-managers', name: 'admin.case-managers', component: () => import('@/views/admin/CaseManagersView.vue') },
            { path: 'schedule', name: 'admin.schedule', component: () => import('@/views/admin/ScheduleView.vue') },
            { path: 'appointments', name: 'admin.appointments', component: () => import('@/views/admin/AppointmentsView.vue') },
            { path: 'appointments/:date', name: 'admin.appointments.day', component: () => import('@/views/admin/AppointmentDayView.vue') },
        ],
    },
    {
        path: '/manager',
        component: AppLayout,
        meta: { requiresAuth: true, roles: ['case_manager'] },
        children: [
            { path: 'dashboard', name: 'manager.dashboard', component: ManagerDashboard },
            { path: 'clients', name: 'manager.clients', component: () => import('@/views/manager/ClientsView.vue') },
            { path: 'appointments', name: 'manager.appointments', component: () => import('@/views/manager/ManagerCalendarView.vue') },
            { path: 'appointments/:date', name: 'manager.appointments.day', component: () => import('@/views/manager/ManagerAppointmentDayView.vue') },
            { path: 'appointments-list', name: 'manager.appointments.list', component: () => import('@/views/manager/AppointmentsListView.vue') },
        ],
    },
    {
        path: '/client',
        component: AppLayout,
        meta: { requiresAuth: true, roles: ['client'] },
        children: [
            { path: 'dashboard', name: 'client.dashboard', component: ClientDashboard },
            { path: 'appointments-list', name: 'client.appointments.list', component: () => import('@/views/client/AppointmentsListView.vue') },
            { path: 'appointments/new', name: 'client.appointments.create', component: () => import('@/views/client/AppointmentCreateView.vue') },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
})

router.beforeEach((to) => {
    const token = localStorage.getItem('token')
    let role = null

    try {
        const user = JSON.parse(localStorage.getItem('user') || 'null')
        role = user?.role || null
    } catch {
        localStorage.removeItem('user')
        localStorage.removeItem('token')
    }

    // Si la ruta requiere guest y ya hay token, redirigir según su rol
    if (to.meta.guest && token) {
        return getRoleRoute(role)
    }

    // Verificar si la ruta o sus ancestros requieren autenticación
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
    if (requiresAuth && !token) {
        return { name: 'login' }
    }

    // Verificar roles acumulados en la jerarquía de rutas (to.matched)
    const allowedRoles = to.matched.find(record => record.meta.roles)?.meta.roles
    if (allowedRoles && token && !allowedRoles.includes(role)) {
        return getRoleRoute(role)
    }

    return true
})

function getRoleRoute(role) {
    const map = {
        admin: { name: 'admin.dashboard' },
        case_manager: { name: 'manager.dashboard' },
        client: { name: 'client.dashboard' },
    }
    return map[role] || { name: 'login' }
}

export default router