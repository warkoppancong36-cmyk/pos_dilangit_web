<script setup lang="ts">
import InventoryMovementsDialog from '@/components/InventoryMovementsDialog.vue'
import { useInventory } from '@/composables/useInventory'
import { useStockMovements } from '@/composables/useStockMovements'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'
import { computed, onMounted, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'

// Vuetify display composable for responsive design
const { xs } = useDisplay()

// Function to refresh inventory data
const refreshCurrentView = () => {
  fetchInventoryList()
  fetchStats()
  fetchLowStockAlerts()
}

const {
  inventoryList,
  loading,
  saveLoading,
  stats,
  lowStockItems,
  alertsLoading,
  stockUpdateDialog,
  reorderDialog,
  movementsDialog,
  selectedInventory,
  selectedItems,
  currentPage,
  totalItems,
  itemsPerPage,
  filters,
  errorMessage,
  successMessage,
  modalErrorMessage,
  stockUpdateForm,
  reorderForm,
  hasSelectedItems,
  totalStockValue,
  lowStockCount,
  outOfStockCount,
  stockStatusOptions,
  fetchInventoryList,
  fetchStats,
  fetchLowStockAlerts,
  updateStock,
  setReorderLevel,
  openStockUpdateDialog,
  openReorderDialog,
  openMovementsDialog,
  closeStockUpdateDialog,
  closeReorderDialog,
  closeMovementsDialog,
  clearModalError,
  onPageChange,
  onItemsPerPageChange,
  handleFiltersUpdate,
  formatCurrency,
  getStockStatusColor,
  getStockStatusText,
  // Pagination computed
  totalPages,
  hasNextPage,
  hasPrevPage,
  paginationInfo
} = useInventory()

// Stock movements for enhanced functionality
const {
  recordStockMovement,
  openStockInDialog,
  openStockOutDialog,
  openAdjustmentDialog,
  getStockStatus,
  getStockPercentage,
  movementDialog,
  adjustmentDialog,
  selectedItem,
  movementFormData,
  saveLoading: movementSaveLoading,
  closeMovementDialog,
  successMessage: stockMovementSuccessMessage,
  modalErrorMessage: stockMovementErrorMessage,
  clearModalError: clearStockMovementError,
} = useStockMovements(refreshCurrentView) // Pass callback function for inventory refresh

// Helper function to convert inventory item to Item format for stock movements
const convertInventoryItemToItem = (inventoryItem: any) => {
  if (!inventoryItem.item) return null
  
  return {
    id_item: inventoryItem.item.id_item,
    item_code: inventoryItem.item.item_code,
    name: inventoryItem.item.name,
    description: inventoryItem.item.description || '',
    unit: inventoryItem.item.unit,
    cost_per_unit: parseFloat(inventoryItem.item.cost_per_unit),
    current_stock: inventoryItem.current_stock,
    minimum_stock: inventoryItem.reorder_level || 0,
    active: true,
    storage_location: inventoryItem.item.storage_location || '',
    expiry_date: inventoryItem.item.expiry_date || null,
    properties: inventoryItem.item.properties || null,
    created_by: inventoryItem.item.created_by || null,
    updated_by: inventoryItem.item.updated_by || null,
    created_at: inventoryItem.item.created_at,
    updated_at: inventoryItem.item.updated_at,
    deleted_at: inventoryItem.item.deleted_at || null
  }
}

// Stock movement handlers for inventory items
const handleStockIn = (inventoryItem: any) => {
  const item = convertInventoryItemToItem(inventoryItem)
  if (item) {
    openStockInDialog(item)
  }
}

const handleStockOut = (inventoryItem: any) => {
  const item = convertInventoryItemToItem(inventoryItem)
  if (item) {
    openStockOutDialog(item)
  }
}

const handleAdjustment = (inventoryItem: any) => {
  const item = convertInventoryItemToItem(inventoryItem)
  if (item) {
    openAdjustmentDialog(item)
  }
}

// Computed properties for inventory view
const currentStats = computed(() => {
  return stats.value
})

const currentData = computed(() => {
  return inventoryList.value
})

const currentLoading = computed(() => {
  return loading.value
})

onMounted(() => {
  fetchInventoryList()
  fetchStats()
  fetchLowStockAlerts()
})

// Export functionality
const exportLoading = ref(false)

// Function to check current authentication status
const checkAuthStatus = () => {
  console.log('🔍 === AUTH STATUS CHECK ===')
  
  // Check all possible token sources
  const cookieToken = useCookie('accessToken').value
  const localToken = localStorage.getItem('token')
  const authToken = localStorage.getItem('auth_token')
  const accessToken = localStorage.getItem('accessToken')
  
  console.log('🍪 Cookie accessToken:', cookieToken ? `Found (${cookieToken.substring(0, 20)}...)` : 'Not found')
  console.log('💾 localStorage token:', localToken ? `Found (${localToken.substring(0, 20)}...)` : 'Not found')
  console.log('💾 localStorage auth_token:', authToken ? `Found (${authToken.substring(0, 20)}...)` : 'Not found')
  console.log('💾 localStorage accessToken:', accessToken ? `Found (${accessToken.substring(0, 20)}...)` : 'Not found')
  
  // Check if any auth store exists
  try {
    const authStore = useAuthStore()
    console.log('🏪 Auth Store isLoggedIn:', authStore.isLoggedIn)
    console.log('🏪 Auth Store user:', authStore.user?.name || 'No user')
    console.log('🏪 Auth Store token:', authStore.token ? `Found (${authStore.token.substring(0, 20)}...)` : 'Not found')
  } catch (e) {
    console.log('🏪 Auth Store not available:', e)
  }
  
  console.log('=========================')
  
  return {
    cookieToken,
    localToken,
    authToken,
    accessToken
  }
}

// Helper function to get authentication token with detailed debugging
const getAuthToken = () => {
  try {
    // First check auth status
    const authStatus = checkAuthStatus()
    
    // Try to get token from cookie first (primary method)
    const cookieToken = useCookie('accessToken').value
    console.log('🔍 Checking cookie token:', cookieToken ? 'Token found in cookie' : 'No token in cookie')
    
    if (cookieToken) {
      console.log('✅ Using cookie token (first 20 chars):', cookieToken.substring(0, 20) + '...')
      return cookieToken
    }
    
    // Fallback to localStorage methods used in other parts
    console.log('🔍 Checking localStorage methods...')
    const localToken = localStorage.getItem('token') || 
                      localStorage.getItem('auth_token') || 
                      localStorage.getItem('accessToken')
    
    if (localToken) {
      console.log('✅ Using localStorage token (first 20 chars):', localToken.substring(0, 20) + '...')
    } else {
      console.log('❌ No token found in any storage')
    }
    
    return localToken || null
  } catch (error) {
    console.warn('❌ Error getting auth token:', error)
    // Fallback to localStorage
    const fallbackToken = localStorage.getItem('token') || 
                         localStorage.getItem('auth_token') || 
                         localStorage.getItem('accessToken') || 
                         null
    console.log('🔄 Fallback token result:', fallbackToken ? 'Found fallback token' : 'No fallback token')
    return fallbackToken
  }
}

// Function to fetch ALL inventory data for export (bypass pagination)
const fetchAllInventoryForExport = async () => {
  try {
    console.log('🚀 Starting fetchAllInventoryForExport...')
    
    const params = new URLSearchParams({
      per_page: '999999', // Very large number to get all data
      page: '1',
      // Include current filters but with unlimited results
      search: filters.value.search || '',
      stock_status: filters.value.stock_status || 'all'
    })

    console.log('📝 Request params:', params.toString())

    // Check all available cookies and localStorage items for debugging
    console.log('🍪 Available cookies:')
    document.cookie.split(';').forEach(cookie => {
      const [name, ...rest] = cookie.trim().split('=')
      if (name.toLowerCase().includes('token') || name.toLowerCase().includes('auth')) {
        console.log(`  - ${name}: ${rest.join('=').substring(0, 20)}...`)
      }
    })
    
    console.log('💾 Available localStorage items:')
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i)
      if (key && (key.toLowerCase().includes('token') || key.toLowerCase().includes('auth'))) {
        const value = localStorage.getItem(key)
        console.log(`  - ${key}: ${value?.substring(0, 20)}...`)
      }
    }

    // Try using useApi composable first (preferred method)
    try {
      console.log('🔄 Attempting to fetch with useApi composable...')
      const { data } = await useApi(`/inventory?${params}`).get().json()
      
      if (data.value && data.value.success) {
        console.log('✅ Successfully fetched with useApi:', data.value.data.data?.length || 0, 'items')
        return data.value.data.data || []
      } else {
        console.log('❌ useApi response not successful:', data.value)
        throw new Error(data.value?.message || 'useApi failed')
      }
    } catch (useApiError) {
      console.warn('❌ useApi failed, trying manual fetch:', useApiError)
      
      // Fallback to manual fetch
      const accessToken = getAuthToken()
      console.log('🔐 Token for manual fetch:', accessToken ? `Token available (${accessToken.substring(0, 20)}...)` : 'No token')

      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(accessToken && { 'Authorization': `Bearer ${accessToken}` }),
      }
      
      console.log('📋 Request headers:', {
        ...headers,
        Authorization: headers.Authorization ? headers.Authorization.substring(0, 30) + '...' : 'Not set'
      })

      const response = await fetch(`/api/inventory?${params}`, {
        method: 'GET',
        headers
      })

      console.log('📊 Response status:', response.status, response.statusText)
      console.log('📋 Response headers:', Object.fromEntries(response.headers.entries()))

      if (!response.ok) {
        const errorText = await response.text()
        console.error('❌ Manual fetch API Error Response:', errorText)
        console.error('📊 Error details - Status:', response.status, 'StatusText:', response.statusText)
        
        // Try to parse error as JSON for better debugging
        try {
          const errorJson = JSON.parse(errorText)
          console.error('📋 Parsed error JSON:', errorJson)
        } catch (e) {
          console.error('📋 Error response is not JSON:', errorText)
        }
        
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const result = await response.json()
      console.log('✅ Manual fetch successful response:', {
        success: result.success,
        dataLength: result.data?.data?.length || 0,
        message: result.message
      })
      
      if (result.success) {
        console.log('✅ Successfully fetched with manual fetch:', result.data.data?.length || 0, 'items')
        return result.data.data || []
      } else {
        console.error('❌ Manual fetch response not successful:', result)
        throw new Error(result.message || 'Failed to fetch inventory data')
      }
    }
    
  } catch (error) {
    console.error('💥 Fetch all inventory error:', error)
    throw new Error('Gagal mengambil data inventory lengkap: ' + (error instanceof Error ? error.message : 'Unknown error'))
  }
}

