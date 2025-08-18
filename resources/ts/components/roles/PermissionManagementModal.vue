<template>
  <v-dialog v-model="dialog" max-width="900px" persistent scrollable>
    <v-card height="80vh">
      <v-card-title class="text-h5 pa-4">
        <span>Manage Permissions - {{ role?.display_name }}</span>
        <v-spacer />
        <v-btn icon variant="text" @click="closeDialog">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text class="pa-0">
        <v-container class="pa-4">
          <!-- Search and filters -->
          <v-row class="mb-4">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="searchQuery"
                label="Search permissions"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="compact"
                clearable
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="selectedGroup"
                :items="permissionGroups"
                label="Filter by group"
                prepend-inner-icon="mdi-filter"
                variant="outlined"
                density="compact"
                clearable
              />
            </v-col>
          </v-row>

          <!-- Quick actions -->
          <v-row class="mb-4">
            <v-col cols="12">
              <v-btn
                variant="outlined"
                size="small"
                prepend-icon="mdi-checkbox-multiple-marked"
                @click="selectAll"
                class="me-2"
              >
                Select All
              </v-btn>
              <v-btn
                variant="outlined"
                size="small"
                prepend-icon="mdi-checkbox-multiple-blank-outline"
                @click="selectNone"
                class="me-2"
              >
                Select None
              </v-btn>
              <v-btn
                variant="outlined"
                size="small"
                prepend-icon="mdi-swap-horizontal"
                @click="toggleAll"
                class="me-2"
              >
                Toggle All
              </v-btn>
            </v-col>
          </v-row>

          <!-- Permissions list -->
          <v-expansion-panels
            v-model="expandedPanels"
            multiple
            variant="accordion"
          >
            <v-expansion-panel
              v-for="group in groupedPermissions"
              :key="group.name"
              :title="group.name"
              :text="`${group.selectedCount}/${group.permissions.length} selected`"
            >
              <template #title>
                <div class="d-flex align-center justify-space-between w-100">
                  <span class="text-h6">{{ group.name }}</span>
                  <v-chip
                    :color="group.selectedCount === group.permissions.length ? 'success' : group.selectedCount > 0 ? 'warning' : 'default'"
                    size="small"
                    class="me-4"
                  >
                    {{ group.selectedCount }}/{{ group.permissions.length }}
                  </v-chip>
                </div>
              </template>

              <template #text>
                <v-row>
                  <v-col
                    v-for="permission in group.permissions"
                    :key="permission.id"
                    cols="12"
                    md="6"
                    lg="4"
                  >
                    <v-card
                      variant="outlined"
                      :class="[
                        'permission-card',
                        { 'permission-selected': selectedPermissions.includes(permission.id) }
                      ]"
                      @click="togglePermission(permission.id)"
                    >
                      <v-card-text class="pa-3">
                        <div class="d-flex align-center">
                          <v-checkbox
                            :model-value="selectedPermissions.includes(permission.id)"
                            @click.stop
                            @update:model-value="togglePermission(permission.id)"
                            density="compact"
                            hide-details
                          />
                          <div class="ms-2">
                            <div class="text-subtitle-2">{{ permission.display_name }}</div>
                            <div class="text-caption text-medium-emphasis">{{ permission.name }}</div>
                            <div v-if="permission.description" class="text-caption text-disabled mt-1">
                              {{ permission.description }}
                            </div>
                          </div>
                        </div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </template>
            </v-expansion-panel>
          </v-expansion-panels>

          <!-- Empty state -->
          <v-sheet
            v-if="groupedPermissions.length === 0"
            class="text-center pa-8"
            color="surface-variant"
            rounded
          >
            <v-icon size="64" color="disabled">mdi-shield-search</v-icon>
            <div class="text-h6 mt-4">No permissions found</div>
            <div class="text-body-2 text-medium-emphasis">
              Try adjusting your search or filter criteria
            </div>
          </v-sheet>
        </v-container>
      </v-card-text>

      <v-card-actions class="pa-4">
        <v-chip color="primary" variant="outlined">
          {{ selectedPermissions.length }} permission(s) selected
        </v-chip>
        <v-spacer />
        <v-btn
          variant="text"
          @click="closeDialog"
          :disabled="loading"
        >
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          @click="savePermissions"
          :loading="loading"
        >
          Save Permissions
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { usePermissions } from '@/composables/usePermissions'
import { useRoles } from '@/composables/useRoles'

