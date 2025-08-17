import { useAuthStore } from '@/stores/auth'
import { checkRoutePermissions } from '@/utils/routePermissions'
import { setupLayouts } from 'virtual:meta-layouts'
import type { App } from 'vue'
import type { RouteRecordRaw } from 'vue-router/auto'
import { createRouter, createWebHistory } from 'vue-router/auto'

function recursiveLayouts(route: RouteRecordRaw): RouteRecordRaw {
  if (route.children) {
    for (let i = 0; i < route.children.length; i++)
      route.children[i] = recursiveLayouts(route.children[i])
    return route
  }
  return setupLayouts([route])[0]
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to) {
    if (to.hash)
      return { el: to.hash, behavior: 'smooth', top: 60 }
    return { top: 0 }
  },
  extendRoutes: pages => [
    ...[...pages].map(route => recursiveLayouts(route)),
  ],
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const publicRoutes = ['/login', '/register', '/forgot-password']
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchProfile()
    } catch (error) {
      authStore.clearToken()
      authStore.clearUser()
    }
  }
  if (!authStore.isLoggedIn && !publicRoutes.includes(to.path)) {
    next('/login')
    return
  }
  if (authStore.isLoggedIn && to.path === '/login') {
    next('/')
    return
  }
  if (authStore.isLoggedIn && !publicRoutes.includes(to.path)) {
    checkRoutePermissions(to, from, next)
    return
  }
  next()
})

export { router }

export default function (app: App) {
  app.use(router)
}