// Upload history state
const uploadHistoryDialog = ref(false)
const uploadHistory = ref<any[]>([])
const uploadHistoryLoading = ref(false)
const uploadHistoryPage = ref(1)
const uploadHistoryPerPage = ref(10)
const uploadHistoryTotal = ref(0)

const exportToExcel = async () => {
  try {
    exportLoading.value = true
    
    // Fetch ALL inventory data for export (not limited to current page)
    const allInventoryData = await fetchAllInventoryForExport()
    
    // Dynamic import for better performance
    const XLSX = await import('xlsx')
    
    // Prepare data for export using ALL data
    const exportData = allInventoryData.map((item: any, index: number) => ({
      'No': index + 1,
      'Produk/Variant': `${item.item?.name || item.product?.name || 'Unknown Item'} (SKU: ${item.item?.item_code || item.product?.sku || '-'})`,
      'Unit': item.item?.unit || 'pcs',
      'Stok Saat Ini': item.current_stock || 0,
      'Stok Tersedia': item.available_stock || 0,
      'Stok Minimum': item.reorder_level || 0,
      'Status': getStockStatusText(item),
      'Harga Rata-rata': formatCurrency(item.average_cost || 0),
      'Dibuat': item.created_at ? new Date(item.created_at).toLocaleDateString('id-ID') : '-',
      'Diperbarui': item.updated_at ? new Date(item.updated_at).toLocaleDateString('id-ID') : '-'
    }))
    
    // Create workbook and worksheet
    const workbook = XLSX.utils.book_new()
    const worksheet = XLSX.utils.json_to_sheet(exportData)
    
    // Add title and summary using ALL data
    const totalAllItems = allInventoryData.length
    const lowStockItemsAll = allInventoryData.filter((item: any) => 
      item.current_stock <= item.reorder_level
    ).length
    const outOfStockItemsAll = allInventoryData.filter((item: any) => 
      item.current_stock === 0
    ).length
    const totalStockValueAll = allInventoryData.reduce((sum: number, item: any) => 
      sum + ((item.current_stock || 0) * (item.average_cost || 0)), 0
    )
    
    const titleData = [
      ['LAPORAN INVENTORY MANAGEMENT'],
      ['Tanggal Export: ' + new Date().toLocaleDateString('id-ID')],
      ['Total Items: ' + totalAllItems],
      ['Total Nilai Stok: ' + formatCurrency(totalStockValueAll)],
      ['Items Low Stock: ' + lowStockItemsAll],
      ['Items Out of Stock: ' + outOfStockItemsAll],
      ['Filter Diterapkan: ' + (filters.value.search ? `Pencarian: "${filters.value.search}", ` : '') + 
       (filters.value.stock_status !== 'all' ? `Status: ${filters.value.stock_status}` : 'Semua Data')],
      [],
    ]
    
    XLSX.utils.sheet_add_aoa(worksheet, titleData, { origin: 'A1' })
    XLSX.utils.sheet_add_json(worksheet, exportData, { origin: 'A9' })
    
    // Set column widths
    const columnWidths = [
      { wch: 5 },   // No
      { wch: 30 },  // Produk/Variant
      { wch: 15 },  // SKU
      { wch: 30 },  // Deskripsi
      { wch: 8 },   // Unit
      { wch: 12 },  // Stok Saat Ini
      { wch: 12 },  // Stok Tersedia
      { wch: 12 },  // Stok Minimum
      { wch: 15 },  // Status
      { wch: 15 },  // Harga Rata-rata
      { wch: 15 },  // Nilai Stok
      { wch: 20 },  // Lokasi Penyimpanan
      { wch: 12 },  // Dibuat
      { wch: 12 },  // Diperbarui
    ]
    worksheet['!cols'] = columnWidths
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Inventory Report')
    
    // Generate filename with timestamp
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-')
    const filename = `inventory-report-${timestamp}.xlsx`
    
    // Download file
    XLSX.writeFile(workbook, filename)
    
    // Upload inventory data to server using ALL data
    try {
      await uploadInventoryData(allInventoryData, {
        total_items: totalAllItems,
        total_stock_value: totalStockValueAll,
        low_stock_count: lowStockItemsAll,
        out_of_stock_count: outOfStockItemsAll
      })
      successMessage.value = ``
    } catch (uploadError) {
      console.error('Upload error:', uploadError)
      successMessage.value = ''
    }
    
  } catch (error) {
    console.error('Export error:', error)
    errorMessage.value = 'Gagal mengexport laporan: ' + (error instanceof Error ? error.message : 'Unknown error')
  } finally {
    exportLoading.value = false
  }
}

