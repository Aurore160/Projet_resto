<template>  
  <div class="profile-view">
    <div class="profile-container">
      <!-- En-tête compact -->
      <div class="profile-header">
        <div class="header-content">
          <h1>Mon Profil</h1>
          <p>Gérez vos informations personnelles et votre sécurité</p>
        </div>
      </div>

      <div class="profile-content">
        <!-- Colonne de gauche - Informations et sécurité combinées -->
        <div class="left-column">
          <!-- Carte profil et sécurité combinées -->
          <div class="profile-security-card">
            <div class="card-header">
              <h2>Informations Personnelles & Sécurité</h2>
            </div>
            
            <!-- Section avatar et informations de base -->
            <div class="profile-main-section">
              <div class="avatar-section">
                <div class="avatar-container">
                  <img 
                    :src="user.avatar" 
                    :alt="`${user.prenom} ${user.nom}`"
                    class="profile-avatar"
                  >
                  <div v-if="isEditing" class="avatar-edit-overlay" @click="triggerFileInput">
                    <div class="edit-icon">📷</div>
                  </div>
                  <input 
                    type="file" 
                    ref="fileInput"
                    @change="handleAvatarChange"
                    accept="image/*"
                    class="file-input"
                  >
                </div>
                <div class="basic-info">
                  <h3>{{ user.prenom }} {{ user.nom }}</h3>
                  <p>{{ user.email }}</p>
                </div>
              </div>

              <!-- Informations personnelles -->
              <div class="personal-info-section">
                <h4>Informations Personnelles</h4>
                <div class="info-grid">
                  <div class="info-group">
                    <div class="info-item">
                      <label>Nom</label>
                      <div v-if="!isEditing" class="info-value">{{ user.nom }}</div>
                      <input v-else v-model="editForm.nom" class="edit-input compact">
                    </div>
                    <div class="info-item">
                      <label>Prénom</label>
                      <div v-if="!isEditing" class="info-value">{{ user.prenom }}</div>
                      <input v-else v-model="editForm.prenom" class="edit-input compact">
                    </div>
                  </div>

                  <div class="info-group">
                    <div class="info-item">
                      <label>Postnom</label>
                      <div v-if="!isEditing" class="info-value">{{ user.postnom || '-' }}</div>
                      <input v-else v-model="editForm.postnom" class="edit-input compact">
                    </div>
                    <div class="info-item">
                      <label>Email</label>
                      <div v-if="!isEditing" class="info-value">{{ user.email }}</div>
                      <input v-else v-model="editForm.email" type="email" class="edit-input compact">
                    </div>
                  </div>

                  <div class="info-group">
                    <div class="info-item">
                      <label>Date de naissance</label>
                      <div class="info-value">{{ formatDate(user.dateNaissance) }}</div>
                    </div>
                    <div class="info-item">
                      <label>Code promo</label>
                      <div v-if="!isEditing" class="info-value promo-code">
                        {{ user.codePromo || 'Aucun' }}
                      </div>
                      <input v-else v-model="editForm.codePromo" class="edit-input compact">
                    </div>
                  </div>

                  <!-- POINTS DE FIDÉLITÉ COMME INFORMATION ORDINAIRE -->
                  <div class="info-group">
                    <div class="info-item">
                      <label>Points de fidélité</label>
                      <div class="info-value loyalty-points">
                        <span class="points-value">{{ user.pointsFidelite }}</span>
                        <span class="points-label">points</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section sécurité intégrée -->
              <div class="security-section">
                <div class="security-header">
                  <h4>Sécurité du Compte</h4>
                  <button 
                    @click="showPasswordEdit = !showPasswordEdit"
                    class="btn btn-outline compact"
                  >
                    {{ showPasswordEdit ? 'Masquer' : 'Modifier le mot de passe' }}
                  </button>
                </div>

                <!-- Formulaire de modification du mot de passe -->
                <div v-if="showPasswordEdit" class="password-edit-form">
                  <div class="form-grid">
                    <div class="form-group">
                      <label>Mot de passe actuel</label>
                      <div class="input-with-icon">
                        <input 
                          v-model="passwordForm.currentPassword"
                          :type="showCurrentPassword ? 'text' : 'password'"
                          placeholder="Mot de passe actuel"
                          class="form-input compact"
                        >
                        <button 
                          @click="showCurrentPassword = !showCurrentPassword"
                          class="input-icon"
                          type="button"
                        >
                          {{ showCurrentPassword ? '🙈' : '👁️' }}
                        </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label>Nouveau mot de passe</label>
                      <div class="input-with-icon">
                        <input 
                          v-model="passwordForm.newPassword"
                          :type="showNewPassword ? 'text' : 'password'"
                          placeholder="Nouveau mot de passe"
                          class="form-input compact"
                        >
                        <button 
                          @click="showNewPassword = !showNewPassword"
                          class="input-icon"
                          type="button"
                        >
                          {{ showNewPassword ? '🙈' : '👁️' }}
                        </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label>Confirmation</label>
                      <div class="input-with-icon">
                        <input 
                          v-model="passwordForm.confirmPassword"
                          :type="showConfirmPassword ? 'text' : 'password'"
                          placeholder="Confirmer le mot de passe"
                          class="form-input compact"
                        >
                        <button 
                          @click="showConfirmPassword = !showConfirmPassword"
                          class="input-icon"
                          type="button"
                        >
                          {{ showConfirmPassword ? '🙈' : '👁️' }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Indicateur de force -->
                  <div v-if="passwordForm.newPassword" class="password-strength">
                    <div class="strength-info">
                      <span>Force :</span>
                      <span class="strength-text" :class="getPasswordStrengthClass">
                        {{ passwordStrengthText }}
                      </span>
                    </div>
                    <div class="strength-bar">
                      <div class="strength-fill" :class="getPasswordStrengthClass"></div>
                    </div>
                  </div>

                  <!-- Validation -->
                  <div v-if="passwordForm.newPassword" class="password-validation">
                    <div class="validation-item" :class="{ valid: passwordForm.newPassword.length >= 8 }">
                      <span class="validation-icon">
                        {{ passwordForm.newPassword.length >= 8 ? '✓' : '○' }}
                      </span>
                      <span>8+ caractères</span>
                    </div>
                    <div class="validation-item" :class="{ valid: hasUpperCase }">
                      <span class="validation-icon">
                        {{ hasUpperCase ? '✓' : '○' }}
                      </span>
                      <span>Majuscule</span>
                    </div>
                    <div class="validation-item" :class="{ valid: hasNumber }">
                      <span class="validation-icon">
                        {{ hasNumber ? '✓' : '○' }}
                      </span>
                      <span>Chiffre</span>
                    </div>
                    <div class="validation-item" :class="{ valid: passwordsMatch }">
                      <span class="validation-icon">
                        {{ passwordsMatch ? '✓' : '○' }}
                      </span>
                      <span>Correspond</span>
                    </div>
                  </div>

                  <div class="password-actions">
                    <button 
                      @click="updatePassword"
                      :disabled="!canUpdatePassword"
                      class="btn btn-success compact"
                    >
                      <span class="btn-icon">🔐</span>
                      Mettre à jour le mot de passe
                    </button>
                    <button 
                      @click="cancelPasswordEdit"
                      class="btn btn-secondary compact"
                    >
                      Annuler
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions principales -->
            <div class="profile-actions">
              <button 
                v-if="!isEditing && !showPasswordEdit"
                @click="startEditing"
                class="btn btn-primary"
              >
                <span class="btn-icon">✏️</span>
                Modifier le profil
              </button>
              
              <div v-else-if="isEditing" class="edit-actions">
                <button 
                  @click="saveProfile"
                  :disabled="!isFormValid"
                  class="btn btn-success"
                >
                  <span class="btn-icon">💾</span>
                  Enregistrer les modifications
                </button>
                <button 
                  @click="cancelEditing"
                  class="btn btn-secondary"
                >
                  <span class="btn-icon">↩️</span>
                  Annuler
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Colonne de droite - Statistiques simplifiées -->
        <div class="right-column">
          <!-- Carte statistiques simplifiée -->
          <div class="stats-card">
            <div class="card-header">
              <h2>Statistiques</h2>
            </div>
            
            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-icon primary">🍽️</div>
                <div class="stat-content">
                  <span class="stat-value">24</span>
                  <span class="stat-label">Commandes</span>
                </div>
              </div>
              
              <div class="stat-item">
                <div class="stat-icon warning">💰</div>
                <div class="stat-content">
                  <span class="stat-value">156K</span>
                  <span class="stat-label">FC Dépensés</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification -->
    <div v-if="showSuccessNotification" class="notification success">
      <div class="notification-content">
        <div class="notification-icon">✅</div>
        <div class="notification-text">
          <p>Mot de passe modifié avec succès</p>
        </div>
        <button @click="showSuccessNotification = false" class="notification-close">
          ×
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'

// Références
const fileInput = ref(null)
const isEditing = ref(false)
const showPasswordEdit = ref(false)
const showSuccessNotification = ref(false)

// États pour afficher/masquer les mots de passe
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// Données utilisateur
const user = reactive({
  nom: 'Dupont',
  prenom: 'Jean',
  postnom: 'Marie',
  email: 'jean.dupont@email.com',
  dateNaissance: '1990-05-15',
  codePromo: 'WELCOME2024',
  pointsFidelite: 1250,
  avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=120&h=120&fit=crop&crop=face',
  membreDepuis: '2023'
})

// Formulaire d'édition du profil
const editForm = reactive({
  nom: '',
  prenom: '',
  postnom: '',
  email: '',
  codePromo: '',
  avatar: ''
})

// Formulaire de modification du mot de passe
const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

// Computed properties
const isFormValid = computed(() => {
  return editForm.nom && editForm.prenom && editForm.email
})

const passwordsMatch = computed(() => {
  return passwordForm.newPassword === passwordForm.confirmPassword && passwordForm.newPassword !== ''
})

// CORRECTION : Remplacer les expressions régulières complexes
const hasUpperCase = computed(() => {
  if (!passwordForm.newPassword) return false
  for (let i = 0; i < passwordForm.newPassword.length; i++) {
    if (passwordForm.newPassword[i] >= 'A' && passwordForm.newPassword[i] <= 'Z') {
      return true
    }
  }
  return false
})

const hasNumber = computed(() => {
  if (!passwordForm.newPassword) return false
  for (let i = 0; i < passwordForm.newPassword.length; i++) {
    if (passwordForm.newPassword[i] >= '0' && passwordForm.newPassword[i] <= '9') {
      return true
    }
  }
  return false
})

const hasSpecialChar = computed(() => {
  if (!passwordForm.newPassword) return false
  const specialChars = '@$!%*?&'
  for (let i = 0; i < passwordForm.newPassword.length; i++) {
    if (specialChars.includes(passwordForm.newPassword[i])) {
      return true
    }
  }
  return false
})

const hasLowerCase = computed(() => {
  if (!passwordForm.newPassword) return false
  for (let i = 0; i < passwordForm.newPassword.length; i++) {
    if (passwordForm.newPassword[i] >= 'a' && passwordForm.newPassword[i] <= 'z') {
      return true
    }
  }
  return false
})

const isPasswordStrong = computed(() => {
  const password = passwordForm.newPassword
  if (!password) return false
  
  return password.length >= 8 && 
         hasLowerCase.value && 
         hasUpperCase.value && 
         hasNumber.value && 
         hasSpecialChar.value
})

const canUpdatePassword = computed(() => {
  return passwordForm.currentPassword && 
         passwordForm.newPassword && 
         passwordForm.confirmPassword && 
         passwordsMatch.value && 
         isPasswordStrong.value
})

const passwordStrength = computed(() => {
  const password = passwordForm.newPassword
  if (!password) return 0
  
  let strength = 0
  if (password.length >= 8) strength += 1
  if (hasLowerCase.value) strength += 1
  if (hasUpperCase.value) strength += 1
  if (hasNumber.value) strength += 1
  if (hasSpecialChar.value) strength += 1
  
  return strength
})

const getPasswordStrengthClass = computed(() => {
  const strength = passwordStrength.value
  if (strength <= 2) return 'weak'
  if (strength <= 3) return 'medium'
  return 'strong'
})

const passwordStrengthText = computed(() => {
  const strength = passwordStrength.value
  if (strength <= 2) return 'Faible'
  if (strength <= 3) return 'Moyen'
  return 'Fort'
})

// Méthodes
const startEditing = () => {
  Object.assign(editForm, user)
  isEditing.value = true
}

const cancelEditing = () => {
  isEditing.value = false
  Object.keys(editForm).forEach(key => {
    editForm[key] = user[key]
  })
}

const saveProfile = async () => {
  if (!isFormValid.value) return

  try {
    Object.assign(user, editForm)
    console.log('Profil mis à jour:', editForm)
    isEditing.value = false
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error)
  }
}

