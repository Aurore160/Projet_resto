<template>
  <div v-if="show" class="userform-backdrop">
    <div class="userform-card">
      <!-- En-tête fixe -->
      <div class="modal-header">
        <h4 class="mb-0">{{ isEdit ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' }}</h4>
        <button class="btn-close" @click="close" aria-label="Fermer"></button>
      </div>

      <!-- Contenu scrollable -->
      <div class="modal-body">
        <form @submit.prevent="submit">
          <div class="row g-3">
            <!-- Nom & Prénom -->
            <div class="col-md-6">
              <label class="form-label">Nom</label>
              <input v-model="local.nom" required class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Prénom</label>
              <input v-model="local.prenom" required class="form-control" />
            </div>

            <!-- Email -->
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input
                v-model="local.email"
                type="email"
                @input="validateEmail"
                :class="{ 'is-invalid': emailError }"
                required
                class="form-control"
                placeholder="exemple@domaine.com"
              />
              <div v-if="emailError" class="invalid-feedback">{{ emailError }}</div>
            </div>

            <!-- Mot de passe -->
            <div class="col-md-6">
              <label class="form-label">Mot de passe</label>
              <input
                v-model="local.motdepasse"
                :required="!isEdit"
                type="password"
                class="form-control"
                :placeholder="isEdit ? 'Laisser vide pour ne pas changer' : ''"
              />
            </div>

            <!-- Téléphone -->
            <div class="col-md-6">
              <label class="form-label">Téléphone</label>
              <input
                v-model="local.telephone"
                @input="validatePhone"
                :class="{ 'is-invalid': phoneError }"
                class="form-control"
                placeholder="+243 ..."
              />
              <div v-if="phoneError" class="invalid-feedback">{{ phoneError }}</div>
            </div>

            <!-- Rôle & Statut -->
            <div class="col-md-6">
              <label class="form-label">Rôle</label>
              <select v-model="local.role" class="form-select">
                <option value="client">Client</option>
                <option value="employe">Employé</option>
                <option value="gestionnaire">Gestionnaire</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Statut</label>
              <select v-model="local.statut" class="form-select">
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
              </select>
            </div>

            <!-- Adresses -->
            <div class="col-md-6">
              <label class="form-label">Adresse de livraison</label>
              <input v-model="local.adresseLivraison" required class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Adresse de facturation</label>
              <input v-model="local.adresseFacturation" required class="form-control" />
            </div>

            <!-- Image -->
            <div class="col-12">
              <label class="form-label">Photo de profil</label>
              <div class="image-upload-container">
                <div class="image-preview" @click="triggerFileInput">
                  <img v-if="imagePreview" :src="imagePreview" alt="Prévisualisation" />
                  <div v-else class="image-placeholder">
                    <i class="bi bi-person-circle"></i>
                    <span>Cliquer pour choisir une image</span>
                  </div>
                  <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    @change="handleImageUpload"
                    class="visually-hidden"
                  />
                </div>
                <button
                  v-if="imagePreview || local.image"
                  type="button"
                  class="btn btn-sm btn-outline-danger mt-2"
                  @click.stop="removeImage"
                >
                  <i class="bi bi-trash"></i> Supprimer l'image
                </button>
              </div>
            </div>

            <!-- Notes -->
            
          </div>
        </form>
      </div>

      <!-- Pied de page fixe -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="close">Annuler</button>
        <button
          type="button"
          class="btn btn-primary"
          
          @click="submit"
        >
          Enregistrer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, nextTick } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  user: { type: Object, default: null }
})
const emits = defineEmits(['update:modelValue', 'save'])

const show = ref(props.modelValue)
const fileInput = ref(null)
const imageFile = ref(null)
const imagePreview = ref(null)

// Erreurs de validation
const emailError = ref('')
const phoneError = ref('')

const local = ref(createEmptyUser())

// Synchronisation
watch(() => props.modelValue, (v) => {
  show.value = v
  if (v) {
    resetForm()
    nextTick(() => {
      if (props.user) {
        local.value = { ...props.user }
        imagePreview.value = props.user.image || null
      }
    })
  }
})

const isEdit = computed(() => !!(local.value && local.value.id))

// Validation
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const phoneRegex = /^\+?[0-9\s\-\(\)]{9,15}$/

