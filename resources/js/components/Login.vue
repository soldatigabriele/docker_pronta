<template>
  <div>
    <h1>Login</h1>
    
    <div v-if="error">
      Error: {{ error }}
    </div>
    
    <div v-if="message">
      {{ message }}
    </div>
    
    <!-- Login Form -->
    <form v-if="!showRegister" @submit.prevent="handleLogin">
      <h2>Sign In</h2>
      
      <div>
        <label for="email">Email:</label>
        <input 
          id="email"
          v-model="loginForm.email" 
          type="email" 
          required
        />
      </div>
      
      <div>
        <label for="password">Password:</label>
        <input 
          id="password"
          v-model="loginForm.password" 
          type="password" 
          required
        />
      </div>
      
      <button type="submit" :disabled="loading">
        {{ loading ? 'Signing in...' : 'Sign In' }}
      </button>
      
      <p>
        Don't have an account? 
        <button type="button" @click="showRegister = true">Register here</button>
      </p>
    </form>
    
    <!-- Register Form -->
    <form v-else @submit.prevent="handleRegister">
      <h2>Register</h2>
      
      <div>
        <label for="name">Name:</label>
        <input 
          id="name"
          v-model="registerForm.name" 
          type="text" 
          required
        />
      </div>
      
      <div>
        <label for="register-email">Email:</label>
        <input 
          id="register-email"
          v-model="registerForm.email" 
          type="email" 
          required
        />
      </div>
      
      <div>
        <label for="register-password">Password:</label>
        <input 
          id="register-password"
          v-model="registerForm.password" 
          type="password" 
          required
        />
      </div>
      
      <div>
        <label for="password-confirmation">Confirm Password:</label>
        <input 
          id="password-confirmation"
          v-model="registerForm.passwordConfirmation" 
          type="password" 
          required
        />
      </div>
      
      <button type="submit" :disabled="loading">
        {{ loading ? 'Registering...' : 'Register' }}
      </button>
      
      <p>
        Already have an account? 
        <button type="button" @click="showRegister = false">Sign in here</button>
      </p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import authService from '../services/auth'

const router = useRouter()

const loading = ref(false)
const error = ref('')
const message = ref('')
const showRegister = ref(false)

const loginForm = ref({
  email: 'test@example.com',
  password: 'password'
})

const registerForm = ref({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: ''
})

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  message.value = ''
  
  try {
    const result = await authService.login(
      loginForm.value.email,
      loginForm.value.password
    )
    
    if (result.success) {
      message.value = 'Login successful! Redirecting...'
      setTimeout(() => {
        router.push('/home')
      }, 1000)
    } else {
      error.value = result.message
    }
  } catch (err) {
    error.value = 'An unexpected error occurred'
    console.error('Login error:', err)
  } finally {
    loading.value = false
  }
}

const handleRegister = async () => {
  loading.value = true
  error.value = ''
  message.value = ''
  
  if (registerForm.value.password !== registerForm.value.passwordConfirmation) {
    error.value = 'Passwords do not match'
    loading.value = false
    return
  }
  
  try {
    const result = await authService.register(
      registerForm.value.name,
      registerForm.value.email,
      registerForm.value.password,
      registerForm.value.passwordConfirmation
    )
    
    if (result.success) {
      message.value = 'Registration successful! Redirecting...'
      setTimeout(() => {
        router.push('/home')
      }, 1000)
    } else {
      error.value = result.message
    }
  } catch (err) {
    error.value = 'An unexpected error occurred'
    console.error('Registration error:', err)
  } finally {
    loading.value = false
  }
}
</script> 