const updatePassword = async () => {
  if (!canUpdatePassword.value) return

  try {
    console.log('Mot de passe mis à jour')
    
    Object.keys(passwordForm).forEach(key => {
      passwordForm[key] = ''
    })
    
    showPasswordEdit.value = false
    showSuccessNotification.value = true
    
    setTimeout(() => {
      showSuccessNotification.value = false
    }, 4000)
    
  } catch (error) {
    console.error('Erreur lors de la mise à jour du mot de passe:', error)
  }
}

const cancelPasswordEdit = () => {
  showPasswordEdit.value = false
  Object.keys(passwordForm).forEach(key => {
    passwordForm[key] = ''
  })
  showCurrentPassword.value = false
  showNewPassword.value = false
  showConfirmPassword.value = false
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (!file.type.startsWith('image/')) {
      alert('Veuillez sélectionner une image')
      return
    }

    if (file.size > 5 * 1024 * 1024) {
      alert('L\'image est trop volumineuse. Maximum 5MB autorisé.')
      return
    }

    const reader = new FileReader()
    reader.onload = (e) => {
      editForm.avatar = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

onMounted(() => {
  console.log('Composant profil chargé')
})
</script>

<style scoped>
.profile-view {
  padding: 2rem;
  min-height: 100vh;
  background: var(--primary-color);
}

.profile-container {
  max-width: 1200px;
  margin: 0 auto;
}

/* Variables et reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* En-tête compact */
.profile-header {
  margin-bottom: 2.5rem;
}

.header-content {
  text-align: center;
  color: white;
}

.header-content h1 {
  font-size: 3.2rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  text-shadow: 0 2px 2px rgba(0,0,0,0.3);
}

.header-content p {
  font-size: 1.3rem;
  opacity: 0.9;
}

/* Grille principale */
.profile-content {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 1.5rem;
  align-items: start;
}

/* Carte combinée profil et sécurité */
.profile-security-card {
  background: var(--secondary-color);
  border-radius: 12px;
  box-shadow: var(--accent-color);
  overflow: hidden;
}

.card-header {
  padding: 1rem 1.25rem;
  border-bottom: var(--accent-color);
  background: var(--accent-color);
}

.card-header h2 {
  color: var(--dark);
  font-size: 1.1rem;
  font-weight: 600;
}

/* Section principale combinée */
.profile-main-section {
  padding: 1.5rem;
}

/* Section avatar et info de base */
.avatar-section {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--light);
}

.avatar-container {
  position: relative;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  cursor: pointer;
  flex-shrink: 0;
}

.profile-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-edit-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(52, 152, 219, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.3s;
}

.avatar-container:hover .avatar-edit-overlay {
  opacity: 1;
}

.edit-icon {
  font-size: 1.5rem;
}

.basic-info {
  flex: 1;
}

.basic-info h3 {
  color: var(--primary-color);
  font-size: 1.3rem;
  margin-bottom: 0.25rem;
}

.basic-info p {
  color: var(--primary-color);
  font-size: 0.9rem;
  margin-bottom: 0.75rem;
}

/* Informations personnelles */
.personal-info-section {
  margin-bottom: 1.5rem;
}

.personal-info-section h4 {
  color: var(--success-color);
  font-size: 1rem;
  margin-bottom: 1rem;
  font-weight: 600;
}

.info-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item label {
  color: var(--success-color);
  font-size: 0.8rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  color: var(--primary-color);
  font-size: 0.8rem;
  font-weight: 500;
}

.promo-code {
  background: var(--primary-color);
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: var(--danger);
  display: inline-block;
  font-size: 0.85rem;
}

/* Section sécurité */
.security-section {
  border-top: 1px solid var(--accent-color);
  padding-top: 1.5rem;
}

.security-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.security-header h4 {
  color: var(--success-color);
  font-size: 1rem;
  font-weight: 600;
}

/* Formulaire mot de passe */
.password-edit-form {
  background: var(--primary-color);
  padding: 1.25rem;
  border-radius: 8px;
  border: 1px solid var(--light);
}

.form-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  color: var(--secondary-color);
  font-weight: 600;
  font-size: 0.85rem;
  margin-bottom: 0.35rem;
}

