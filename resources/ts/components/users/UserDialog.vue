<template>
  <VDialog
    :model-value="modelValue"
    max-width="600px"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard>
      <VCardTitle class="text-h5 pa-6 pb-4">
        <VIcon :icon="editMode ? 'tabler-edit' : 'tabler-plus'" class="me-3" />
        {{ editMode ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}
      </VCardTitle>

      <VDivider />

      <VForm ref="formRef" @submit.prevent="handleSubmit">
        <VCardText class="pa-6">
          <!-- Error Alert -->
          <VAlert
            v-if="errorMessage"
            type="error"
            variant="outlined"
            class="mb-4"
            :text="errorMessage"
            closable
            @click:close="$emit('clear-error')"
          />

          <VRow>
            <!-- Full Name -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.name"
                label="Nama Lengkap"
                variant="outlined"
                prepend-inner-icon="tabler-user"
                :rules="nameRules"
                required
              />
            </VCol>

            <!-- Username -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.username"
                label="Username"
                variant="outlined"
                prepend-inner-icon="tabler-at"
                :rules="usernameRules"
                required
              />
            </VCol>

            <!-- Email -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.email"
                label="Email"
                type="email"
                variant="outlined"
                prepend-inner-icon="tabler-mail"
                :rules="emailRules"
                required
              />
            </VCol>

            <!-- Phone -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.phone"
                label="Nomor Telepon"
                variant="outlined"
                prepend-inner-icon="tabler-phone"
              />
            </VCol>

            <!-- Password -->
            <VCol cols="12" md="6">
              <VTextField
                v-model="formData.password"
                :label="editMode ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password'"
                :type="showPassword ? 'text' : 'password'"
                variant="outlined"
                prepend-inner-icon="tabler-lock"
                :append-inner-icon="showPassword ? 'tabler-eye-off' : 'tabler-eye'"
                :rules="passwordRules"
                :required="!editMode"
                @click:append-inner="showPassword = !showPassword"
              />
            </VCol>

            <!-- Role -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="formData.role_id"
                label="Role"
                variant="outlined"
                prepend-inner-icon="tabler-shield"
                :items="roleOptions"
                item-title="name"
                item-value="id"
                :rules="roleRules"
                required
              />
            </VCol>

            <!-- Status -->
            <VCol cols="12">
              <VSwitch
                v-model="formData.is_active"
                label="Pengguna Aktif"
                color="success"
                inset
              />
            </VCol>
          </VRow>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-6">
          <VSpacer />
          <VBtn
            variant="outlined"
            @click="$emit('cancel')"
          >
            Batal
          </VBtn>
          <VBtn
            type="submit"
            color="primary"
            variant="elevated"
            :loading="loading"
            :prepend-icon="editMode ? 'tabler-check' : 'tabler-plus'"
          >
            {{ editMode ? 'Perbarui' : 'Simpan' }}
          </VBtn>
        </VCardActions>
      </VForm>
    </VCard>
  </VDialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { UserFormData, Role } from '@/composables/useUserManagement'

interface Props {
  modelValue: boolean
  editMode: boolean
  formData: UserFormData
  loading: boolean
  errorMessage: string
  roleOptions: Role[]
  nameRules: Array<(v: string) => boolean | string>
  usernameRules: Array<(v: string) => boolean | string>
  emailRules: Array<(v: string) => boolean | string>
  passwordRules: Array<(v: string) => boolean | string>
  roleRules: Array<(v: number) => boolean | string>
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'save'): void
  (e: 'cancel'): void
  (e: 'clear-error'): void
}

defineProps<Props>()
const emit = defineEmits<Emits>()

const formRef = ref()
const showPassword = ref(false)

const handleSubmit = async () => {
  const { valid } = await formRef.value.validate()
  
  if (valid) {
    emit('save')
  }
}
</script>
