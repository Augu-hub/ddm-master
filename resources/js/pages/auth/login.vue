<template>
  <div class="auth-page">
    <Head title="Connexion — DADEM Suite" />

    <div class="auth-grid">
      <!-- Panneau visuel -->
      <aside class="brand-pane">
        <div class="brand-pane-overlay"></div>

        <img
          class="brand-hero"
          src="https://source.unsplash.com/aT24h1Lm_vw/2000x1400"
          alt="DADEM — Audit • Risk • Processus • DDM Param"
        />

        <div class="brand-content">
          <h2 class="brand-title">
            Audit • Risques • Processus <span class="accent">DDM Param</span>
          </h2>
          <p class="brand-desc">
            Plateforme intégrée pour piloter risques, contrôles et paramétrage multi-tenant.
          </p>

          <!-- Icônes PARP -->
          <div class="parp-grid">
            <div class="parp-item">
              <div class="parp-icon parp-param">
                <i class="ti ti-settings-cog"></i>
              </div>
              <span class="parp-label">Param</span>
            </div>
            <div class="parp-item">
              <div class="parp-icon parp-audit">
                <i class="ti ti-file-check"></i>
              </div>
              <span class="parp-label">Audit</span>
            </div>
            <div class="parp-item">
              <div class="parp-icon parp-risque">
                <i class="ti ti-alert-triangle"></i>
              </div>
              <span class="parp-label">Risque</span>
            </div>
            <div class="parp-item">
              <div class="parp-icon parp-processus">
                <i class="ti ti-sitemap"></i>
              </div>
              <span class="parp-label">Processus</span>
            </div>
          </div>

          <figure class="brand-figure" role="img" aria-label="Projets d'audit">
            <img
              src="https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1600&q=80"
              alt="Projets d'audit — tableaux, reportings, tâches"
              loading="lazy"
            />
            <figcaption>Projets d'audit — coordination claire & exécution rapide</figcaption>
          </figure>

          <ul class="brand-points">
            <li><i class="ti ti-shield-check"></i> Contrôles & matrices de risques</li>
            <li><i class="ti ti-database-cog"></i> Paramétrage modulaire</li>
            <li><i class="ti ti-users-group"></i> Processus & rôles gouvernés</li>
            <li><i class="ti ti-chart-line"></i> Dashboards temps réel</li>
          </ul>
        </div>
      </aside>

      <!-- Colonne formulaire -->
      <main class="form-pane">
        <div class="form-card">
          <div class="form-header">
            <div class="logo-badge mb-3">
              <span class="logo-dot"></span>
              <span class="logo-text">DADEM</span>
              <span class="logo-sub">Suite</span>
            </div>

            <h1 class="form-title mb-2">Connexion</h1>
            <p class="form-subtitle mb-3">Accédez au panneau d'administration.</p>
          </div>

          <!-- Section connexion simplifiée -->
          <div class="login-methods mb-4">
            <div class="d-grid">
              <b-button 
                variant="outline-primary" 
                class="sso-btn mb-2"
                disabled
              >
                <i class="ti ti-brand-google me-2"></i>
                Se connecter avec Google (bientôt)
              </b-button>
              
              <div class="divider">
                <span class="divider-text">ou avec e-mail</span>
              </div>
            </div>
          </div>

          <p v-if="status" class="alert alert-success py-2 mb-3" role="status">{{ status }}</p>

          <b-form @submit.prevent="submit" class="mb-3 text-start" novalidate>
            <div v-if="error" class="alert alert-danger py-2 mb-3" role="alert">{{ error }}</div>

            <b-form-group label="Adresse e-mail" label-for="email" class="mb-3">
              <b-input-group>
                <b-input-group-text aria-hidden="true"><i class="ti ti-mail"></i></b-input-group-text>
                <b-form-input
                  id="email"
                  type="email"
                  name="email"
                  v-model="form.email"
                  :state="fieldState('email')"
                  placeholder="ex. admin@dadem.app"
                  autocomplete="email"
                  required
                />
              </b-input-group>
              <small v-if="form.errors.email" class="text-danger d-block mt-1">{{ form.errors.email }}</small>
            </b-form-group>

            <b-form-group label="Mot de passe" label-for="password" class="mb-2">
              <b-input-group>
                <b-input-group-text aria-hidden="true"><i class="ti ti-lock"></i></b-input-group-text>
                <b-form-input
                  id="password"
                  :type="showPassword ? 'text' : 'password'"
                  name="password"
                  v-model="form.password"
                  :state="fieldState('password')"
                  placeholder="Votre mot de passe"
                  autocomplete="current-password"
                  required
                />
                <b-button
                  variant="outline-secondary"
                  @click="togglePassword"
                  :aria-pressed="showPassword ? 'true' : 'false'"
                  :title="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                >
                  <i :class="showPassword ? 'ti ti-eye-off' : 'ti ti-eye'"></i>
                </b-button>
              </b-input-group>
              <small v-if="form.errors.password" class="text-danger d-block mt-1">{{ form.errors.password }}</small>
            </b-form-group>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <b-form-checkbox v-model="form.remember">Se souvenir</b-form-checkbox>
              <Link v-if="canResetPassword" :href="route('password.request')" class="forgot-password-link">
                Mot de passe oublié ?
              </Link>
            </div>

            <div class="d-grid">
              <b-button variant="primary" type="submit" :disabled="form.processing" class="submit-btn">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                <i v-else class="ti ti-login-2 me-1"></i> Se connecter
              </b-button>
            </div>
          </b-form>

          <p class="register-link text-center mb-0">
            Pas de compte ?
            <Link :href="route('register')" class="fw-semibold">Créer un compte</Link>
          </p>

          <div class="security-info mt-3">
            <span class="badge bg-soft-accent text-accent"><i class="ti ti-lock-check me-1"></i> SSO bientôt dispo</span>
            <span class="sep">•</span>
            <span><i class="ti ti-shield-lock me-1"></i> Sécurité renforcée</span>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps<{ 
  status?: string
  canResetPassword: boolean 
}>()

