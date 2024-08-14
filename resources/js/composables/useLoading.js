import { ref } from 'vue'

const isLoading = ref(false)

const setLoading = (value) => {
  isLoading.value = value
}

export function useLoading() {
  return { isLoading, setLoading }
}
