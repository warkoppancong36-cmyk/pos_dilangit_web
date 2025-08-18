<template>
  <v-dialog v-model="dialog" max-width="600px" persistent>
    <v-card>
      <v-card-title class="text-h5 pa-4">
        <span>{{ isEdit ? 'Edit Role' : 'Create New Role' }}</span>
        <v-spacer />
        <v-btn icon variant="text" @click="closeDialog">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-card-text class="pa-4">
        <v-form ref="formRef" v-model="valid" @submit.prevent="saveRole">
          <v-row>
            <v-col cols="12">
              <v-text-field
                v-model="roleForm.name"
                label="Role Name"
                :rules="nameRules"
                variant="outlined"
                required
                prepend-inner-icon="mdi-shield-account"
              />
            </v-col>

            <v-col cols="12">
              <v-text-field
                v-model="roleForm.display_name"
                label="Display Name"
                :rules="displayNameRules"
                variant="outlined"
                required
                prepend-inner-icon="mdi-format-title"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="roleForm.description"
                label="Description"
                variant="outlined"
                rows="3"
                prepend-inner-icon="mdi-text-long"
              />
            </v-col>

            <v-col cols="12">
              <v-switch
                v-model="roleForm.is_active"
                label="Active"
                color="primary"
                inset
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-card-actions class="pa-4">
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
          @click="saveRole"
          :loading="loading"
          :disabled="!valid"
        >
          {{ isEdit ? 'Update' : 'Create' }} Role
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoles } from '@/composables/useRoles'

interface RoleForm {
  id?: number
  name: string
  display_name: string
  description?: string
  is_active: boolean
}

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

const { createRole, updateRole } = useRoles()

// Form
const formRef = ref()
const valid = ref(false)
const loading = ref(false)

const roleForm = ref<RoleForm>({
  name: '',
  display_name: '',
  description: '',
  is_active: true
})

// Validation rules
const nameRules = [
  (v: string) => !!v || 'Role name is required',
  (v: string) => (v && v.length >= 3) || 'Role name must be at least 3 characters',
  (v: string) => /^[a-zA-Z0-9_-]+$/.test(v) || 'Role name can only contain letters, numbers, underscores, and hyphens'
]

const displayNameRules = [
  (v: string) => !!v || 'Display name is required',
  (v: string) => (v && v.length >= 3) || 'Display name must be at least 3 characters'
]

// Computed
const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.role?.id)

// Methods
const resetForm = () => {
  roleForm.value = {
    name: '',
    display_name: '',
    description: '',
    is_active: true
  }
  
  if (formRef.value) {
    formRef.value.resetValidation()
  }
}

const closeDialog = () => {
  emit('update:modelValue', false)
}

const saveRole = async () => {
  if (!formRef.value?.validate()) {
    return
  }

  loading.value = true

  try {
    if (isEdit.value) {
      await updateRole(roleForm.value.id!, roleForm.value)
    } else {
      await createRole(roleForm.value)
    }

    emit('saved')
    closeDialog()
  } catch (error) {
    console.error('Error saving role:', error)
  } finally {
    loading.value = false
  }
}

// Watchers
watch(() => props.role, (newRole) => {
  if (newRole) {
    roleForm.value = {
      id: newRole.id,
      name: newRole.name || '',
      display_name: newRole.display_name || '',
      description: newRole.description || '',
      is_active: newRole.is_active !== undefined ? newRole.is_active : true
    }
  } else {
    resetForm()
  }
}, { immediate: true })

watch(() => props.modelValue, (isOpen) => {
  if (!isOpen) {
    resetForm()
  }
})
</script>

<style scoped>
.v-card-title {
  background-color: rgb(var(--v-theme-surface-variant));
}
</style>