const error = ref('')
const showPassword = ref(false)

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const fieldState = (key: 'email' | 'password') => {
  if (form.errors[key]) return false
  if (form[key] && !form.errors[key]) return true
  return undefined
}

const togglePassword = () => { 
  showPassword.value = !showPassword.value 
}

const submit = () => {
  error.value = ''
  form.post(route('login'), {
    onError: (errs) => {
      if (errs && typeof errs === 'object' && Object.keys(errs).length === 0) {
        error.value = 'Échec de la connexion. Vérifiez vos identifiants.'
      }
    },
    onFinish: () => form.reset('password'),
  })
}
</script>

<style scoped>
:root {
  --indigo: #5b6cff;
  --violet: #7c3aed;
  --emerald: #10b981;
  --amber: #f59e0b;
  --blue: #3b82f6;
  --rose: #f43f5e;
  --slate-700: #334155;
  --slate-500: #64748b;
  --slate-300: #cbd5e1;
  --soft-bg: #f8fafc;
  --accent: var(--emerald);
  --radius: 12px;
  --t: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.auth-page {
  min-height: 100vh;
}

.auth-grid {
  display: grid;
  grid-template-columns: 1fr;
  min-height: 100vh;
}

@media (min-width: 992px) {
  .auth-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.brand-pane {
  position: relative;
  background: radial-gradient(1400px 700px at 110% -10%, rgba(124, 58, 237, 0.35), transparent 65%),
              radial-gradient(1000px 600px at -20% 110%, rgba(91, 108, 255, 0.35), transparent 65%),
              linear-gradient(135deg, #0f172a, #1e293b);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.brand-pane-overlay {
  position: absolute;
  inset: 0;
  background: radial-gradient(1400px 1400px at 70% 20%, rgba(16, 185, 129, 0.1), transparent 70%);
  pointer-events: none;
}

.brand-hero {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.28;
  mix-blend-mode: soft-light;
}

.brand-content {
  position: relative;
  z-index: 2;
  color: #e5e7eb;
  padding: 24px;
  width: 100%;
  max-width: 680px;
  margin: 0 auto;
}

.brand-title {
  font-weight: 800;
  font-size: clamp(1.3rem, 2.2vw, 1.7rem);
  line-height: 1.2;
  margin: 0 0 0.4rem;
  color: #fff;
  letter-spacing: -0.3px;
}

.brand-title .accent {
  color: var(--accent);
  text-shadow: 0 6px 24px rgba(16, 185, 129, 0.25);
}

.brand-desc {
  color: #cbd5e1;
  font-size: clamp(0.88rem, 1.4vw, 0.95rem);
  margin: 0 0 1.2rem;
  line-height: 1.4;
}

/* Grid PARP */
.parp-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.8rem;
  margin: 0 0 1.2rem;
}

.parp-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
}

.parp-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(91, 108, 255, 0.12));
  border: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  color: var(--accent);
  transition: var(--t);
  backdrop-filter: blur(10px);
}

