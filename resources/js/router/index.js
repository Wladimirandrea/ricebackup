// resources/js/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'

const LoginView = () => import('@/views/auth/LoginView.vue')
const ForgotPasswordView = () => import('@/views/auth/ForgotPasswordView.vue')
const ResetPasswordView = () => import('@/views/auth/ResetPasswordView.vue')
const AdminDashboard = () => import('@/views/admin/DashboardView.vue')
const ManagerDashboard = () => import('@/views/manager/DashboardView.vue')
const ClientDashboard = () => import('@/views/client/DashboardView.vue')
const UsersView = () => import('@/views/admin/UsersView.vue')

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
            { path: 'users', name: 'admin.users', component: UsersView },
            { path: 'case-managers', name: 'admin.case-managers', component: () => import('@/views/admin/CaseManagersView.vue') },
            { path: 'schedule', name: 'admin.schedule', component: () => import('@/views/admin/ScheduleView.vue'), meta: { requiresAuth: true, role: 'admin' } },
            { path: 'appointments', name: 'admin.appointments', component: () => import('@/views/admin/AppointmentsView.vue'), meta: { requiresAuth: true, role: 'admin' } },
            
            {
                path: 'appointments/:date',
                name: 'admin.appointments.day',
                component: () => import('@/views/admin/AppointmentDayView.vue'),
                meta: { requiresAuth: true, role: 'admin' },
            },
        ],
    },
    {
        path: '/manager',
        component: AppLayout,
        meta: { requiresAuth: true, roles: ['case_manager'] },
        children: [
            { path: 'dashboard', name: 'manager.dashboard', component: ManagerDashboard },
            { path: 'clients', name: 'manager.clients', component: () => import('@/views/manager/ClientsView.vue') },
            { path: 'appointments', name: 'manager.appointments', component: () => import('@/views/manager/ManagerCalendarView.vue'), meta: { requiresAuth: true, role: 'case_manager' },
            },
            { path: 'appointments/:date', name: 'manager.appointments.day', component: () => import('@/views/manager/ManagerAppointmentDayView.vue'), meta: { requiresAuth: true, role: 'case_manager' },},
        ],
    },
    {
        path: '/client',
        component: AppLayout,
        meta: { requiresAuth: true, roles: ['client'] },
        children: [
            { path: 'dashboard', name: 'client.dashboard', component: ClientDashboard },
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
    const user = JSON.parse(localStorage.getItem('user') || 'null')
    const role = user?.role || null

    if (to.meta.guest && token) return getRoleRoute(role)
    if (to.meta.requiresAuth && !token) return { name: 'login' }
    if (to.meta.roles && token && !to.meta.roles.includes(role)) return getRoleRoute(role)

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