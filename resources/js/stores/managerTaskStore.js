// resources/js/stores/managerTaskStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/plugins/axios'

export const useManagerTaskStore = defineStore('managerTask', () => {
  const tasks = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchTasks() {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/manager/tasks')
      tasks.value = data.data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Error loading tasks'
    } finally {
      loading.value = false
    }
  }

  async function addTask(title, priority = 'media') {
    try {
      const { data } = await api.post('/manager/tasks', { title, priority })
      tasks.value.unshift(data.task)
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Error creating task' }
    }
  }

  async function toggleTask(task) {
    const previous = task.completed
    task.completed = !task.completed // optimistic
    try {
      await api.patch(`/manager/tasks/${task.id}`, { completed: task.completed })
    } catch {
      task.completed = previous
    }
  }

  async function deleteTask(task) {
    const previous = [...tasks.value]
    tasks.value = tasks.value.filter(t => t.id !== task.id)
    try {
      await api.delete(`/manager/tasks/${task.id}`)
    } catch {
      tasks.value = previous
    }
  }

  return { tasks, loading, error, fetchTasks, addTask, toggleTask, deleteTask }
})