<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  permission: role.view
  name: roles
</route>

<script setup lang="ts">
import RolesMain from './roles-main.vue'
</script>

<template>
  <RolesMain />
</template>