.input-with-icon {
  position: relative;
}

.input-icon {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  padding: 0.2rem;
  border-radius: 4px;
  transition: background 0.3s;
}

.input-icon:hover {
  background: var(--success-color);
}

/* Champs d'édition */
.edit-input.compact,
.form-input.compact {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1.5px solid var(--light);
  border-radius: 6px;
  font-size: 0.9rem;
  transition: all 0.3s;
  background: white;
}

.edit-input.compact:focus,
.form-input.compact:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
}

/* Force du mot de passe */
.password-strength {
  margin-bottom: 1rem;
}

.strength-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.35rem;
  font-size: 0.8rem;
}

.strength-text.weak { color: var(--danger); }
.strength-text.medium { color: var(--warning); }
.strength-text.strong { color: var(--success); }

.strength-bar {
  height: 4px;
  background: var(--primary-color);
  border-radius: 2px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  transition: all 0.3s;
}

.strength-fill.weak {
  width: 33%;
  background: var(--danger);
}

.strength-fill.medium {
  width: 66%;
  background: var(--warning);
}

.strength-fill.strong {
  width: 100%;
  background: var(--success);
}

/* Validation */
.password-validation {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.75rem;
}

.validation-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--gray);
}

.validation-item.valid {
  color: var(--success);
}

