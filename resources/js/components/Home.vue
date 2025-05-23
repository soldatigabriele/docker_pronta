<template>
  <div>
    <h1>Welcome to Your Dashboard</h1>
    
    <div v-if="user">
      <h2>User Information</h2>
      <p><strong>Name:</strong> {{ user.name }}</p>
      <p><strong>Email:</strong> {{ user.email }}</p>
      <p><strong>Member since:</strong> {{ formatDate(user.created_at) }}</p>
    </div>
    
    <div v-else>
      <p>Loading user information...</p>
    </div>
    
    <div>
      <h2>Quick Actions</h2>
      <button @click="refreshUser">Refresh User Info</button>
      <button @click="handleLogout" :disabled="loggingOut">
        {{ loggingOut ? 'Logging out...' : 'Logout' }}
      </button>
    </div>
    
    <div>
      <h2>Next Steps</h2>
      <p>This is where you would typically see:</p>
      <ul>
        <li>Your reminder lists</li>
        <li>Recent activity</li>
        <li>Quick add items</li>
        <li>Shared lists</li>
      </ul>
      <p><em>These features will be implemented next!</em></p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import authService from '../services/auth'

const router = useRouter()

const user = ref(null)
const loggingOut = ref(false)

const refreshUser = async () => {
  try {
    const currentUser = await authService.getCurrentUser()
    user.value = currentUser
  } catch (error) {
    console.error('Failed to refresh user:', error)
  }
}

const handleLogout = async () => {
  loggingOut.value = true
  
  try {
    await authService.logout()
    router.push('/login')
  } catch (error) {
    console.error('Logout error:', error)
  } finally {
    loggingOut.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'Unknown'
  
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

onMounted(() => {
  // Get user from auth service (should already be loaded by router guard)
  user.value = authService.getUser()
  
  // If not available, try to fetch it
  if (!user.value) {
    refreshUser()
  }
})
</script> 