// Upload inventory data to server
const uploadInventoryData = async (inventoryData?: any[], customSummary?: any) => {
  try {
    // Use provided data or fallback to current inventoryList
    const dataToUpload = inventoryData || inventoryList.value
    const summaryToUpload = customSummary || {
      total_items: totalItems.value,
      total_stock_value: totalStockValue.value || 0,
      low_stock_count: lowStockCount.value,
      out_of_stock_count: outOfStockCount.value
    }
    
    // Prepare comprehensive inventory data for upload
    const uploadData = {
      inventory_data: dataToUpload.map(item => ({
        id_inventory: item.id_inventory,
        id_product: item.id_product,
        id_item: item.id_item,
        current_stock: item.current_stock || 0,
        available_stock: item.available_stock || 0,
        reserved_stock: item.reserved_stock || 0,
        reorder_level: item.reorder_level || 0,
        max_stock_level: item.max_stock_level || null,
        average_cost: item.average_cost || 0,
        product_name: item.product?.name || item.item?.name || 'Unknown',
        product_sku: item.product?.sku || item.item?.item_code || null,
        unit: item.item?.unit || 'pcs',
        category: item.product?.category?.name || null,
        status: getStockStatusText(item),
        stock_value: (item.current_stock || 0) * (item.average_cost || 0),
        created_at: item.created_at,
        updated_at: item.updated_at
      })),
      summary: {
        total_items: summaryToUpload.total_items,
        total_stock_value: summaryToUpload.total_stock_value,
        low_stock_count: summaryToUpload.low_stock_count,
        out_of_stock_count: summaryToUpload.out_of_stock_count,
        export_timestamp: new Date().toISOString(),
        filters_applied: {
          search: filters.value.search || null,
          stock_status: filters.value.stock_status !== 'all' ? filters.value.stock_status : null
        }
      }
    }

    // Send data to API endpoint
    const accessToken = getAuthToken()
    const response = await fetch('/api/inventory/upload-data', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        // Add authorization header if needed
        ...(accessToken && { 'Authorization': `Bearer ${accessToken}` }),
      },
      body: JSON.stringify(uploadData)
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('Upload result:', result)
    
    return result
  } catch (error) {
    console.error('Upload inventory data error:', error)
    throw error
  }
}

// Print functionality
const printInventory = async () => {
  try {
    // Show loading state
    exportLoading.value = true
    
    // Fetch ALL inventory data for print
    const allInventoryData = await fetchAllInventoryForExport()
    
    const printContent = generatePrintContent(allInventoryData)
    const printWindow = window.open('', '_blank')
    
    if (printWindow) {
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
          <head>
            <title>Laporan Inventory - ${new Date().toLocaleDateString('id-ID')}</title>
            <meta charset="utf-8">
            <style>
              @media print {
                @page { 
                  margin: 1cm; 
                  size: A4 landscape;
                }
                body { 
                  font-family: Arial, sans-serif; 
                  font-size: 12px; 
                  margin: 0;
                  color: #000;
                }
              }
              body { 
                font-family: Arial, sans-serif; 
                margin: 20px;
                color: #000;
              }
              .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
              }
              .company-name {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 5px;
              }
              .report-title {
                font-size: 18px;
                color: #666;
                margin-bottom: 5px;
              }
              .report-date {
                font-size: 14px;
                color: #888;
              }
              .summary {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
                padding: 10px;
                background-color: #f5f5f5;
                border-radius: 5px;
              }
              table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
              }
              th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                font-size: 11px;
              }
              th {
                background-color: #f8f9fa;
                font-weight: bold;
                text-align: center;
              }
              .text-center { text-align: center; }
              .text-right { text-align: right; }
              .low-stock { background-color: #ffebee; }
              .out-of-stock { background-color: #ffcdd2; }
              .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 10px;
              }
            </style>
          </head>
          <body>
            ${printContent}
          </body>
        </html>
      `)
      
      printWindow.document.close()
      printWindow.focus()
      
      setTimeout(() => {
        printWindow.print()
        printWindow.close()
      }, 250)
    }
  } catch (error) {
    console.error('Print error:', error)
    errorMessage.value = 'Gagal memprint laporan: ' + (error instanceof Error ? error.message : 'Unknown error')
  } finally {
    exportLoading.value = false
  }
}

const generatePrintContent = (inventoryData?: any[]) => {
  const currentDate = new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
  
  // Use provided data or fallback to current inventoryList
  const dataToUse = inventoryData || inventoryList.value
  
  const totalProducts = dataToUse.length
  const lowStockItems = dataToUse.filter(item => 
    item.current_stock <= item.reorder_level
  ).length
  const outOfStockItems = dataToUse.filter(item => 
    item.current_stock === 0
  ).length
  
  let tableRows = ''
  dataToUse.forEach((item, index) => {
    const productName = item.product?.name || item.item?.name || '-'
    const sku = item.product?.sku || item.item?.item_code || '-'
    const category = item.product?.category?.name || '-'
    const currentStock = item.current_stock || 0
    const reorderLevel = item.reorder_level || 0
    const maxStock = item.max_stock_level || '-'
    const unit = item.item?.unit || 'pcs'
    const averageCost = item.average_cost ? formatCurrency(item.average_cost) : '-'
    
    const isLowStock = currentStock <= reorderLevel && currentStock > 0
    const isOutOfStock = currentStock === 0
    const rowClass = isOutOfStock ? 'out-of-stock' : (isLowStock ? 'low-stock' : '')
    
    tableRows += `
      <tr class="${rowClass}">
        <td class="text-center">${index + 1}</td>
        <td>${productName}<br><small style="color: #666;">${sku}</small></td>
        <td class="text-center">${category}</td>
        <td class="text-center">${currentStock}</td>
        <td class="text-center">${unit}</td>
        <td class="text-center">${reorderLevel}</td>
        <td class="text-center">${maxStock}</td>
        <td class="text-right">${averageCost}</td>
      </tr>
    `
  })
  
  return `
    <div class="header">
      <div class="company-name">SISTEM INVENTORY MANAGEMENT</div>
      <div class="report-title">Laporan Stok Inventory (Semua Data)</div>
      <div class="report-date">Dicetak pada: ${currentDate}</div>
    </div>
    
    <div class="summary">
      <div><strong>Total Produk:</strong> ${totalProducts}</div>
      <div><strong>Stok Menipis:</strong> ${lowStockItems}</div>
      <div><strong>Stok Habis:</strong> ${outOfStockItems}</div>
      <div><strong>Filter:</strong> ${filters.value.search ? `"${filters.value.search}"` : 'Semua Data'}</div>
    </div>
    
    <table>
      <thead>
        <tr>
          <th width="5%">No</th>
          <th width="25%">Nama Produk</th>
          <th width="15%">Kategori</th>
          <th width="10%">Stok</th>
          <th width="10%">Unit</th>
          <th width="10%">Min. Stok</th>
          <th width="10%">Max. Stok</th>
          <th width="15%">Harga Rata-rata</th>
        </tr>
      </thead>
      <tbody>
        ${tableRows}
      </tbody>
    </table>
    
    <div class="footer">
      <p>Laporan ini dicetak secara otomatis oleh sistem pada ${new Date().toLocaleString('id-ID')}</p>
      <p>Total ${totalProducts} item inventory | Filter: ${filters.value.search ? `Pencarian "${filters.value.search}"` : 'Semua Data'}</p>
      <p>© ${new Date().getFullYear()} Sistem Inventory Management</p>
    </div>
  `
}

// Upload history functions
const openUploadHistoryDialog = () => {
  uploadHistoryDialog.value = true
  fetchUploadHistory()
}

const fetchUploadHistory = async () => {
  try {
    uploadHistoryLoading.value = true
    
    const accessToken = getAuthToken()
    const response = await fetch(`/api/inventory/upload-history?page=${uploadHistoryPage.value}&per_page=${uploadHistoryPerPage.value}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(accessToken && { 'Authorization': `Bearer ${accessToken}` }),
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    
    if (result.success) {
      uploadHistory.value = result.data || []
      uploadHistoryTotal.value = result.pagination?.total || 0
    } else {
      throw new Error(result.message || 'Failed to fetch upload history')
    }
    
  } catch (error) {
    console.error('Fetch upload history error:', error)
    errorMessage.value = 'Gagal mengambil riwayat upload: ' + (error instanceof Error ? error.message : 'Unknown error')
  } finally {
    uploadHistoryLoading.value = false
  }
}

