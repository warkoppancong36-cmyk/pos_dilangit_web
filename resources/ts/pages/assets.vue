<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  permission: asset.view
  name: assets
</route>

<script setup lang="ts">
import AssetsMain from './assets-main.vue'
</script>

<template>
  <AssetsMain />
</template>
