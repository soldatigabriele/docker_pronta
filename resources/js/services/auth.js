import axios from 'axios'

const API_BASE_URL = 'https://pronta.test/api'

class AuthService {
  constructor() {
    this.user = null
    this.token = localStorage.getItem('authToken')
    
    if (this.token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
    }
  }

  async login(email, password) {
    try {
      const response = await axios.post(`${API_BASE_URL}/auth/login`, {
        email,
        password
      })

      if (response.data.success) {
        const { user, token } = response.data.data
        this.user = user
        this.token = token
        
        localStorage.setItem('authToken', token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        
        return { success: true, user }
      }
      
      return { success: false, message: response.data.message }
    } catch (error) {
      console.error('Login error:', error)
      
      if (error.response?.data?.message) {
        return { success: false, message: error.response.data.message }
      }
      
      return { success: false, message: 'Login failed. Please try again.' }
    }
  }

  async register(name, email, password, passwordConfirmation) {
    try {
      const response = await axios.post(`${API_BASE_URL}/auth/register`, {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation
      })

      if (response.data.success) {
        const { user, token } = response.data.data
        this.user = user
        this.token = token
        
        localStorage.setItem('authToken', token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        
        return { success: true, user }
      }
      
      return { success: false, message: response.data.message }
    } catch (error) {
      console.error('Registration error:', error)
      
      if (error.response?.data?.errors) {
        const errors = error.response.data.errors
        const firstError = Object.values(errors)[0][0]
        return { success: false, message: firstError }
      }
      
      if (error.response?.data?.message) {
        return { success: false, message: error.response.data.message }
      }
      
      return { success: false, message: 'Registration failed. Please try again.' }
    }
  }

  async logout() {
    try {
      if (this.token) {
        await axios.post(`${API_BASE_URL}/auth/logout`)
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      this.user = null
      this.token = null
      localStorage.removeItem('authToken')
      delete axios.defaults.headers.common['Authorization']
    }
  }

  async getCurrentUser() {
    if (!this.token) {
      return null
    }

    try {
      const response = await axios.get(`${API_BASE_URL}/auth/user`)
      
      if (response.data.success) {
        this.user = response.data.data
        return this.user
      }
      
      // Token might be invalid, clear it
      this.logout()
      return null
    } catch (error) {
      console.error('Get current user error:', error)
      // Token might be invalid, clear it
      this.logout()
      return null
    }
  }

  isAuthenticated() {
    return !!this.token
  }

  getUser() {
    return this.user
  }
}

export default new AuthService() 