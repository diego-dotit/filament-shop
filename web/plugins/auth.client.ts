// This plugin runs once on the client before any route middleware,
// ensuring session is restored from localStorage before auth guards fire.
export default defineNuxtPlugin(async () => {
  const { restoreSession } = useAuth()
  await restoreSession()
})
