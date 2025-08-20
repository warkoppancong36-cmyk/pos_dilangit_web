export default [
  {
    title: 'Dashboard',
    to: { name: 'root' },
    icon: { icon: 'tabler-smart-home' },
  },
  // {
  //   title: 'Point of Sale',
  //   to: { name: 'pos' },
  //   icon: { icon: 'tabler-cash-register' },
  //   role: ['admin', 'manager', 'kasir'], // Cashier, manager, and admin can access
  // },
  {
    title: 'Management',
    icon: { icon: 'tabler-settings' },
    children: [
      {
        title: 'Kategori',
        to: { name: 'categories-management' },
        icon: { icon: 'tabler-category' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
      {
        title: 'Pajak',
        to: { name: 'ppn-management' },
        icon: { icon: 'tabler-percentage' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
      {
        title: 'Item',
        to: { name: 'items-management' },
        icon: { icon: 'tabler-components' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
      {
        title: 'Produk',
        to: { name: 'products-management' },
        icon: { icon: 'tabler-box' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
      // {
      //   title: 'Komposisi Produk',
      //   to: { name: 'product-items-management' },
      //   icon: { icon: 'tabler-assembly' },
      //   role: ['admin', 'manager'], // Only admin and manager can access
      // },

      // VARIANT REMOVED - Using Product-Item relationship instead
      // {
      //   title: 'Variant',
      //   to: { name: 'variants-management' },
      //   icon: { icon: 'tabler-versions' },
      //   role: ['admin', 'manager'],
      // },
      {
        title: 'Supplier',
        to: { name: 'suppliers-management' },
        icon: { icon: 'tabler-truck-delivery' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
      {
        title: 'Customer',
        to: { name: 'customers-management' },
        icon: { icon: 'tabler-users' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
      {
        title: 'Purchase',
        to: { name: 'purchases-management' },
        icon: { icon: 'tabler-shopping-cart' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
      {
        title: 'Inventory',
        to: { name: 'inventory-management' },
        icon: { icon: 'tabler-package' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
      // {
      //   title: 'HPP Management',
      //   to: { name: 'hpp-management' },
      //   icon: { icon: 'tabler-calculator' },
      //   role: ['admin', 'manager'], // Only admin and manager can access
      // },
      {
        title: 'Diskon',
        to: { name: 'discounts-management' },
        icon: { icon: 'tabler-tag' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
      {
        title: 'Promosi',
        to: { name: 'promotions-management' },
        icon: { icon: 'tabler-speakerphone' },
        role: ['admin', 'manager'], // Only admin and manager can access
      },
    ],
  },
  {
    title: 'System Administration',
    icon: { icon: 'tabler-shield-lock' },
    role: ['admin'], // Only admin can access
    children: [
      {
        title: 'Asset Management',
        to: { name: 'assets' },
        icon: { icon: 'tabler-files' },
        role: ['admin'],
      },
      {
        title: 'Role Management',
        to: { name: 'roles' },
        icon: { icon: 'tabler-users-group' },
        role: ['admin'],
      },
      {
        title: 'User Management',
        to: { name: 'users' },
        icon: { icon: 'tabler-user-cog' },
        role: ['admin'],
      },
    ],
  },
]
