<template>
  <div class="reviews-page">
    <HeaderSpecified />

    <main class="container review-content">
      <h2>Mes avis </h2>

      <div v-if="userReviews.length === 0" class="empty">
        <p>Vous n’avez encore laissé aucun avis.</p>
        <router-link to="/user" class="btn accent">Donner un avis</router-link>
      </div>

      <div v-else class="review-grid">
        <article
          v-for="(rev, index) in userReviews"
          :key="rev.date"
          class="review-card"
        >
          <div class="review-header">
            <strong>Plat #{{ rev.idPlat }}</strong>
            <span class="review-date">{{ formatDate(rev.date) }}</span>
          </div>
          <p class="review-note">Note : ⭐ {{ rev.note }}/5</p>
          <p class="review-text">{{ rev.message }}</p>

          <div class="review-actions">
            <button class="btn edit" @click="openEdit(index)">Modifier</button>
            <button class="btn remove" @click="deleteReview(index)">Supprimer </button>
          </div>
        </article>
      </div>

    
      <div v-if="editOpen" class="edit-overlay" @click.self="closeEdit">
        <div class="edit-card">
          <h3>Modifier l’avis</h3>
          <label>Note (1 à 5)</label>
          <input type="number" min="1" max="5" v-model="editForm.note" />
          <label>Commentaire</label>
          <textarea v-model="editForm.message"></textarea>
          <div class="edit-actions">
            <button @click="saveEdit">Enregistrer</button>
            <button @click="closeEdit">Annuler</button>
          </div>
        </div>
      </div>
    </main>

    <FooterSpecified />
  </div>
</template>

<script setup>
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import { ref, reactive, computed } from "vue";
import {useUserStore} from "@/stores/userStore.js";


const useStore = useUserStore();


const userReviews = computed(() =>
  store.state.reviews.filter(
    (r) => r.user === (store.state.user?.email || "Anonyme")
  )
);


const editOpen = ref(false);
const editIndex = ref(null);
const editForm = reactive({ note: 0, message: "" });

function openEdit(index) {
  editIndex.value = index;
  editForm.note = userReviews.value[index].note;
  editForm.message = userReviews.value[index].message;
  editOpen.value = true;
}

function closeEdit() {
  editOpen.value = false;
  editIndex.value = null;
}


function saveEdit() {
  if (!editForm.message || !editForm.note) {
    alert("Veuillez remplir tous les champs.");
    return;
  }
  const idx = store.state.reviews.findIndex(
    (r) =>
      r.user === (store.state.user?.email || "Anonyme") &&
      r.date === userReviews.value[editIndex.value].date
  );
  if (idx !== -1) {
    store.state.reviews[idx].note = editForm.note;
    store.state.reviews[idx].message = editForm.message;
    store.addNotification("Avis modifié avec succès !");
    localStorage.setItem("reviews", JSON.stringify(store.state.reviews));
  }
  closeEdit();
}


function deleteReview(index) {
  if (!confirm("Supprimer cet avis ?")) return;
  const review = userReviews.value[index];
  store.state.reviews = store.state.reviews.filter(
    (r) => r.date !== review.date
  );
  localStorage.setItem("reviews", JSON.stringify(store.state.reviews));
  store.addNotification("Avis supprimé avec succès !");
}


function formatDate(d) {
  return new Date(d).toLocaleString();
}
</script>

<style scoped>
@import "@/assets/site.css";

.review-content {
  padding: 24px 16px;
  max-width: 900px;
  margin: auto;
}

.review-grid {
  display: grid;
  gap: 16px;
  margin-top: 20px;
}

.review-card {
  background:#d6c7a6;
  border-radius: 12px;
  padding: 14px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.review-note {
  font-weight: 600;
  color: var(--accent);
  margin: 6px 0;
}

.review-text {
  font-size: 0.95rem;
  line-height: 1.4;
}

.review-actions {
  margin-top: 8px;
  display: flex;
  gap: 8px;
}

.btn {
  padding: 8px 12px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.edit {
  background: var(--accent);
  color: white;
}

.remove {
  background: #e74c3c;
  color: white;
}


.edit-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 90;
}

.edit-card {
  background: white;
  width: 360px;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
}

.edit-card textarea {
  width: 100%;
  height: 80px;
  resize: none;
  border-radius: 6px;
  border: 1px solid #ccc;
  margin-top: 4px;
}

.edit-card input {
  width: 100%;
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 6px;
}

.edit-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 12px;
}

.empty {
  text-align: center;
  padding: 60px 20px;
}

@media (max-width: 600px) {
  .review-card {
    font-size: 0.9rem;
  }
}
</style>