const onUploadHistoryPageChange = (page: number) => {
  uploadHistoryPage.value = page
  fetchUploadHistory()
}

const closeUploadHistoryDialog = () => {
  uploadHistoryDialog.value = false
  uploadHistory.value = []
  uploadHistoryPage.value = 1
}

// Function to test login and refresh token
const testLogin = async () => {
  try {
    console.log('🚀 Testing login to get fresh token...')
    
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        login: 'admin',
        password: 'password123'
      })
    })
    
    console.log('📊 Login Response Status:', response.status, response.statusText)
    
    const data = await response.json()
    console.log('📋 Login Response Data:', data)
    
    if (data.success && data.data.token) {
      const token = data.data.token
      console.log('✅ Login successful! Token:', token.substring(0, 30) + '...')
      
      // Save to localStorage
      localStorage.setItem('token', token)
      console.log('💾 Token saved to localStorage')
      
      // Also save to cookie
      const accessTokenCookie = useCookie('accessToken', {
        default: () => '',
        secure: true,
        sameSite: 'strict',
        maxAge: 60 * 60 * 24 * 7 // 7 days
      })
      accessTokenCookie.value = token
      console.log('🍪 Token saved to cookie')
      
      successMessage.value = 'Login berhasil! Token telah diperbarui. Silakan coba export lagi.'
      
      // Test API call with new token
      console.log('🌐 Testing API call with new token...')
      const testResponse = await fetch('/api/inventory?per_page=1', {
        headers: {
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json'
        }
      })
      
      console.log('📊 Test API Response Status:', testResponse.status, testResponse.statusText)
      
      if (testResponse.ok) {
        console.log('✅ New token works! You can now try the export function.')
      } else {
        console.log('❌ New token still doesn\'t work')
        errorMessage.value = 'Token baru tidak bekerja. Mungkin ada masalah dengan server.'
      }
      
    } else {
      console.log('❌ Login failed:', data.message)
      errorMessage.value = 'Login gagal: ' + (data.message || 'Unknown error')
    }
    
  } catch (error) {
    console.log('💥 Login error:', error)
    errorMessage.value = 'Error saat login: ' + (error instanceof Error ? error.message : 'Unknown error')
  }
}

// Debug watcher for totalItems
watch(totalItems, (newValue, oldValue) => {
}, { immediate: true })
</script>

