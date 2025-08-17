import { useAuthStore } from '@/stores/auth'
import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'

export interface RoutePermission {
  roles?: string[]
  permissions?: string[]
  requireAll?: boolean
}

export function checkRoutePermissions(
  to: RouteLocationNormalized,
  from: RouteLocationNormalized,
  next: NavigationGuardNext
) {
  const authStore = useAuthStore()
  if (!authStore.isLoggedIn) {
    next('/login')
    return
  }
  const routePermissions = to.meta.permissions as RoutePermission | undefined
  if (!routePermissions) {
    next()
    return
  }
  const { roles, permissions, requireAll = false } = routePermissions
  if (roles && roles.length > 0) {
    const userRole = authStore.userRole
    if (!userRole || !roles.includes(userRole)) {
      if (authStore.isAdmin) {
        next('/admin/dashboard')
      } else if (authStore.isManager) {
        next('/manager/dashboard')
      } else if (authStore.isCashier) {
        next('/cashier/dashboard')
      } else {
        next('/dashboard')
      }
      return
    }
  }
  if (permissions && permissions.length > 0) {
    const hasPermission = requireAll 
      ? authStore.hasAllPermissions(permissions)
      : authStore.hasAnyPermission(permissions)
    if (!hasPermission) {
      next({
        path: '/dashboard',
        query: { error: 'insufficient_permissions' }
      })
      return
    }
  }
  next()
}

export function defineRoutePermissions(permissions: RoutePermission) {
  return { permissions }
}

export const RoutePermissions = {
  ADMIN_ONLY: defineRoutePermissions({
    roles: ['Super Admin']
  }),
  MANAGEMENT: defineRoutePermissions({
    roles: ['Super Admin', 'Manager']
  }),
  STAFF: defineRoutePermissions({
    roles: ['Super Admin', 'Manager', 'Supervisor', 'Kasir']
  }),
  USER_MANAGEMENT: defineRoutePermissions({
    permissions: ['manage_users']
  }),
  PRODUCT_MANAGEMENT: defineRoutePermissions({
    permissions: ['manage_products']
  }),
  SALES_REPORTS: defineRoutePermissions({
    permissions: ['view_sales_reports']
  }),
  INVENTORY_MANAGEMENT: defineRoutePermissions({
    permissions: ['manage_inventory']
  }),
  TRANSACTION_ACCESS: defineRoutePermissions({
    permissions: ['process_transactions']
  }),
  FINANCIAL_REPORTS: defineRoutePermissions({
    permissions: ['view_sales_reports', 'view_financial_data'],
    requireAll: true
  })
}
