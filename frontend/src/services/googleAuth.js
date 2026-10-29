// Browser proof binds the one-use handoff to the tab that started sign-in.
export async function startGoogleLogin() {
  const bytes = crypto.getRandomValues(new Uint8Array(32))
  const encode = bytes => btoa(String.fromCharCode(...bytes)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
  const verifier = encode(bytes)
  const challenge = encode(new Uint8Array(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier))))
  sessionStorage.setItem('google_code_verifier', verifier)
  window.location.assign(`/api/auth/google/redirect?code_challenge=${encodeURIComponent(challenge)}`)
}