<template>
  <div class="inventory-management">
    <!-- Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold inventory-title">
          Kelola Inventory
        </h1>
        <p class="text-body-1 text-medium-emphasis inventory-subtitle">
          Kelola stok barang dan pergerakan inventory
        </p>
      </div>
      <div class="d-flex gap-3 align-center">
        <!-- Debug Buttons for Authentication Testing -->
        <VBtn
          color="info"
          prepend-icon="tabler-bug"
          variant="outlined"
          size="small"
          @click="checkAuthStatus"
        >
          Check Auth
        </VBtn>
        
        <VBtn
          color="warning"
          prepend-icon="tabler-login"
          variant="outlined"
          size="small"
          @click="testLogin"
        >
          Test Login
        </VBtn>
        
        <VBtn
          color="primary"
          prepend-icon="tabler-refresh"
          variant="outlined"
          @click="refreshCurrentView"
        >
          Refresh Data
        </VBtn>
      </div>
    </div>

    <!-- Alert Messages -->
    <VAlert
      v-if="errorMessage"
      type="error"
      variant="outlined"
      class="mb-4"
      :text="errorMessage"
      closable
      @click:close="errorMessage = ''"
    />

    <VAlert
      v-if="successMessage"
      type="success"
      variant="outlined"
      class="mb-4"
      :text="successMessage"
      closable
      @click:close="successMessage = ''"
    />

    <VAlert
      v-if="stockMovementSuccessMessage"
      type="success"
      variant="outlined"
      class="mb-4"
      :text="stockMovementSuccessMessage"
      closable
      @click:close="stockMovementSuccessMessage = ''"
    />

    <!-- Statistics Cards -->
    <VRow class="mb-6">
      <VCol
        cols="12"
        md="3"
      >
        <VCard class="stats-card">
          <VCardText>
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-h4 font-weight-bold text-primary">
                  {{ currentStats.total_items || 0 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Total Produk
                </div>
              </div>
              <VIcon
                icon="tabler-package"
                size="48"
                class="text-primary opacity-50"
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="3"
      >
        <VCard class="stats-card">
          <VCardText>
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-h4 font-weight-bold text-warning">
                  {{ lowStockCount || 0 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Stok Menipis
                </div>
              </div>
              <VIcon
                icon="tabler-alert-triangle"
                size="48"
                class="text-warning opacity-50"
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="3"
      >
        <VCard class="stats-card">
          <VCardText>
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-h4 font-weight-bold text-error">
                  {{ outOfStockCount || 0 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Stok Habis
                </div>
              </div>
              <VIcon
                icon="tabler-x-circle"
                size="48"
                class="text-error opacity-50"
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="3"
      >
        <VCard class="stats-card">
          <VCardText>
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-h4 font-weight-bold text-success">
                  {{ formatCurrency(totalStockValue || 0) }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  Nilai Stok
                </div>
              </div>
              <VIcon
                icon="tabler-currency-dollar"
                size="48"
                class="text-success opacity-50"
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Stock Movement Info Card -->
    <VCard class="mb-6 info-card" color="info" variant="tonal">
      <VCardText>
        <div class="d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-3">
            <VIcon
              icon="tabler-info-circle"
              size="32"
              class="text-info"
            />
            <div>
              <div class="text-subtitle-1 font-weight-medium text-info">
                Sistem Stock Movement Otomatis
              </div>
              <div class="text-body-2 text-medium-emphasis">
                Stok akan bertambah otomatis saat purchasing dan berkurang saat penjualan POS.
                Gunakan tombol <strong>Penyesuaian</strong> untuk koreksi manual jika diperlukan.
              </div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <VChip color="success" variant="tonal" size="small">
              <VIcon icon="tabler-shopping-cart" class="me-1" />
              Purchase → Stok +
            </VChip>
            <VChip color="error" variant="tonal" size="small">
              <VIcon icon="tabler-cash" class="me-1" />
              POS Sale → Stok -
            </VChip>
          </div>
        </div>
      </VCardText>
    </VCard>

    <!-- Low Stock Alerts -->
    <VCard
      v-if="lowStockItems.length > 0"
      class="mb-6 low-stock-alert"
    >
          <VCardTitle class="d-flex align-center">
            <VIcon
              icon="tabler-alert-triangle"
              class="text-warning me-2"
            />
            Peringatan Stok Menipis ({{ lowStockItems.length }} produk)
          </VCardTitle>
          <VCardText>
            <div class="d-flex flex-wrap gap-2">
              <VChip
                v-for="item in lowStockItems.slice(0, 10)"
                :key="item.id_inventory"
                color="warning"
                variant="outlined"
                size="small"
              >
                {{ item.product?.name }}
                ({{ item.current_stock }}/{{ item.reorder_level }})
              </VChip>
              <VChip
                v-if="lowStockItems.length > 10"
                color="warning"
                variant="tonal"
                size="small"
              >
                +{{ lowStockItems.length - 10 }} lainnya
              </VChip>
            </div>
          </VCardText>
        </VCard>

        <!-- Info Card saat tidak ada data -->
        <VCard
          v-if="!loading && inventoryList.length === 0 && !errorMessage && !filters.search && filters.stock_status === 'all'"
          class="mb-6"
        >
          <VCardText class="text-center py-6">
            <VIcon
              icon="tabler-info-circle"
              size="48"
              class="text-info mb-3"
            />
            <div class="text-h6 mb-2">
              Sistem Inventory Management Siap Digunakan
            </div>
            <div class="text-body-2 text-medium-emphasis">
              Buat produk dan variant terlebih dahulu untuk mulai melacak inventory.
            </div>
          </VCardText>
        </VCard>

        <!-- Search and Filters Section - ALWAYS SHOW -->
        <VCard class="mb-6">
          <VCardText>
            <VRow>
              <VCol
                cols="12"
                md="4"
              >
                <VTextField
                  v-model="filters.search"
                  label="Cari inventory produk..."
                  placeholder="Nama produk, SKU, atau variant"
                  prepend-inner-icon="tabler-search"
                  clearable
                  variant="outlined"
                  @update:model-value="handleFiltersUpdate({ search: $event })"
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <VSelect
                  v-model="filters.stock_status"
                  :items="stockStatusOptions"
                  label="Status Stok"
                  variant="outlined"
                  clearable
                  @update:model-value="handleFiltersUpdate({ stock_status: $event || 'all' })"
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
                class="d-flex align-center gap-2"
              >
                <VBtn
                  variant="outlined"
                  prepend-icon="tabler-refresh"
                  @click="fetchInventoryList(); fetchStats(); fetchLowStockAlerts()"
                >
                  Refresh
                </VBtn>
                
                <VBtn
                  v-if="filters.search || filters.stock_status !== 'all'"
                  variant="outlined"
                  color="warning"
                  prepend-icon="tabler-filter-off"
                  @click="handleFiltersUpdate({ search: '', stock_status: 'all' })"
                >
                  Clear
                </VBtn>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Empty State Card (only when no data exists at all) -->
        <VCard
          v-if="!loading && inventoryList.length === 0 && !errorMessage && !filters.search && filters.stock_status === 'all'"
          class="mb-6"
        >
          <VCardText class="text-center py-6">
            <VIcon
              icon="tabler-package-off"
              size="48"
              class="text-disabled mb-3"
            />
            <div class="text-h6 mb-2">
              Sistem Inventory Management Siap Digunakan
            </div>
            <div class="text-body-2 text-medium-emphasis">
              Buat produk dan variant terlebih dahulu untuk mulai melacak inventory.
            </div>
          </VCardText>
        </VCard>

        <!-- Inventory Table -->
        <VCard class="products-inventory-table">
          <VCardTitle class="d-flex align-center gap-2 coffee-header">
            <VIcon
              icon="tabler-package"
              class="text-white"
            />
            <span class="text-white">Inventory Produk</span>
            <VSpacer />

            <!-- Export Excel Button -->
            <VBtn
              color="success"
              variant="elevated"
              prepend-icon="tabler-file-spreadsheet"
              :loading="exportLoading"
              @click="exportToExcel"
            >
              Export Semua Data
            </VBtn>
            
            <VChip
              color="black"
              size="small"
              variant="tonal"
            >
              {{ totalItems }} Produk
            </VChip>
          </VCardTitle>

          <VDivider />

          <!-- Wrapper div untuk horizontal scroll -->
          <div class="table-container">
            <VDataTableServer
            :headers="[
              { title: 'Produk/Variant', key: 'item_name', sortable: false, width: '280px' },
              { title: 'Stok Saat Ini', key: 'current_stock', sortable: false, width: '120px' },
              { title: 'Stok Tersedia', key: 'available_stock', sortable: false, width: '120px' },
              { title: 'Status', key: 'status', sortable: false, width: '120px' },
              { title: 'Reorder Level', key: 'reorder_level', sortable: false, width: '120px' },
              { title: 'Harga Rata-rata', key: 'average_cost', sortable: false, width: '150px' },
              { title: 'Nilai Stok', key: 'stock_value', sortable: false, width: '150px' },
              { title: 'Aksi', key: 'actions', sortable: false, width: '200px', fixed: true },
            ]"
            :items="inventoryList"
            :loading="loading"
            :items-length="totalItems"
            :items-per-page="itemsPerPage"
            :page="currentPage"
            :items-per-page-options="[10, 15, 25, 50, 100]"
            :items-per-page-text="'Items per page:'"
            :page-text="'{0}-{1} of {2}'"
            :no-data-text="'Tidak ada data inventory'"
            class="text-no-wrap fixed-actions-table"
            id="inventory-table"
            fixed-header
            height="500px"
            @update:page="onPageChange"
            @update:items-per-page="onItemsPerPageChange"
          >
            <!-- Item Name with SKU -->
            <template #item.item_name="{ item }">
              <div class="d-flex align-center">
                <div>
                  <div class="font-weight-medium">
                    {{ item.item?.name || item.product?.name || 'Unknown Item' }}
                  </div>
                  <div class="d-flex flex-column">
                    <small class="text-primary font-weight-medium">
                      SKU: {{ item.item?.item_code || item.product?.sku || '-' }}
                    </small>
                    <small class="text-medium-emphasis">
                      {{ item.item?.description || item.product?.category?.name || '-' }}
                    </small>
                  </div>
                </div>
              </div>
            </template>

            <!-- SKU -->
            <template #item.sku="{ item }">
              <VChip
                variant="outlined"
                size="small"
                color="primary"
              >
                {{ item.item?.item_code || item.product?.sku || 'N/A' }}
              </VChip>
            </template>

            <!-- Current Stock -->
            <template #item.current_stock="{ item }">
              <div class="text-center">
                <div class="text-h6 font-weight-bold">
                  {{ item.current_stock }}
                </div>
                <small
                  v-if="item.reserved_stock > 0"
                  class="text-warning"
                >
                  ({{ item.reserved_stock }} reserved)
                </small>
              </div>
            </template>

            <!-- Available Stock -->
            <template #item.available_stock="{ item }">
              <div class="text-center">
                <VChip
                  :color="item.available_stock > 0 ? 'success' : 'error'"
                  variant="tonal"
                  size="small"
                >
                  {{ item.available_stock }}
                </VChip>
              </div>
            </template>

            <!-- Status -->
            <template #item.status="{ item }">
              <VChip
                :color="getStockStatusColor(item)"
                variant="tonal"
                size="small"
              >
                {{ getStockStatusText(item) }}
              </VChip>
            </template>

            <!-- Reorder Level -->
            <template #item.reorder_level="{ item }">
              <div class="text-center">
                {{ item.reorder_level || '-' }}
              </div>
            </template>

            <!-- Average Cost -->
            <template #item.average_cost="{ item }">
              <div class="text-end">
                {{ formatCurrency(item.average_cost) }}
              </div>
            </template>

            <!-- Stock Value -->
            <template #item.stock_value="{ item }">
              <div class="text-end font-weight-medium text-success">
                {{ formatCurrency(item.current_stock * item.average_cost) }}
              </div>
            </template>

            <!-- Actions -->
            <template #item.actions="{ item }">
              <div class="d-flex align-center gap-1">
                <!-- Adjustment -->
                <VTooltip text="Penyesuaian Stok">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="tabler-adjustments"
                      color="warning"
                      variant="text"
                      size="small"
                      :disabled="!item.item"
                      @click="handleAdjustment(item)"
                    />
                  </template>
                </VTooltip>

                <VTooltip text="Update Stok">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="tabler-edit"
                      color="primary"
                      variant="text"
                      size="small"
                      @click="openStockUpdateDialog(item)"
                    />
                  </template>
                </VTooltip>

                <VTooltip text="Set Reorder Level">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="tabler-settings"
                      color="info"
                      variant="text"
                      size="small"
                      @click="openReorderDialog(item)"
                    />
                  </template>
                </VTooltip>

                <VTooltip text="Lihat Riwayat">
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      icon="tabler-history"
                      color="secondary"
                      variant="text"
                      size="small"
                      @click="openMovementsDialog(item)"
                    />
                  </template>
                </VTooltip>
              </div>
            </template>

            <!-- No Data -->
            <template #no-data>
              <div class="text-center py-12">
                <VIcon
                  :icon="filters.search || filters.stock_status !== 'all' ? 'tabler-search-off' : 'tabler-package-off'"
                  size="64"
                  class="text-medium-emphasis mb-4"
                />
                <div class="text-h5 text-medium-emphasis mb-2">
                  {{ filters.search || filters.stock_status !== 'all' ? 'Tidak Ada Hasil Pencarian' : 'Belum Ada Data Inventory' }}
                </div>
                <div class="text-body-1 text-medium-emphasis mb-4">
                  <span v-if="filters.search || filters.stock_status !== 'all'">
                    <span v-if="filters.search">
                      Tidak ditemukan inventory dengan kata kunci "<strong>{{ filters.search }}</strong>"
                    </span>
                    <span v-if="filters.search && filters.stock_status !== 'all'"> dan </span>
                    <span v-if="filters.stock_status !== 'all'">
                      status stok "<strong>{{ stockStatusOptions.find(opt => opt.value === filters.stock_status)?.title || filters.stock_status }}</strong>"
                    </span>
                    <br>Coba kata kunci yang berbeda atau hapus filter.
                  </span>
                  <span v-else>
                    Data inventory akan muncul setelah produk dan variant dibuat.<br>
                    Silakan buat produk terlebih dahulu di menu <strong>Kelola Produk</strong>.
                  </span>
                </div>
                <div class="d-flex justify-center gap-3">
                  <VBtn
                    v-if="filters.search || filters.stock_status !== 'all'"
                    color="warning"
                    variant="outlined"
                    prepend-icon="tabler-filter-off"
                    @click="handleFiltersUpdate({ search: '', stock_status: 'all' })"
                  >
                    Hapus Filter
                  </VBtn>
                  <VBtn
                    v-else
                    color="primary"
                    prepend-icon="tabler-plus"
                    to="/products-management"
                  >
                    Buat Produk Baru
                  </VBtn>
                  <VBtn
                    variant="outlined"
                    prepend-icon="tabler-refresh"
                    @click="fetchInventoryList(); fetchStats(); fetchLowStockAlerts()"
                  >
                    Refresh Data
                  </VBtn>
                </div>
              </div>
            </template>

            <!-- Loading -->
            <template #loading>
              <div class="text-center py-8">
                <VProgressCircular
                  indeterminate
                  color="primary"
                  size="40"
                />
                <div class="mt-4 text-medium-emphasis">
                  Memuat data inventory...
                </div>
              </div>
            </template>
          </VDataTableServer>
          </div> <!-- End table-container -->
          
          <!-- Total Data Info -->
        </VCard>

        <!-- Stock Update Dialog -->
        <VDialog
          v-model="stockUpdateDialog"
          max-width="800px"
          :fullscreen="xs"
          persistent
          class="stock-dialog"
        >
          <VCard>
            <VCardTitle>
              Update Stok - {{ selectedInventory?.product?.name }}
            </VCardTitle>
            <VDivider />
            <VForm @submit.prevent="updateStock">
              <VCardText>
                <VAlert
                  v-if="modalErrorMessage"
                  type="error"
                  variant="tonal"
                  closable
                  class="mb-4"
                  @click:close="clearModalError"
                >
                  {{ modalErrorMessage }}
                </VAlert>

                <VRow>
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VSelect
                      v-model="stockUpdateForm.movement_type"
                      label="Jenis Pergerakan *"
                      :items="[
                        { title: 'Stock Masuk', value: 'stock_in' },
                        { title: 'Stock Keluar', value: 'stock_out' },
                        { title: 'Penyesuaian', value: 'adjustment' },
                        { title: 'Transfer', value: 'transfer' },
                        { title: 'Retur', value: 'return' },
                      ]"
                      variant="outlined"
                    />
                  </VCol>
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model.number="stockUpdateForm.quantity"
                      label="Kuantitas *"
                      type="number"
                      min="1"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VRow>
                  <VCol cols="12">
                    <VTextField
                      v-model="stockUpdateForm.reason"
                      label="Alasan *"
                      placeholder="Contoh: Pembelian dari supplier, Penjualan, dll"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VRow>
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model="stockUpdateForm.reference_number"
                      label="Nomor Referensi"
                      placeholder="PO-001, SO-001, dll"
                      variant="outlined"
                    />
                  </VCol>
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <VTextField
                      v-model.number="stockUpdateForm.cost_per_unit"
                      label="Harga per Unit"
                      type="number"
                      min="0"
                      step="0.01"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VRow>
                  <VCol cols="12">
                    <VTextarea
                      v-model="stockUpdateForm.notes"
                      label="Catatan"
                      rows="2"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>
              </VCardText>

              <VDivider />
              <VCardActions class="pa-4">
                <VSpacer />
                <VBtn
                  variant="outlined"
                  @click="closeStockUpdateDialog"
                >
                  Batal
                </VBtn>
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="saveLoading"
                >
                  Update Stok
                </VBtn>
              </VCardActions>
            </VForm>
          </VCard>
        </VDialog>

        <!-- Set Reorder Level Dialog -->
        <VDialog
          v-model="reorderDialog"
          max-width="600px"
          :fullscreen="xs"
          persistent
          class="stock-dialog"
        >
          <VCard>
            <VCardTitle>
              Set Reorder Level - {{ selectedInventory?.product?.name }}
            </VCardTitle>
            <VDivider />
            <VForm @submit.prevent="setReorderLevel">
              <VCardText>
                <VAlert
                  v-if="modalErrorMessage"
                  type="error"
                  variant="tonal"
                  closable
                  class="mb-4"
                  @click:close="clearModalError"
                >
                  {{ modalErrorMessage }}
                </VAlert>

                <VRow>
                  <VCol cols="12">
                    <VTextField
                      v-model.number="reorderForm.reorder_level"
                      label="Reorder Level *"
                      type="number"
                      min="0"
                      hint="Stok minimum sebelum perlu restock"
                      persistent-hint
                      variant="outlined"
                    />
                  </VCol>
                </VRow>

                <VRow>
                  <VCol cols="12">
                    <VTextField
                      v-model.number="reorderForm.max_stock_level"
                      label="Max Stock Level"
                      type="number"
                      min="0"
                      hint="Stok maksimum yang diizinkan"
                      persistent-hint
                      variant="outlined"
                    />
                  </VCol>
                </VRow>
              </VCardText>

              <VDivider />
              <VCardActions class="pa-4">
                <VSpacer />
                <VBtn
                  variant="outlined"
                  @click="closeReorderDialog"
                >
                  Batal
                </VBtn>
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="saveLoading"
                >
                  Simpan
                </VBtn>
              </VCardActions>
            </VForm>
          </VCard>
        </VDialog>

    <!-- Movements History Dialog -->
    <!-- Movements History Dialog -->
    <InventoryMovementsDialog
      v-model="movementsDialog"
      :inventory-id="selectedInventory?.id_inventory?.toString()"
      :inventory-name="selectedInventory?.item?.name"
    />

    <!-- Stock Adjustment Dialog -->
    <VDialog
      v-model="adjustmentDialog"
      max-width="600px"
      :fullscreen="xs"
      persistent
      class="stock-dialog"
    >
      <VCard class="stock-movement-dialog coffee-dialog">
        <VCardTitle class="d-flex align-center justify-space-between coffee-header">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-adjustments"
              class="text-white"
            />
            <span class="text-white">
              Penyesuaian Stok
            </span>
          </div>
          <VBtn
            icon="tabler-x"
            variant="text"
            color="white"
            @click="closeMovementDialog"
          />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <VForm @submit.prevent="recordStockMovement">
            <!-- Item Info -->
            <VAlert
              v-if="selectedItem"
              color="info"
              variant="tonal"
              class="mb-4"
            >
              <div class="d-flex align-center gap-3">
                <VIcon icon="tabler-package" />
                <div>
                  <div class="font-weight-bold">{{ selectedItem.name }}</div>
                  <div class="text-caption">{{ selectedItem.item_code }}</div>
                  <div class="text-caption">Stok Saat Ini: {{ selectedItem.current_stock }} {{ selectedItem.unit }}</div>
                </div>
              </div>
            </VAlert>

            <!-- Error Alert -->
            <VAlert
              v-if="stockMovementErrorMessage"
              type="error"
              variant="outlined"
              class="mb-4"
              :text="stockMovementErrorMessage"
              closable
              @click:close="clearStockMovementError"
            />

            <!-- Quantity Input -->
            <VTextField
              v-model.number="movementFormData.quantity"
              label="Stok Baru"
              type="number"
              min="0"
              step="0.001"
              variant="outlined"
              :suffix="selectedItem?.unit"
              class="mb-4"
              required
            />

            <!-- Notes -->
            <VTextarea
              v-model="movementFormData.notes"
              label="Catatan (opsional)"
              variant="outlined"
              rows="3"
              class="mb-4"
            />

            <!-- Actions -->
            <div class="d-flex gap-3 justify-end">
              <VBtn
                variant="outlined"
                @click="closeMovementDialog"
              >
                Batal
              </VBtn>
              <VBtn
                type="submit"
                color="primary"
                class="coffee-primary"
                :loading="movementSaveLoading"
              >
                Simpan Penyesuaian
              </VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 
    Stock Movement Dialog - Commented out as stock movements are now automatic
    from purchasing and POS transactions. Only adjustment dialog is kept.
    
    <VDialog
      v-model="movementDialog"
      max-width="800px"
      :fullscreen="xs"
      persistent
      class="stock-dialog"
    >
      <VCard class="stock-movement-dialog coffee-dialog">
        <VCardTitle class="d-flex align-center justify-space-between coffee-header">
          <div class="d-flex align-center gap-2">
            <VIcon
              :icon="movementFormData.type === 'in' ? 'tabler-plus' : 'tabler-minus'"
              class="text-white"
            />
            <span class="text-white">
              {{
                movementFormData.type === 'in' ? 'Stok Masuk'
                : movementFormData.type === 'out' ? 'Stok Keluar'
                  : 'Penyesuaian Stok'
              }}
            </span>
          </div>
          <VBtn
            icon="tabler-x"
            variant="text"
            color="white"
            @click="closeMovementDialog"
          />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <VForm @submit.prevent="recordStockMovement">
            <VAlert
              v-if="selectedItem"
              color="info"
              variant="tonal"
              class="mb-4"
            >
              <div class="d-flex align-center justify-space-between">
                <div>
                  <div class="font-weight-medium">{{ selectedItem.name }}</div>
                  <div class="text-caption">Stok Saat Ini: {{ selectedItem.current_stock }} {{ selectedItem.unit }}</div>
                </div>
                <div class="text-right">
                  <div class="text-caption text-medium-emphasis">SKU</div>
                  <div class="font-weight-medium">{{ selectedItem.item_code }}</div>
                </div>
              </div>
            </VAlert>

            <VAlert
              v-if="modalErrorMessage"
              type="error"
              variant="outlined"
              class="mb-4"
              :text="modalErrorMessage"
              closable
              @click:close="clearModalError"
            />

            <VRow>
              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="movementFormData.quantity"
                  :label="movementFormData.type === 'adjustment' ? 'Stok Baru' : 'Jumlah'"
                  type="number"
                  :step="movementFormData.type === 'adjustment' ? '1' : '0.001'"
                  min="0"
                  variant="outlined"
                  :suffix="selectedItem?.unit || 'pcs'"
                  required
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="movementFormData.cost_per_unit"
                  label="Harga per Unit"
                  type="number"
                  step="0.01"
                  min="0"
                  variant="outlined"
                  prefix="Rp"
                  :readonly="movementFormData.type !== 'in'"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="movementFormData.notes"
                  label="Catatan"
                  placeholder="Masukkan catatan untuk pergerakan stok..."
                  rows="3"
                  variant="outlined"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-6">
          <VSpacer />
          <VBtn
            variant="outlined"
            class="coffee-secondary"
            @click="closeMovementDialog"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            class="coffee-primary"
            :loading="movementSaveLoading"
            @click="recordStockMovement"
          >
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
    -->    <!-- Stock Movement Dialog -->
    <VDialog
      v-model="movementDialog"
      max-width="800px"
      :fullscreen="xs"
      persistent
      class="stock-dialog"
    >
      <VCard class="stock-movement-dialog coffee-dialog">
        <VCardTitle class="d-flex align-center justify-space-between coffee-header">
          <div class="d-flex align-center gap-2">
            <VIcon
              :icon="movementFormData.type === 'in' ? 'tabler-plus' : 'tabler-minus'"
              class="text-white"
            />
            <span class="text-white">
              {{
                movementFormData.type === 'in' ? 'Stok Masuk'
                : movementFormData.type === 'out' ? 'Stok Keluar'
                  : 'Penyesuaian Stok'
              }}
            </span>
          </div>
          <VBtn
            icon="tabler-x"
            variant="text"
            color="white"
            @click="closeMovementDialog"
          />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <VForm @submit.prevent="recordStockMovement">
            <!-- Item Info -->
            <VAlert
              v-if="selectedItem"
              color="info"
              variant="tonal"
              class="mb-4"
            >
              <div class="d-flex align-center gap-3">
                <VAvatar
                  color="primary"
                  size="40"
                  variant="tonal"
                >
                  <VIcon icon="tabler-package" />
                </VAvatar>
                <div>
                  <div class="font-weight-bold">
                    {{ selectedItem.name }}
                  </div>
                  <div class="text-caption">
                    Stok saat ini: {{ selectedItem.current_stock }} {{ selectedItem.unit }}
                  </div>
                </div>
              </div>
            </VAlert>

            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model.number="movementFormData.quantity"
                  :label="`Jumlah ${movementFormData.type === 'in' ? 'Masuk' : movementFormData.type === 'out' ? 'Keluar' : 'Akhir'}`"
                  type="number"
                  variant="outlined"
                  required
                  :min="movementFormData.type === 'adjustment' ? 0 : 1"
                  :rules="[v => v > 0 || 'Jumlah harus lebih dari 0']"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model.number="movementFormData.cost_per_unit"
                  label="Harga per Unit"
                  type="number"
                  variant="outlined"
                  prefix="Rp"
                  :readonly="movementFormData.type !== 'in'"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="movementFormData.notes"
                  label="Catatan"
                  placeholder="Masukkan catatan untuk pergerakan stok..."
                  rows="3"
                  variant="outlined"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-6">
          <VSpacer />
          <VBtn
            variant="outlined"
            class="coffee-secondary"
            @click="closeMovementDialog"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            class="coffee-primary"
            :loading="movementSaveLoading"
            @click="recordStockMovement"
          >
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
    
    <!-- Upload History Dialog -->
    <VDialog
      v-model="uploadHistoryDialog"
      max-width="1200px"
      :fullscreen="xs"
      persistent
      class="upload-history-dialog"
    >
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between coffee-header">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-history"
              class="text-white"
            />
            <span class="text-white">Riwayat Upload Data Inventory</span>
          </div>
          <VBtn
            icon="tabler-x"
            variant="text"
            color="white"
            @click="closeUploadHistoryDialog"
          />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-6">
          <!-- Loading State -->
          <div
            v-if="uploadHistoryLoading"
            class="text-center py-8"
          >
            <VProgressCircular
              indeterminate
              color="primary"
              size="40"
            />
            <div class="mt-4 text-medium-emphasis">
              Memuat riwayat upload...
            </div>
          </div>

          <!-- History Table -->
          <div v-else>
            <VDataTable
              :headers="[
                { title: 'Tanggal Upload', key: 'created_at', sortable: false },
                { title: 'User', key: 'user_name', sortable: false },
                { title: 'Total Items', key: 'total_items_processed', sortable: false },
                { title: 'Berhasil Update', key: 'total_items_updated', sortable: false },
                { title: 'Dilewati', key: 'total_items_skipped', sortable: false },
                { title: 'Total Nilai Stok', key: 'total_stock_value', sortable: false },
                { title: 'Status', key: 'upload_status', sortable: false },
              ]"
              :items="uploadHistory"
              :items-per-page="uploadHistoryPerPage"
              class="elevation-1"
            >
              <!-- Date Column -->
              <template #item.created_at="{ item }">
                <div class="text-body-2">
                  {{ new Date(item.created_at).toLocaleDateString('id-ID', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  }) }}
                </div>
              </template>

              <!-- User Column -->
              <template #item.user_name="{ item }">
                <div class="d-flex align-center gap-2">
                  <VAvatar
                    size="32"
                    color="primary"
                    variant="tonal"
                  >
                    <VIcon icon="tabler-user" />
                  </VAvatar>
                  <div>
                    <div class="text-body-2 font-weight-medium">
                      {{ item.user_name || 'Unknown User' }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ item.user_email || '-' }}
                    </div>
                  </div>
                </div>
              </template>

              <!-- Total Items Column -->
              <template #item.total_items_processed="{ item }">
                <VChip
                  color="info"
                  variant="tonal"
                  size="small"
                >
                  {{ item.total_items_processed }}
                </VChip>
              </template>

              <!-- Updated Items Column -->
              <template #item.total_items_updated="{ item }">
                <VChip
                  color="success"
                  variant="tonal"
                  size="small"
                >
                  {{ item.total_items_updated }}
                </VChip>
              </template>

              <!-- Skipped Items Column -->
              <template #item.total_items_skipped="{ item }">
                <VChip
                  :color="item.total_items_skipped > 0 ? 'warning' : 'success'"
                  variant="tonal"
                  size="small"
                >
                  {{ item.total_items_skipped }}
                </VChip>
              </template>

              <!-- Stock Value Column -->
              <template #item.total_stock_value="{ item }">
                <div class="text-end font-weight-medium text-success">
                  {{ formatCurrency(item.total_stock_value || 0) }}
                </div>
              </template>

              <!-- Status Column -->
              <template #item.upload_status="{ item }">
                <VChip
                  :color="
                    item.upload_status === 'success' ? 'success'
                    : item.upload_status === 'failed' ? 'error'
                    : 'warning'
                  "
                  variant="tonal"
                  size="small"
                >
                  {{
                    item.upload_status === 'success' ? 'Berhasil'
                    : item.upload_status === 'failed' ? 'Gagal'
                    : 'Sebagian'
                  }}
                </VChip>
              </template>

              <!-- No Data -->
              <template #no-data>
                <div class="text-center py-8">
                  <VIcon
                    icon="tabler-database-off"
                    size="64"
                    class="text-medium-emphasis mb-4"
                  />
                  <div class="text-h6 text-medium-emphasis mb-2">
                    Belum Ada Riwayat Upload
                  </div>
                  <div class="text-body-2 text-medium-emphasis">
                    Riwayat upload akan muncul setelah Anda melakukan export data inventory.
                  </div>
                </div>
              </template>
            </VDataTable>

            <!-- Pagination -->
            <div
              v-if="uploadHistoryTotal > uploadHistoryPerPage"
              class="d-flex justify-center mt-4"
            >
              <VPagination
                v-model="uploadHistoryPage"
                :length="Math.ceil(uploadHistoryTotal / uploadHistoryPerPage)"
                :total-visible="7"
                @update:model-value="onUploadHistoryPageChange"
              />
            </div>
          </div>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn
            variant="outlined"
            @click="closeUploadHistoryDialog"
          >
            Tutup
          </VBtn>
          <VBtn
            color="primary"
            prepend-icon="tabler-refresh"
            @click="fetchUploadHistory"
          >
            Refresh
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped lang="scss">
.inventory-management {
  .inventory-title {
    color: rgb(var(--v-theme-primary));
    font-family: Inter, sans-serif;
  }

  .inventory-subtitle {
    margin-block-start: 4px;
    opacity: 0.8;
  }

  .stats-card {
    border-inline-start: 4px solid rgb(var(--v-theme-primary));
    transition: transform 0.2s ease;

    &:hover {
      transform: translateY(-2px);
    }
  }

  .low-stock-alert {
    background: rgb(var(--v-theme-warning) / 5%);
    border-inline-start: 4px solid rgb(var(--v-theme-warning));
  }

  // Coffee Dialog Theme
  .coffee-dialog {
    .coffee-header {
      background: linear-gradient(135deg, #b07124 0%, #8d7053 100%);
      color: white;
    }

    .coffee-primary {
      border: none;
      background: linear-gradient(135deg, #b07124 0%, #8d7053 100%);
      color: white;

      &:hover {
        background: linear-gradient(135deg, #9a5e1f 0%, #7a5d47 100%);
        box-shadow: 0 4px 12px rgba(176, 113, 36, 40%);
      }
    }

    .coffee-secondary {
      border-color: #b07124;
      color: #b07124;

      &:hover {
        background-color: rgba(176, 113, 36, 10%);
      }
    }
  }

  // Stock Dialog styling
  .stock-dialog {
    .v-dialog {
      &.v-dialog--fullscreen {
        .v-card {
          display: flex;
          flex-direction: column;
          block-size: 100vh;

          .v-card-text {
            flex: 1;
            overflow-y: auto;
          }
        }
      }
    }
  }

  // Tabs styling
  .tabs-container {
    .v-tab {
      font-weight: 500;
      letter-spacing: normal;
      text-transform: none !important;

      &--selected {
        color: rgb(var(--v-theme-primary)) !important;
      }

      .v-chip {
        transition: all 0.2s ease;
      }
    }

    .v-tabs-slider {
      background-color: rgb(var(--v-theme-primary));
    }
  }

  // Distinguish between products and items tables
  .products-inventory-table {
    .coffee-header {
      background: linear-gradient(135deg, #b07124 0%, #8d7053 100%);
    }

    .products-table {
      .v-data-table__td {
        border-block-end: 1px solid rgba(176, 113, 36, 10%);
      }
    }
  }

  // Table container for horizontal scroll
  .table-container {
    overflow-x: auto;
    width: 100%;
    position: relative;
    max-width: 100%;
    
    // Ensure scrollbar appears when needed
    &::-webkit-scrollbar {
      height: 8px;
    }
    
    &::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
    }
    
    &::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 4px;
    }
    
    &::-webkit-scrollbar-thumb:hover {
      background: #555;
    }
  }

  // Fixed actions table styling
  #inventory-table {
    :deep(.v-data-table) {
      .v-data-table__wrapper {
        overflow-x: auto !important;
        position: relative;
      }

      table {
        min-width: 1300px !important; // Adjusted for one less column
      }

      // Target action column specifically (8th column = Aksi, since SKU column removed)
      thead tr th:nth-child(8),
      tbody tr td:nth-child(8) {
        position: sticky !important;
        right: 0px !important;
        background: rgb(var(--v-theme-surface)) !important;
        border-left: 3px solid #e0e0e0 !important;
        z-index: 100 !important;
        box-shadow: -8px 0 16px rgba(0, 0, 0, 0.25) !important;
        min-width: 200px !important;
        max-width: 200px !important;
        width: 200px !important;
      }

      // Header styling for action column
      thead tr th:nth-child(8) {
        background: rgb(var(--v-theme-surface-variant)) !important;
        z-index: 101 !important;
        font-weight: 700 !important;
        text-align: center !important;
      }

      // Data cell styling for action column
      tbody tr td:nth-child(8) {
        text-align: center !important;
        padding: 8px !important;
      }

      // Ensure other columns have proper width to trigger scroll
      thead tr th:not(:nth-child(8)),
      tbody tr td:not(:nth-child(8)) {
        min-width: 150px;
        white-space: nowrap;
      }

      // First column (Product name + SKU) wider
      thead tr th:first-child,
      tbody tr td:first-child {
        min-width: 280px;
      }
    }
  }
}
</style>