function validateEmail() {
  const email = local.value.email
  if (!email) {
    emailError.value = ''
  } else if (!emailRegex.test(email)) {
    emailError.value = 'Veuillez entrer un email valide'
  } else {
    emailError.value = ''
  }
}

function validatePhone() {
  const phone = local.value.telephone
  if (!phone) {
    phoneError.value = ''
  } else if (!phoneRegex.test(phone)) {
    phoneError.value = 'Format de téléphone invalide (ex: +243 970 123 456)'
  } else {
    phoneError.value = ''
  }
}

const isFormValid = computed(() => {
  return (
    local.value.nom &&
    local.value.prenom &&
    local.value.email &&
    !emailError.value &&
    local.value.adresseLivraison &&
    local.value.adresseFacturation &&
    !phoneError.value &&
    (!isEdit.value || local.value.motdepasse || props.user?.id) // mot de passe optionnel en édition
  )
})

function createEmptyUser() {
  return {
    id: null,
    nom: '',
    prenom: '',
    email: '',
    motdepasse: '',
    telephone: '',
    adresseLivraison: '',
    adresseFacturation: '',
    role: 'client',
    statut: 'actif',
    notes: '',
    image: null
  }
}

function resetForm() {
  local.value = createEmptyUser()
  imageFile.value = null
  imagePreview.value = null
  emailError.value = ''
  phoneError.value = ''
}

function triggerFileInput() {
  fileInput.value?.click()
}

function handleImageUpload(event) {
  const file = event.target.files[0]
  if (!file) return
  imageFile.value = file
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function removeImage() {
  imageFile.value = null
  imagePreview.value = null
  if (fileInput.value) fileInput.value.value = ''
}

function close() {
  resetForm()
  emits('update:modelValue', false)
}

function submit() {
  if (!isFormValid.value) return

  const payload = { ...local.value }

  if (imageFile.value) {
    payload.image = imagePreview.value
  }

  if (!payload.id) payload.id = Date.now()

  emits('save', payload)
  resetForm()
  emits('update:modelValue', false)
}
</script>

<style scoped>
:root {
  --color-primary: #8a8174;
  --color-secondary: #a89f91;
  --color-light: #f5f3f0;
  --color-dark: #3a352f;
}

.userform-backdrop {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(2, 6, 23, 0.5);
  z-index: 1200;
  padding: 1rem;
}

.userform-card {
  width: 720px;
  max-width: 100%;
  background: white;
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  border: 1px solid var(--color-secondary);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.modal-header {
  padding: 18px 24px 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
}

.modal-body {
  padding: 0 24px;
  overflow-y: auto;
  max-height: calc(90vh - 140px); /* ajusté pour laisser place au header + footer */
}

.modal-footer {
  padding: 16px 24px 24px;
  display: flex;
  justify-content: end;
  gap: 12px;
  border-top: 1px solid #eee;
  margin-top: auto;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-dark);
  margin-bottom: 6px;
}

.form-control,
.form-select {
  border: 1px solid #ddd;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 0.95rem;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(138, 129, 116, 0.15);
}

.form-control.is-invalid {
  border-color: #e53e3e;
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.875em;
  color: #e53e3e;
}

.btn-close {
  background: transparent;
  border: 0;
  width: 28px;
  height: 28px;
  font-size: 1.2rem;
  color: #666;
  opacity: 1;
}

.btn-close:hover {
  color: var(--color-primary);
}

/* Image upload */
.image-upload-container {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.image-preview {
  width: 120px;
  height: 120px;
  border-radius: 12px;
  overflow: hidden;
  border: 2px dashed #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: border-color 0.2s;
  background: var(--color-light);
}

.image-preview:hover {
  border-color: var(--color-primary);
}

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  text-align: center;
  color: #888;
  font-size: 0.9rem;
}

.image-placeholder i {
  font-size: 2.5rem;
  color: var(--color-secondary);
  margin-bottom: 6px;
}

.visually-hidden {
  position: absolute !important;
  width: 1px !important;
  height: 1px !important;
  padding: 0 !important;
  margin: -1px !important;
  overflow: hidden !important;
  clip: rect(0, 0, 0, 0) !important;
  white-space: nowrap !important;
  border: 0 !important;
}

/* Boutons */
.btn-primary {
  background: green;
  border: none;
  padding: 10px 20px;
  font-weight: 600;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #639a73;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f0f0f0;
  border: 1px solid #ddd;
  color: #444;
}

.btn-secondary:hover {
  background: #e5e5e5;
}
</style>