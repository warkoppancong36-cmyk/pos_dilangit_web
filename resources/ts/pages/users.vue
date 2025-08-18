<route lang="yaml">
meta:
  layout: default
  requiresAuth: true
  permission: user.view
  name: users
</route>

<script setup lang="ts">
import UsersMain from './users-main.vue'
</script>

<template>
  <UsersMain />
</template>