.parp-icon:hover {
  transform: translateY(-3px);
}

/* Couleurs spécifiques pour chaque module */
.parp-param {
  background: linear-gradient(135deg, rgba(91, 108, 255, 0.15), rgba(59, 130, 246, 0.12));
  color: #5b6cff;
  border-color: rgba(91, 108, 255, 0.15);
}

.parp-param:hover {
  box-shadow: 0 10px 24px rgba(91, 108, 255, 0.3);
  background: linear-gradient(135deg, rgba(91, 108, 255, 0.2), rgba(59, 130, 246, 0.15));
}

.parp-audit {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.12));
  color: #10b981;
  border-color: rgba(16, 185, 129, 0.15);
}

.parp-audit:hover {
  box-shadow: 0 10px 24px rgba(16, 185, 129, 0.3);
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.15));
}

.parp-risque {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(251, 146, 60, 0.12));
  color: #f59e0b;
  border-color: rgba(245, 158, 11, 0.15);
}

.parp-risque:hover {
  box-shadow: 0 10px 24px rgba(245, 158, 11, 0.3);
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(251, 146, 60, 0.15));
}

.parp-processus {
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.15), rgba(147, 51, 234, 0.12));
  color: #7c3aed;
  border-color: rgba(124, 58, 237, 0.15);
}

.parp-processus:hover {
  box-shadow: 0 10px 24px rgba(124, 58, 237, 0.3);
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.2), rgba(147, 51, 234, 0.15));
}

.parp-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #f1f5f9;
  text-align: center;
}

.brand-figure {
  margin: 1.2rem 0;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
}

.brand-figure img {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 10px;
}

.brand-figure figcaption {
  font-size: 0.78rem;
  color: #e2e8f0;
  margin-top: 0.35rem;
  text-align: center;
  opacity: 0.85;
}

.brand-points {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem 0.8rem;
}

.brand-points li {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  color: #f1f5f9;
  font-weight: 600;
  font-size: clamp(0.82rem, 1.3vw, 0.9rem);
}

.brand-points i {
  color: var(--accent);
  font-size: 1rem;
  filter: drop-shadow(0 3px 6px rgba(16, 185, 129, 0.18));
}

.form-pane {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  background: linear-gradient(135deg, #fafbfc 0%, #fff 100%);
  min-height: 100vh;
  overflow-y: auto;
}

.form-card {
  width: min(480px, 92vw);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: var(--radius);
  padding: 24px 22px;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06), 0 2px 8px rgba(15, 23, 42, 0.03);
  margin: auto;
}

.logo-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: linear-gradient(135deg, rgba(91, 108, 255, 0.08), rgba(124, 58, 237, 0.08));
  border: 1px solid rgba(124, 58, 237, 0.12);
  padding: 0.45rem 0.75rem;
  border-radius: 999px;
}

.logo-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--indigo), var(--violet));
  box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.1);
}

.logo-text {
  font-weight: 800;
  letter-spacing: 0.3px;
  background: linear-gradient(135deg, var(--indigo), var(--violet));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-size: 0.95rem;
}

.logo-sub {
  opacity: 0.7;
  font-weight: 600;
  margin-left: 0.15rem;
  color: var(--slate-700);
  font-size: 0.9rem;
}

.form-title {
  font-size: clamp(1.3rem, 3vw, 1.5rem);
  font-weight: 700;
  letter-spacing: -0.2px;
  color: #0f172a;
  line-height: 1.25;
  margin: 0;
}