.validation-icon {
  font-weight: bold;
  font-size: 0.7rem;
}

/* Actions */
.profile-actions {
  padding: 1.25rem;
  border-top: 1px solid var(--light);
  background: var(--accent-color);
}

.btn {
  padding: 0.75rem 1.25rem;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--primary);
  color: var(--secondary-color);
}

.btn-success {
  background: var(--success);
  color: var(--secondary-color);
}

.btn-secondary {
  background: var(--secondary-color);
  color: white;
}

.btn-outline {
  background: transparent;
  border: 1.5px solid var(--primary);
  color: var(--primary);
  font-size: 0.8rem;
  padding: 0.5rem 0.75rem;
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.edit-actions,
.password-actions {
  display: flex;
  gap: 0.75rem;
}

/* Colonne de droite */
.right-column {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Cartes de droite */
.stats-card {
  background: var(--secondary-color);
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
  overflow: hidden;
}

/* Statistiques simplifiées */
.stats-grid {
  padding: 1.25rem;
  display: grid;
  gap: 1rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--light);
  border-radius: 8px;
  transition: transform 0.3s;
}

.stat-item:hover {
  transform: translateX(3px);
}

.stat-icon {
  width: 45px;
  height: 45px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}

.stat-icon.primary { background: #d4edff; color: var(--primary); }
.stat-icon.warning { background: #fff3cd; color: var(--warning); }

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--success-color);
}

.stat-label {
  color: var(--fin-color);
  font-size: 0.85rem;
}

/* Notification */
.notification {
  position: fixed;
  top: 6rem;
  right: 1rem;
  z-index: 1000;
  animation: slideInRight 0.3s ease-out;
}

.notification.success {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  border-left: 3px solid var(--success);
}

.notification-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  min-width: 250px;
}

