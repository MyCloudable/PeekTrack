import axios from 'axios'
import { ref } from 'vue'
import {useLoading} from './useLoading'

const { isLoading, setLoading } = useLoading()

export const useRecovery = () => {

  const recover = async (model, id) => {
    setLoading(true)
    
    try {
      const response = await axios.post(`/recover/${model}/${id}`)
      
      if (response.data.success) {
        return true
      } else {
        return false
      }
    } catch (error) {
      return false
    } finally {
      setLoading(false)
    }
  }

  return {
    recover,
  }
}
