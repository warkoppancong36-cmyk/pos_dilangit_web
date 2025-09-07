import axios from 'axios'
import { ref, computed } from 'vue'

const API_BASE_URL = '/api/bluetooth-devices'

export interface BluetoothDevice {
    id_bluetooth_device: number
    device_name: string
    device_address: string
    device_type: 'printer' | 'scanner' | 'cash_drawer' | 'scale' | 'other'
    device_type_label?: string
    manufacturer?: string
    model?: string
    device_capabilities?: any[]
    connection_settings?: any
    is_default: boolean
    is_active: boolean
    last_connected_at?: string
    notes?: string
    connection_status?: string
    created_at?: string
    updated_at?: string
}

export interface BluetoothDeviceFormData {
    device_name: string
    device_address: string
    device_type: string
    manufacturer: string
    model: string
    is_default: boolean
    is_active: boolean
    notes: string
}

export const useBluetoothDevices = () => {
    // State
    const devices = ref<BluetoothDevice[]>([])
    const loading = ref(false)
    const saveLoading = ref(false)
    const deleteLoading = ref<number | null>(null)
    const toggleLoading = ref<number | null>(null)
    const testLoading = ref<number | null>(null)

    // Dialog states
    const dialog = ref(false)
    const deleteDialog = ref(false)
    const editMode = ref(false)
    const selectedDevice = ref<BluetoothDevice | null>(null)

    // Form data
    const formData = ref<BluetoothDeviceFormData>({
        device_name: '',
        device_address: '',
        device_type: '',
        manufacturer: '',
        model: '',
        is_default: false,
        is_active: true,
        notes: ''
    })

    // Filters
    const search = ref('')
    const typeFilter = ref('')
    const activeOnly = ref(false)
    const perPage = ref(10)
    const viewMode = ref('grid')
    const currentPage = ref(1)

    // Pagination
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0
    })

    // Messages
    const successMessage = ref('')
    const errorMessage = ref('')
    const modalErrorMessage = ref('')

    // Computed
    const totalPages = computed(() => pagination.value.last_page)
    const totalDevices = computed(() => devices.value.length)
    const activeDevices = computed(() => devices.value.filter(d => d.is_active).length)
    const inactiveDevices = computed(() => devices.value.filter(d => !d.is_active).length)
    const printerDevices = computed(() => devices.value.filter(d => d.device_type === 'printer').length)
    const otherDevices = computed(() => devices.value.filter(d => d.device_type !== 'printer').length)

    // Device type options
    const deviceTypes = [
        { value: 'printer', title: 'Printer', icon: 'mdi-printer' },
        { value: 'scanner', title: 'Scanner', icon: 'mdi-qrcode-scan' },
        { value: 'cash_drawer', title: 'Cash Drawer', icon: 'mdi-cash-register' },
        { value: 'scale', title: 'Scale', icon: 'mdi-scale-bathroom' },
        { value: 'other', title: 'Other', icon: 'mdi-devices' }
    ]

    // Permissions
    const canCreateEdit = computed(() => true) // Replace with actual permission check

    // Methods
    const fetchDevices = async () => {
        loading.value = true
        try {
            const params: any = {}
            if (typeFilter.value) params.device_type = typeFilter.value
            if (activeOnly.value) params.active_only = true

            const response = await axios.get(API_BASE_URL, { params })
            devices.value = response.data.data || []

            successMessage.value = ''
            errorMessage.value = ''
        } catch (error: any) {
            console.error('Failed to fetch devices:', error)
            errorMessage.value = error.response?.data?.message || 'Gagal memuat data perangkat bluetooth'
        } finally {
            loading.value = false
        }
    }

    const saveDevice = async () => {
        if (!validateForm()) return

        saveLoading.value = true
        modalErrorMessage.value = ''

        try {
            const payload = { ...formData.value }

            if (editMode.value && selectedDevice.value) {
                await axios.put(`${API_BASE_URL}/${selectedDevice.value.id_bluetooth_device}`, payload)
                successMessage.value = 'Perangkat bluetooth berhasil diperbarui'
            } else {
                await axios.post(API_BASE_URL, payload)
                successMessage.value = 'Perangkat bluetooth berhasil ditambahkan'
            }

            await fetchDevices()
            closeDialog()
        } catch (error: any) {
            console.error('Failed to save device:', error)
            modalErrorMessage.value = error.response?.data?.message || 'Gagal menyimpan perangkat bluetooth'
        } finally {
            saveLoading.value = false
        }
    }

    const deleteDevice = async (device: BluetoothDevice) => {
        deleteLoading.value = device.id_bluetooth_device
        try {
            await axios.delete(`${API_BASE_URL}/${device.id_bluetooth_device}`)
            successMessage.value = 'Perangkat bluetooth berhasil dihapus'
            await fetchDevices()
        } catch (error: any) {
            console.error('Failed to delete device:', error)
            errorMessage.value = error.response?.data?.message || 'Gagal menghapus perangkat bluetooth'
        } finally {
            deleteLoading.value = null
            deleteDialog.value = false
        }
    }

    const confirmDelete = (device: BluetoothDevice) => {
        selectedDevice.value = device
        deleteDialog.value = true
    }

    const toggleActiveStatus = async (device: BluetoothDevice) => {
        toggleLoading.value = device.id_bluetooth_device
        try {
            await axios.put(`${API_BASE_URL}/${device.id_bluetooth_device}`, {
                is_active: !device.is_active
            })
            successMessage.value = `Perangkat bluetooth berhasil ${device.is_active ? 'dinonaktifkan' : 'diaktifkan'}`
            await fetchDevices()
        } catch (error: any) {
            console.error('Failed to toggle device status:', error)
            errorMessage.value = error.response?.data?.message || 'Gagal mengubah status perangkat bluetooth'
        } finally {
            toggleLoading.value = null
        }
    }

    const setAsDefault = async (device: BluetoothDevice) => {
        try {
            await axios.post(`${API_BASE_URL}/${device.id_bluetooth_device}/set-default`)
            successMessage.value = 'Perangkat berhasil dijadikan default'
            await fetchDevices()
        } catch (error: any) {
            console.error('Failed to set default device:', error)
            errorMessage.value = error.response?.data?.message || 'Gagal menjadikan perangkat default'
        }
    }

    const testConnection = async (device: BluetoothDevice) => {
        testLoading.value = device.id_bluetooth_device
        try {
            const response = await axios.post(`${API_BASE_URL}/${device.id_bluetooth_device}/test-connection`)
            successMessage.value = response.data.message || 'Koneksi berhasil ditest'
        } catch (error: any) {
            console.error('Failed to test connection:', error)
            errorMessage.value = error.response?.data?.message || 'Gagal test koneksi perangkat'
        } finally {
            testLoading.value = null
        }
    }

    const openCreateDialog = () => {
        editMode.value = false
        selectedDevice.value = null
        formData.value = {
            device_name: '',
            device_address: '',
            device_type: '',
            manufacturer: '',
            model: '',
            is_default: false,
            is_active: true,
            notes: ''
        }
        dialog.value = true
    }

    const openEditDialog = (device: BluetoothDevice) => {
        editMode.value = true
        selectedDevice.value = device
        formData.value = {
            device_name: device.device_name,
            device_address: device.device_address,
            device_type: device.device_type,
            manufacturer: device.manufacturer || '',
            model: device.model || '',
            is_default: device.is_default,
            is_active: device.is_active,
            notes: device.notes || ''
        }
        dialog.value = true
    }

    const closeDialog = () => {
        dialog.value = false
        modalErrorMessage.value = ''
    }

    const validateForm = (): boolean => {
        if (!formData.value.device_name.trim()) {
            modalErrorMessage.value = 'Nama perangkat wajib diisi'
            return false
        }
        if (!formData.value.device_address.trim()) {
            modalErrorMessage.value = 'Alamat perangkat wajib diisi'
            return false
        }
        if (!formData.value.device_type) {
            modalErrorMessage.value = 'Tipe perangkat wajib dipilih'
            return false
        }

        // Validate MAC address format
        const macRegex = /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/
        if (!macRegex.test(formData.value.device_address)) {
            modalErrorMessage.value = 'Format alamat MAC tidak valid (contoh: 00:11:22:33:44:55)'
            return false
        }

        return true
    }

    const onSearch = () => {
        currentPage.value = 1
        fetchDevices()
    }

    const onFilterChange = () => {
        currentPage.value = 1
        fetchDevices()
    }

    const onPageChange = (page: number) => {
        currentPage.value = page
        fetchDevices()
    }

    return {
        // State
        devices,
        loading,
        saveLoading,
        deleteLoading,
        toggleLoading,
        testLoading,

        // Dialog states
        dialog,
        deleteDialog,
        editMode,
        selectedDevice,

        // Form data
        formData,

        // Filters
        search,
        typeFilter,
        activeOnly,
        perPage,
        viewMode,
        currentPage,

        // Pagination
        pagination,

        // Messages
        successMessage,
        errorMessage,
        modalErrorMessage,

        // Computed
        totalPages,
        totalDevices,
        activeDevices,
        inactiveDevices,
        printerDevices,
        otherDevices,
        deviceTypes,
        canCreateEdit,

        // Methods
        fetchDevices,
        saveDevice,
        deleteDevice,
        confirmDelete,
        toggleActiveStatus,
        setAsDefault,
        testConnection,
        openCreateDialog,
        openEditDialog,
        closeDialog,
        onSearch,
        onFilterChange,
        onPageChange
    }
}
