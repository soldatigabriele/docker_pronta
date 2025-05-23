import { createRouter, createWebHistory } from 'vue-router'
import authService from '../services/auth'
import Login from '../components/Login.vue'
import Home from '../components/Home.vue'

const routes = [
  {
    path: '/',
    redirect: '/home'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresGuest: true }
  },
  {
    path: '/home',
    name: 'Home', 
    component: Home,
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard for authentication
router.beforeEach(async (to, from, next) => {
  const isAuthenticated = authService.isAuthenticated()
  
  // If route requires authentication and user is not authenticated
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login')
    return
  }
  
  // If route requires guest (login page) and user is authenticated
  if (to.meta.requiresGuest && isAuthenticated) {
    next('/home')
    return
  }
  
  // If user is authenticated but we haven't fetched user data yet
  if (isAuthenticated && !authService.getUser()) {
    try {
      await authService.getCurrentUser()
    } catch (error) {
      console.error('Failed to get current user:', error)
      // If fetching user fails, redirect to login
      next('/login')
      return
    }
  }
  
  next()
})

export default router 