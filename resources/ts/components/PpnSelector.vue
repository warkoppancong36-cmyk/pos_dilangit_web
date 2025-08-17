<script setup lang="ts">
import { Ppn } from '@/composables/usePpn'
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

// Props
interface Props {
  modelValue?: number | null
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  clearable?: boolean
  variant?: 'filled' | 'outlined' | 'underlined' | 'plain' | 'solo' | 'solo-filled' | 'solo-inverted'
  density?: 'default' | 'comfortable' | 'compact'
  hideDetails?: boolean
  errorMessages?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  label: 'Select PPN',
  placeholder: 'Choose PPN rate...',
  required: false,
  disabled: false,
  clearable: true,
  variant: 'outlined',
  density: 'default',
  hideDetails: false
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: number | null]
  'ppn-selected': [ppn: Ppn | null]
}>()

// Reactive data
const ppnOptions = ref<Ppn[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

// Computed
const selectedValue = computed({
  get: () => props.modelValue,
  set: (value: number | null) => {
    emit('update:modelValue', value)
    
    // Emit selected PPN object
    const selectedPpn = value ? ppnOptions.value.find(ppn => ppn.id_ppn === value) || null : null
    emit('ppn-selected', selectedPpn)
  }
})

const formattedOptions = computed(() => {
  return ppnOptions.value.map(ppn => ({
    title: `${ppn.name} (${ppn.nominal}%)`,
    value: ppn.id_ppn,
    props: {
      subtitle: ppn.description || `Tax rate: ${ppn.nominal}%`
    }
  }))
})

const rules = computed(() => {
  const ruleList: Array<(v: any) => boolean | string> = []
  
  if (props.required) {
    ruleList.push((v: any) => !!v || `${props.label} is required`)
  }
  
  return ruleList
})

// Methods
const fetchActivePpn = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await axios.get('/api/ppn/active')
    
    if (response.data.success) {
      ppnOptions.value = response.data.data
    } else {
      error.value = 'Failed to load PPN options'
    }
  } catch (err: any) {
    console.error('Error fetching active PPN:', err)
    error.value = err.response?.data?.message || 'Failed to load PPN options'
  } finally {
    loading.value = false
  }
}

const refreshOptions = () => {
  fetchActivePpn()
}

// Lifecycle
onMounted(() => {
  fetchActivePpn()
})

// Expose methods for parent component
defineExpose({
  refreshOptions,
  ppnOptions: computed(() => ppnOptions.value)
})
</script>

<template>
  <VSelect
    v-model="selectedValue"
    :items="formattedOptions"
    :label="label"
    :placeholder="placeholder"
    :loading="loading"
    :disabled="disabled || loading"
    :clearable="clearable"
    :variant="variant"
    :density="density"
    :hide-details="hideDetails"
    :error-messages="errorMessages"
    :rules="rules"
    item-title="title"
    item-value="value"
    :return-object="false"
  >
    <!-- Custom item slot for better display -->
    <template #item="{ props: itemProps, item }">
      <VListItem v-bind="itemProps">
        <template #prepend>
          <VIcon
            icon="mdi-percent"
            color="primary"
            size="small"
          />
        </template>
        
        <VListItemTitle>{{ item.title }}</VListItemTitle>
        <VListItemSubtitle v-if="item.props?.subtitle">
          {{ item.props.subtitle }}
        </VListItemSubtitle>
      </VListItem>
    </template>

    <!-- Loading state -->
    <template #prepend-inner>
      <VProgressCircular
        v-if="loading"
        indeterminate
        size="16"
        color="primary"
      />
      <VIcon
        v-else
        icon="mdi-percent"
        color="primary"
      />
    </template>

    <!-- Error state -->
    <template #append-inner>
      <VBtn
        v-if="error"
        icon="mdi-refresh"
        variant="text"
        size="small"
        color="error"
        @click="refreshOptions"
      />
    </template>

    <!-- No data state -->
    <template #no-data>
      <VListItem>
        <VListItemTitle>
          {{ error || 'No active PPN rates available' }}
        </VListItemTitle>
        <VListItemSubtitle v-if="error">
          <VBtn
            variant="text"
            size="small"
            prepend-icon="mdi-refresh"
            @click="refreshOptions"
          >
            Retry
          </VBtn>
        </VListItemSubtitle>
      </VListItem>
    </template>
  </VSelect>
</template>

<style scoped>
/* Custom styles if needed */
</style>