interface Props {
  modelValue: boolean
  role?: any
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'saved'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { permissions, fetchPermissions } = usePermissions()
const { updateRolePermissions } = useRoles()

// Form state
const loading = ref(false)
const searchQuery = ref('')
const selectedGroup = ref('')
const selectedPermissions = ref<number[]>([])
const expandedPanels = ref<number[]>([])

// Computed
const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const filteredPermissions = computed(() => {
  let filtered = permissions.value

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(permission =>
      permission.name.toLowerCase().includes(query) ||
      permission.display_name.toLowerCase().includes(query) ||
      permission.description?.toLowerCase().includes(query)
    )
  }

  // Filter by group
  if (selectedGroup.value) {
    filtered = filtered.filter(permission =>
      permission.name.startsWith(selectedGroup.value.toLowerCase())
    )
  }

  return filtered
})

const groupedPermissions = computed(() => {
  const groups: Record<string, any[]> = {}

  filteredPermissions.value.forEach(permission => {
    // Extract group from permission name (e.g., 'user.create' -> 'User')
    const groupName = permission.name.split('.')[0]
    const displayGroupName = groupName.charAt(0).toUpperCase() + groupName.slice(1)

    if (!groups[displayGroupName]) {
      groups[displayGroupName] = []
    }
    groups[displayGroupName].push(permission)
  })

  return Object.entries(groups).map(([name, permissions]) => ({
    name,
    permissions,
    selectedCount: permissions.filter(p => selectedPermissions.value.includes(p.id)).length
  })).sort((a, b) => a.name.localeCompare(b.name))
})

const permissionGroups = computed(() => {
  const groups = new Set<string>()
  permissions.value.forEach(permission => {
    const groupName = permission.name.split('.')[0]
    groups.add(groupName.charAt(0).toUpperCase() + groupName.slice(1))
  })
  return Array.from(groups).sort()
})

// Watchers
watch(() => props.role, (newRole) => {
  if (newRole?.permissions) {
    selectedPermissions.value = newRole.permissions.map((p: any) => p.id)
  } else {
    selectedPermissions.value = []
  }
}, { immediate: true })

watch(() => props.modelValue, (isOpen) => {
  if (isOpen && permissions.value.length === 0) {
    fetchPermissions()
  }
})

// Methods
const closeDialog = () => {
  emit('update:modelValue', false)
}

const togglePermission = (permissionId: number) => {
  const index = selectedPermissions.value.indexOf(permissionId)
  if (index > -1) {
    selectedPermissions.value.splice(index, 1)
  } else {
    selectedPermissions.value.push(permissionId)
  }
}

const selectAll = () => {
  selectedPermissions.value = filteredPermissions.value.map(p => p.id)
}

const selectNone = () => {
  selectedPermissions.value = []
}

const toggleAll = () => {
  const filteredIds = filteredPermissions.value.map(p => p.id)
  const allSelected = filteredIds.every(id => selectedPermissions.value.includes(id))

  if (allSelected) {
    // Remove all filtered permissions
    selectedPermissions.value = selectedPermissions.value.filter(id => !filteredIds.includes(id))
  } else {
    // Add all filtered permissions that aren't already selected
    const newSelections = filteredIds.filter(id => !selectedPermissions.value.includes(id))
    selectedPermissions.value.push(...newSelections)
  }
}

const savePermissions = async () => {
  if (!props.role?.id) return

  loading.value = true

  try {
    await updateRolePermissions(props.role.id, selectedPermissions.value)
    emit('saved')
    closeDialog()
  } catch (error) {
    console.error('Error saving permissions:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  if (permissions.value.length === 0) {
    fetchPermissions()
  }
  
  // Expand first few panels by default
  expandedPanels.value = [0, 1, 2]
})
</script>

<style scoped>
.permission-card {
  cursor: pointer;
  transition: all 0.2s ease;
}

.permission-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.permission-selected {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.04);
}

.v-expansion-panel-title {
  padding: 16px !important;
}
</style>
