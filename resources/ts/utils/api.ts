import { ofetch } from 'ofetch'

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  async onRequest({ options }) {
    const accessToken = useCookie('accessToken').value


    if (accessToken) {
      if (!options.headers)
        options.headers = new Headers()

      if (options.headers instanceof Headers)
        options.headers.set('Authorization', `Bearer ${accessToken}`)
      else
        (options.headers as any).Authorization = `Bearer ${accessToken}`

    }
    else {
    }
  },
})