.notification-icon {
  font-size: 1.2rem;
  color: var(--success);
}

.notification-text p {
  color: var(--dark);
  font-size: 0.85rem;
  font-weight: 500;
}

.notification-close {
  background: none;
  border: none;
  font-size: 1.1rem;
  cursor: pointer;
  color: var(--gray);
  padding: 0.2rem;
  border-radius: 3px;
  transition: background 0.3s;
  margin-left: auto;
}

.notification-close:hover {
  background: var(--light);
}

/* Animations */
@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Responsive */
@media (max-width: 1024px) {
  .profile-content {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .profile-view {
    padding: 0.75rem;
  }
  
  .info-group {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
  
  .avatar-section {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .password-validation {
    grid-template-columns: 1fr;
  }
  
  .edit-actions,
  .password-actions {
    flex-direction: column;
  }
  
  .security-header {
    flex-direction: column;
    gap: 0.75rem;
    align-items: flex-start;
  }
  
  .notification {
    right: 0.75rem;
    left: 0.75rem;
  }
}

@media (max-width: 480px) {
  .profile-view {
    padding: 0.5rem;
  }
  
  .header-content h1 {
    font-size: 1.5rem;
  }
  
  .profile-main-section {
    padding: 1rem;
  }
  
  .card-header {
    padding: 0.75rem 1rem;
  }
  
  .avatar-container {
    width: 70px;
    height: 70px;
  }
}
</style>