.form-subtitle {
  color: var(--slate-500);
  line-height: 1.4;
  font-size: 0.9rem;
  margin: 0;
}

.login-methods {
  margin-bottom: 1.2rem;
}

.sso-btn {
  border-radius: 10px;
  padding: 0.75rem 0.9rem;
  font-weight: 600;
  border: 1.5px solid #e2e8f0;
  transition: var(--t);
  font-size: 0.92rem;
}

.sso-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.divider {
  position: relative;
  text-align: center;
  margin: 0.8rem 0 0.15rem;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background-color: var(--slate-300);
}

.divider-text {
  position: relative;
  background: #fff;
  padding: 0 0.5rem;
  color: var(--slate-500);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.form-card :deep(.form-control) {
  border-radius: 10px;
  padding: 0.7rem 0.9rem;
  border: 1.5px solid #e2e8f0;
  transition: var(--t);
  font-size: 0.95rem;
  height: auto;
}

.form-card :deep(.form-control:focus) {
  box-shadow: 0 0 0 3px rgba(91, 108, 255, 0.08);
  border-color: var(--indigo);
}

.form-card :deep(.input-group-text) {
  padding: 0.7rem 0.9rem;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-right: 0;
  border-radius: 10px 0 0 10px;
  font-size: 0.95rem;
}

.submit-btn {
  border-radius: 10px;
  padding: 0.8rem 1rem;
  font-weight: 700;
  font-size: 0.95rem;
  background: linear-gradient(135deg, var(--indigo) 0%, var(--violet) 100%);
  border: none;
  box-shadow: 0 8px 20px rgba(124, 58, 237, 0.24);
  transition: var(--t);
  height: auto;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 26px rgba(124, 58, 237, 0.3);
}

.submit-btn:disabled {
  opacity: 0.7;
  transform: none;
}

.forgot-password-link {
  color: var(--slate-500);
  text-decoration: none;
  border-bottom: 1px dashed var(--slate-300);
  font-size: 0.88rem;
  transition: var(--t);
}

.forgot-password-link:hover {
  color: var(--indigo);
  border-bottom-color: var(--indigo);
}

.register-link {
  color: var(--slate-500);
  font-size: 0.9rem;
}

.register-link a {
  color: var(--slate-700);
  text-decoration: none;
  transition: var(--t);
}

.register-link a:hover {
  color: var(--indigo);
}

.security-info {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  color: var(--slate-500);
  font-size: 0.8rem;
  padding-top: 0.8rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 0.6rem;
}

.security-info .sep {
  opacity: 0.4;
}

.bg-soft-accent {
  background: rgba(16, 185, 129, 0.1) !important;
}

.text-accent {
  color: var(--accent) !important;
}

@media (max-width: 991px) {
  .brand-content {
    max-width: 650px;
    padding: 20px;
  }
  
  .form-pane {
    padding: 20px 12px;
  }
  
  .form-card {
    width: min(460px, 95vw);
    padding: 22px 18px;
  }
}

@media (max-width: 768px) {
  .parp-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.7rem;
  }
  
  .parp-icon {
    width: 46px;
    height: 46px;
    font-size: 1.2rem;
  }
  
  .parp-label {
    font-size: 0.78rem;
  }
}

@media (max-width: 576px) {
  .form-card {
    padding: 18px 14px;
    border: none;
    box-shadow: none;
    background: transparent;
  }
  
  .form-pane {
    padding: 12px 10px;
    background: #fff;
  }
  
  .brand-points {
    grid-template-columns: 1fr;
    gap: 0.45rem;
  }
  
  .parp-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .security-info {
    flex-direction: column;
    gap: 0.35rem;
    text-align: center;
    padding-top: 0.6rem;
    margin-top: 0.5rem;
  }
  
  .security-info .sep {
    display: none;
  }
  
  .brand-content {
    padding: 18px;
  }
  
  .logo-badge {
    padding: 0.4rem 0.65rem;
  }
}

@media (min-width: 1400px) {
  .brand-content {
    max-width: 750px;
  }
  
  .form-card {
    width: min(540px, 82vw);
    padding: 28px 24px;
  }
}
</style>