<template>
  <div class="contact-page">
    <Header />
    <main class="contact-main">
      <section class="contact-left">
        <h2>Contactez nous</h2>
        <p><strong>Adresse</strong><br/>Wenge Q/Righini, C/lemba, Kinshasa, RDC</p>
        <p><strong>Téléphone</strong><br/><a :href="whatsappLink" target="_blank">{{ phone }}</a></p>
        <p><strong>E-mail</strong><br/><a href="mailto:rogermulumbu13@gmail.com">zeduc@gmail.com</a></p>
        <p><strong>Heures</strong><br/>Lundi-Vendredi 8h-21h<br/>Samedi 9h-00h<br/>Dimanche 9h-23h</p>
      </section>

      <section class="contact-form">
        <div class="form-card">
          <h3>Envoyez-nous un message</h3>
          <form @submit.prevent="send">
            <input v-model="name" placeholder="Nom complet" required />
            <input v-model="email" type="email" placeholder="E-mail" required />
            <input v-model="phoneInput" placeholder="Téléphone" />
            <textarea v-model="message" placeholder="Votre message" required></textarea>
            <button type="submit">Envoyez</button>
          </form>
        </div>
      </section>
    </main>
    <Footer />
  </div>
</template>

<script setup>
import Header from "@/component/Header.vue";
import Footer from "@/component/Footer.vue";
import { ref } from "vue";
const name = ref(""); const email = ref(""); const phoneInput = ref(""); const message = ref("");
const phone = "+243 825181727";
const whatsappLink = `https://wa.me/${phone.replace(/\D/g,'')}`;

const send = () => {
  alert("Message envoyé — merci !");
  name.value=""; email.value=""; phoneInput.value=""; message.value="";
};
</script>

<style scoped>

.contact-main{
  max-width:1200px;
  margin:24px auto;
  display:flex;
  gap:18px;
  padding:0 16px;
}

.contact-left{ 
    flex:1; 
    background:transparent; 
    color:#222; 
}

.contact-form{ 
    flex:1; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
}

.form-card{ 
    width:100%; 
    background:url('@/assets/contact.jpg') center/cover; 
    padding:24px; 
    border-radius:18px; 
    color:#fff; 
}

.form-card input, .form-card textarea{
    width:100%; 
    padding:10px; 
    margin-bottom:10px; 
    border-radius:8px; 
    border:none; 
    opacity:0.95;
}

.form-card button{ 
    width:100%; 
    padding:12px; 
    border-radius:8px; 
    background:#cfbd97; 
    color:#fff; 
    border:none; }


@media (max-width:900px){
  .contact-main{ 
    flex-direction:column; 
}
}
</style>